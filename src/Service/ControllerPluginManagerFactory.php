<?php

/**
 * @see       https://github.com/laminas/laminas-mvc for the canonical source repository
 * @copyright https://github.com/laminas/laminas-mvc/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-mvc/blob/master/LICENSE.md New BSD License
 */

namespace Laminas\Mvc\Service;

use Laminas\Mvc\Controller\PluginManager;
use Laminas\ServiceManager\ServiceLocatorInterface;

class ControllerPluginManagerFactory extends AbstractPluginManagerFactory
{
    const PLUGIN_MANAGER_CLASS = 'Laminas\Mvc\Controller\PluginManager';

    /**
     * Create and return the MVC controller plugin manager
     *
     * @param  ServiceLocatorInterface $serviceLocator
     * @return PluginManager
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $plugins = parent::createService($serviceLocator);
        return $plugins;
    }
}
