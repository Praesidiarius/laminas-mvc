<?php

/**
 * @see       https://github.com/laminasframwork/laminas-mvc for the canonical source repository
 * @copyright https://github.com/laminasframwork/laminas-mvc/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminasframwork/laminas-mvc/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace LaminasTest\Mvc\Controller\Plugin\TestAsset;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class SamplePluginWithConstructorFactory implements FactoryInterface
{
    protected $options;

    public function __invoke(ContainerInterface $container, $name, ?array $options = null)
    {
        return new SamplePluginWithConstructor($options);
    }

    public function setCreationOptions(array $options)
    {
        $this->options = $options;
    }
}
