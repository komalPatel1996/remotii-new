<?php
return array(
	'controllers' => array(
			'invokables' => array(
					'Common\Controller\Session' => 'Common\Controller\SessionController',
			),
	),	
	'router' => array(
        'routes' => array(

            'session' => array(
            		'type' => 'Literal',
            		'options' => array(
            				'route' => '/sess2',
            				'defaults' => array(
            						'controller' => 'Common\Controller\Session',
            						'action'     => 'index',
            				),
            		),
            		'may_terminate' => true,
            		'child_routes' => array(
            				'authenticate' => array(
            						'type' => 'Literal',
            						'options' => array(
            								'route' => '/ex',
            								'defaults' => array(
            										'controller' => 'Common\Controller\Session',
            										'action'     => 'extendSession',
            								),
            						),
            				),
            		),
            ),
        ),
    ),
	'view_manager' => array(
			'display_not_found_reason' => true,
			'display_exceptions' => true,
			'doctype' => 'HTML5',
			'not_found_template' => 'error/404',
			'exception_template' => 'error/index',
			'template_map' => array(
					'layout/RemotiiFrontend' => __DIR__ . '/../view/layout/layout.phtml',
					'remotii-frontend/index/index' => __DIR__ . '/../view/remotii-frontend/index/index.phtml',
					'error/404' => __DIR__ . '/../view/error/404.phtml',
					'error/index' => __DIR__ . '/../view/error/index.phtml',
			),
			'template_path_stack' => array(
					'common' => __DIR__ . '/../view',
			),
	),
);
