<?php

/**
 * @see       https://github.com/laminasframwork/laminas-mvc for the canonical source repository
 * @copyright https://github.com/laminasframwork/laminas-mvc/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminasframwork/laminas-mvc/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace LaminasTest\Mvc\Service;

use ArrayObject;
use Interop\Container\ContainerInterface;
use Laminas\Mvc\Service\InjectTemplateListenerFactory;
use Laminas\Mvc\View\Http\InjectTemplateListener;
use Laminas\ServiceManager\ServiceLocatorInterface;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject;

/**
 * Tests for {@see \Laminas\Mvc\Service\InjectTemplateListenerFactory}
 *
 * @covers \Laminas\Mvc\Service\InjectTemplateListenerFactory
 */
class InjectTemplateListenerFactoryTest extends TestCase
{
    public function testFactoryCanCreateInjectTemplateListener()
    {
        $this->buildInjectTemplateListenerWithConfig([]);
    }

    public function testFactoryCanSetControllerMap()
    {
        $listener = $this->buildInjectTemplateListenerWithConfig([
            'view_manager' => [
                'controller_map' => [
                    'SomeModule' => 'some/module',
                ],
            ],
        ]);

        $this->assertEquals('some/module', $listener->mapController('SomeModule'));
    }

    public function testFactoryCanSetControllerMapViaArrayAccessVM()
    {
        $listener = $this->buildInjectTemplateListenerWithConfig([
            'view_manager' => new ArrayObject([
                'controller_map' => [
                    // must be an array due to type hinting on setControllerMap()
                    'SomeModule' => 'some/module',
                ],
            ]),
        ]);

        $this->assertEquals('some/module', $listener->mapController('SomeModule'));
    }

    /**
     * @param mixed $config
     *
     * @return PHPUnit_Framework_MockObject_MockObject|InjectTemplateListener
     */
    private function buildInjectTemplateListenerWithConfig($config)
    {
        $serviceLocator = $this->prophesize(ServiceLocatorInterface::class);
        $serviceLocator->willImplement(ContainerInterface::class);

        $serviceLocator->get('config')->willReturn($config);

        $factory  = new InjectTemplateListenerFactory();
        $listener = $factory($serviceLocator->reveal(), 'InjectTemplateListener');

        $this->assertInstanceOf(InjectTemplateListener::class, $listener);

        return $listener;
    }
}
