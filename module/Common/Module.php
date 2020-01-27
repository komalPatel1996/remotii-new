<?php
namespace Common;

class Module {

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
    
    public function onBootstrap()
    {
    	 
    }
    
    public function getServiceConfig()
    {
    	return array(
    			'invokables' => array(
    					'common_session_service'=> 'Common\Service\Session',
    			),
    	);
    }
    
    public function getViewHelperConfig() {
    	return array(
    			'factories' => array(
    				'session' => function ($serviceManager) {
    					// Get the service locator
    					$serviceLocator = $serviceManager->getServiceLocator();
    					return new \Common\View\Helper\Session($serviceLocator);
    				},
    			)
    	);
    }
}

