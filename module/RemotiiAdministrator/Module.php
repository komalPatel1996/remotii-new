<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2013 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace RemotiiAdministrator;

use Zend\Mvc\ModuleRouteListener;
use Zend\Mvc\MvcEvent;

class Module
{
    public function onBootstrap(MvcEvent $e)
    {
        $eventManager        = $e->getApplication()->getEventManager();
        $moduleRouteListener = new ModuleRouteListener();
        $moduleRouteListener->attach($eventManager);
        
        //  function is used to render the template Module wise
        $e->getApplication()->getEventManager()->getSharedManager()->attach('Zend\Mvc\Controller\AbstractActionController', 'dispatch', function($e) {
        $controller      = $e->getTarget();
        $controllerClass = get_class($controller);
        $moduleNamespace = substr($controllerClass, 0, strpos($controllerClass, '\\'));
        $config          = $e->getApplication()->getServiceManager()->get('config');
        $controller->layout($config['module_layouts'][$moduleNamespace]);
        }, 100);
        
        //  Defined to get the controller and action name
        $e->getApplication()->getServiceManager()->get('translator');
        $e->getApplication()->getServiceManager()->get('viewhelpermanager')->setFactory('IsActive', function($sm) use ($e) {
            $isActive = new View\Helper\IsActive($e->getRouteMatch());
            return $isActive;
        });
        
    }

    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }

    public function getAutoloaderConfig()
    {
        return array(
            'Zend\Loader\StandardAutoloader' => array(
                'namespaces' => array(
                    __NAMESPACE__ => __DIR__ . '/src/' . __NAMESPACE__,
                ),
            ),
        );
    }
}
