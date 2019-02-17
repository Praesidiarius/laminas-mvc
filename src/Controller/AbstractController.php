<?php
/**
 * @see       https://github.com/zendframework/zend-mvc for the canonical source repository
 * @copyright Copyright (c) 2005-2019 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-mvc/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Zend\Mvc\Controller;

use Zend\EventManager\EventInterface as Event;
use Zend\EventManager\EventManager;
use Zend\EventManager\EventManagerAwareInterface;
use Zend\EventManager\EventManagerInterface;
use Zend\Http\PhpEnvironment\Response as HttpResponse;
use Zend\Http\Request as HttpRequest;
use Zend\Mvc\InjectApplicationEventInterface;
use Zend\Mvc\MvcEvent;
use Zend\ServiceManager\ServiceManager;
use Zend\Stdlib\DispatchableInterface as Dispatchable;
use Zend\Stdlib\RequestInterface as Request;
use Zend\Stdlib\ResponseInterface as Response;

use function array_merge;
use function array_values;
use function call_user_func_array;
use function class_implements;
use function is_callable;
use function lcfirst;
use function str_replace;
use function strrpos;
use function strstr;
use function substr;
use function ucwords;

/**
 * Abstract controller
 *
 * Convenience methods for pre-built plugins (@see __call):
 *
 * @codingStandardsIgnoreStart
 * @method ModelInterface acceptableViewModelSelector(array $matchAgainst = null, bool $returnDefault = true, AbstractFieldValuePart $resultReference = null)
 * @codingStandardsIgnoreEnd
 * @method Forward forward()
 * @method Plugin\Layout|ModelInterface layout(string $template = null)
 * @method Plugin\Params|mixed params(string $param = null, mixed $default = null)
 * @method Plugin\Redirect redirect()
 * @method Plugin\Url url()
 * @method ViewModel createHttpNotFoundModel(Response $response)
 */
abstract class AbstractController implements
    Dispatchable,
    EventManagerAwareInterface,
    InjectApplicationEventInterface
{
    /** @var PluginManager */
    protected $plugins;

    /** @var Request */
    protected $request;

    /** @var Response */
    protected $response;

    /** @var MvcEvent */
    protected $event;

    /** @var EventManagerInterface */
    protected $events;

    /** @var null|string|string[] */
    protected $eventIdentifier;

    /**
     * Execute the request
     *
     * @param  MvcEvent $e
     * @return mixed
     */
    abstract public function onDispatch(MvcEvent $e);

    /**
     * Dispatch a request
     *
     * @events dispatch.pre, dispatch.post
     * @param  Request       $request
     * @param  null|Response $response
     * @return Response|mixed
     */
    public function dispatch(Request $request, ?Response $response = null)
    {
        $this->request = $request;
        if (! $response) {
            $response = new HttpResponse();
        }
        $this->response = $response;

        $e = $this->getEvent();
        $e->setName(MvcEvent::EVENT_DISPATCH);
        $e->setRequest($request);
        $e->setResponse($response);
        $e->setTarget($this);

        $result = $this->getEventManager()->triggerEventUntil(function ($test) {
            return $test instanceof Response;
        }, $e);

        if ($result->stopped()) {
            return $result->last();
        }

        return $e->getResult();
    }

    /**
     * Get request object
     *
     * @return Request
     */
    public function getRequest()
    {
        if (! $this->request) {
            $this->request = new HttpRequest();
        }

        return $this->request;
    }

    /**
     * Get response object
     *
     * @return Response
     */
    public function getResponse()
    {
        if (! $this->response) {
            $this->response = new HttpResponse();
        }

        return $this->response;
    }

    /**
     * Set the event manager instance used by this context
     *
     * @param  EventManagerInterface $events
     * @return AbstractController
     */
    public function setEventManager(EventManagerInterface $events)
    {
        $className = static::class;

        $identifiers = [
            self::class,
            $className,
        ];

        $rightmostNsPos = strrpos($className, '\\');
        if ($rightmostNsPos) {
            $identifiers[] = strstr($className, '\\', true); // top namespace
            $identifiers[] = substr($className, 0, $rightmostNsPos); // full namespace
        }

        $events->setIdentifiers(array_merge(
            $identifiers,
            array_values(class_implements($className)),
            (array) $this->eventIdentifier
        ));

        $this->events = $events;
        $this->attachDefaultListeners();

        return $this;
    }

    /**
     * Retrieve the event manager
     *
     * Lazy-loads an EventManager instance if none registered.
     *
     * @return EventManagerInterface
     */
    public function getEventManager()
    {
        if (! $this->events) {
            $this->setEventManager(new EventManager());
        }

        return $this->events;
    }

    /**
     * Set an event to use during dispatch
     *
     * By default, will re-cast to MvcEvent if another event type is provided.
     *
     * @param  Event $e
     * @return void
     */
    public function setEvent(Event $e)
    {
        if (! $e instanceof MvcEvent) {
            $eventParams = $e->getParams();
            $e           = new MvcEvent();
            $e->setParams($eventParams);
            unset($eventParams);
        }
        $this->event = $e;
    }

    /**
     * Get the attached event
     *
     * Will create a new MvcEvent if none provided.
     *
     * @return MvcEvent
     */
    public function getEvent()
    {
        if (! $this->event) {
            $this->setEvent(new MvcEvent());
        }

        return $this->event;
    }

    /**
     * Get plugin manager
     *
     * @return PluginManager
     */
    public function getPluginManager()
    {
        if (! $this->plugins) {
            $this->setPluginManager(new PluginManager(new ServiceManager()));
        }

        $this->plugins->setController($this);
        return $this->plugins;
    }

    /**
     * Set plugin manager
     *
     * @param  PluginManager $plugins
     * @return AbstractController
     */
    public function setPluginManager(PluginManager $plugins)
    {
        $this->plugins = $plugins;
        $this->plugins->setController($this);

        return $this;
    }

    /**
     * Get plugin instance
     *
     * @param  string     $name    Name of plugin to return
     * @param  null|array $options Options to pass to plugin constructor (if not already instantiated)
     * @return mixed
     */
    public function plugin($name, ?array $options = null)
    {
        return $this->getPluginManager()->get($name, $options);
    }

    /**
     * Method overloading: return/call plugins
     *
     * If the plugin is a functor, call it, passing the parameters provided.
     * Otherwise, return the plugin instance.
     *
     * @param  string $method
     * @param  array  $params
     * @return mixed
     */
    public function __call($method, $params)
    {
        $plugin = $this->plugin($method);
        if (is_callable($plugin)) {
            return call_user_func_array($plugin, $params);
        }

        return $plugin;
    }

    /**
     * Register the default events for this controller
     *
     * @return void
     */
    protected function attachDefaultListeners()
    {
        $events = $this->getEventManager();
        $events->attach(MvcEvent::EVENT_DISPATCH, [$this, 'onDispatch']);
    }

    /**
     * Transform an "action" token into a method name
     *
     * @param  string $action
     * @return string
     */
    public static function getMethodFromAction($action)
    {
        $method = str_replace(['.', '-', '_'], ' ', $action);
        $method = ucwords($method);
        $method = str_replace(' ', '', $method);
        $method = lcfirst($method);
        return $method . 'Action';
    }
}
