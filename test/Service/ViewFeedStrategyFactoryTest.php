<?php

/**
 * @see       https://github.com/laminasframwork/laminas-mvc for the canonical source repository
 * @copyright https://github.com/laminasframwork/laminas-mvc/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminasframwork/laminas-mvc/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace LaminasTest\Mvc\Service;

use Interop\Container\ContainerInterface;
use Laminas\Mvc\Service\ViewFeedStrategyFactory;
use Laminas\View\Renderer\FeedRenderer;
use Laminas\View\Strategy\FeedStrategy;
use PHPUnit\Framework\TestCase;

class ViewFeedStrategyFactoryTest extends TestCase
{
    private function createContainer()
    {
        $renderer  = $this->prophesize(FeedRenderer::class);
        $container = $this->prophesize(ContainerInterface::class);
        $container->get('ViewFeedRenderer')->will(function () use ($renderer) {
            return $renderer->reveal();
        });
        return $container->reveal();
    }

    public function testReturnsFeedStrategy()
    {
        $factory = new ViewFeedStrategyFactory();
        $result  = $factory($this->createContainer(), 'ViewFeedStrategy');
        $this->assertInstanceOf(FeedStrategy::class, $result);
    }
}
