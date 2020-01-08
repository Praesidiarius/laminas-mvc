<?php

/**
 * @see       https://github.com/laminasframwork/laminas-mvc for the canonical source repository
 * @copyright https://github.com/laminasframwork/laminas-mvc/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminasframwork/laminas-mvc/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace LaminasTest\Mvc\Application;

use Laminas\Mvc\Application;
use Laminas\Mvc\MvcEvent;
use PHPUnit\Framework\TestCase;

/**
 * @coversNothing
 */
class InabilityToRetrieveControllerShouldTriggerDispatchErrorTest extends TestCase
{
    use MissingControllerTrait;

    /**
     * @group error-handling
     */
    public function testInabilityToRetrieveControllerShouldTriggerDispatchError()
    {
        $application = $this->prepareApplication();

        $response = $application->getResponse();
        $events   = $application->getEventManager();
        $events->attach(MvcEvent::EVENT_DISPATCH_ERROR, function ($e) use ($response) {
            $error      = $e->getError();
            $controller = $e->getController();
            $response->setContent('Code: ' . $error . '; Controller: ' . $controller);
            return $response;
        });

        $application->run();
        $this->assertStringContainsString(Application::ERROR_CONTROLLER_NOT_FOUND, $response->getContent());
        $this->assertStringContainsString('bad', $response->getContent());
    }
}
