<?php

/**
 * @see       https://github.com/laminasframwork/laminas-mvc for the canonical source repository
 * @copyright https://github.com/laminasframwork/laminas-mvc/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminasframwork/laminas-mvc/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace LaminasTest\Mvc\Application;

use Laminas\Mvc\MvcEvent;
use Laminas\Router\RouteMatch;
use PHPUnit\Framework\TestCase;

/**
 * @coversNothing
 */
class RoutingSuccessTest extends TestCase
{
    use PathControllerTrait;

    public function testRoutingIsExcecutedDuringRun()
    {
        $application = $this->prepareApplication();

        $log = [];

        $application->getEventManager()->attach(MvcEvent::EVENT_ROUTE, function ($e) use (&$log) {
            $match = $e->getRouteMatch();
            $this->assertInstanceOf(RouteMatch::class, $match, 'Did not receive expected route match');
            $log['route-match'] = $match;
        }, -100);

        $application->run();
        $this->assertArrayHasKey('route-match', $log);
        $this->assertInstanceOf(RouteMatch::class, $log['route-match']);
    }
}
