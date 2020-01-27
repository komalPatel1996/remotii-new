<?php

/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2013 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */
return array(
    'controllers' => array(
        'invokables' => array(
            'RemotiiDefault\Controller\Index' => 'RemotiiDefault\Controller\IndexController'
        ),
    ),
    'router' => array(
        'routes' => array(
            //  TMP for non logged in user -------------------------------------
            'home' => array(
                'type' => 'Zend\Mvc\Router\Http\Literal',
                'options' => array(
                    'route' => '/',
                    'defaults' => array(
                        'controller' => 'RemotiiDefault\Controller\Index',
                        'action' => 'index',
                    ),
                ),
            ),
            'about-remotii' => array(
                'type' => 'Zend\Mvc\Router\Http\Literal',
                'options' => array(
                    'route' => '/about-remotii',
                    'defaults' => array(
                        'controller' => 'RemotiiDefault\Controller\Index',
                        'action' => 'aboutRemotii',
                    ),
                ),
            ),
            'about' => array(
                'type' => 'Zend\Mvc\Router\Http\Literal',
                'options' => array(
                    'route' => '/about',
                    'defaults' => array(
                        'controller' => 'RemotiiDefault\Controller\Index',
                        'action' => 'about',
                    ),
                ),
            ),
            'features' => array(
                'type' => 'Zend\Mvc\Router\Http\Literal',
                'options' => array(
                    'route' => '/features',
                    'defaults' => array(
                        'controller' => 'RemotiiDefault\Controller\Index',
                        'action' => 'features',
                    ),
                ),
            ),
            'contact' => array(
                'type' => 'Zend\Mvc\Router\Http\Literal',
                'options' => array(
                    'route' => '/contact',
                    'defaults' => array(
                        'controller' => 'RemotiiDefault\Controller\Index',
                        'action' => 'contact',
                    ),
                ),
            ),
            'careers' => array(
                'type' => 'Zend\Mvc\Router\Http\Literal',
                'options' => array(
                    'route' => '/careers',
                    'defaults' => array(
                        'controller' => 'RemotiiDefault\Controller\Index',
                        'action' => 'careers',
                    ),
                ),
            ),
            'wheretobuy' => array(
                'type' => 'Zend\Mvc\Router\Http\Literal',
                'options' => array(
                    'route' => '/where-to-buy',
                    'defaults' => array(
                        'controller' => 'RemotiiDefault\Controller\Index',
                        'action' => 'whereToBuy',
                    ),
                ),
            ),
            'terms-and-conditions' => array(
                'type' => 'Zend\Mvc\Router\Http\Literal',
                'options' => array(
                    'route' => '/terms-and-conditions',
                    'defaults' => array(
                        'controller' => 'RemotiiDefault\Controller\Index',
                        'action' => 'termsAndConditions',
                    ),
                ),
            ),
            'remotii-uses' => array(
                'type' => 'Zend\Mvc\Router\Http\Literal',
                'options' => array(
                    'route' => '/remotii-uses',
                    'defaults' => array(
                        'controller' => 'RemotiiDefault\Controller\Index',
                        'action' => 'remotiiUses',
                    ),
                ),
            ),
            'getting-started' => array(
                'type' => 'Zend\Mvc\Router\Http\Literal',
                'options' => array(
                    'route' => '/getting-started',
                    'defaults' => array(
                        'controller' => 'RemotiiDefault\Controller\Index',
                        'action' => 'gettingStarted',
                    ),
                ),
            ),
            //  TMP for non logged in user -------------------------------------
            // The following is a route to simplify getting started creating
            // new controllers and actions without needing to create a new
            // module. Simply drop new controllers in, and you can access them
            // using the path /application/:controller/:action
            'remotiifrontend' => array(
                'type' => 'Segment',
                'options' => array(
                    'route' => '/client[/:action][/:id]',
                    'defaults' => array(
                        '__NAMESPACE__' => 'RemotiiDefault\Controller',
                        'controller' => 'Index',
                        'action' => 'index',
                    ),
                ),
            ),
            'client-remotii' => array(
                'type' => 'Segment',
                'options' => array(
                    'route' => '/client/my-remotii[/:id]',
                    'defaults' => array(
                        '__NAMESPACE__' => 'RemotiiDefault\Controller',
                        'controller' => 'Index',
                        'action' => 'myRemotii',
                    ),
                ),
            ),
            'redirect-remotii' => array(
                'type' => 'Segment',
                'options' => array(
                    'route' => '/client/redirect-remotii[/:id]',
                    'defaults' => array(
                        '__NAMESPACE__' => 'RemotiiDefault\Controller',
                        'controller' => 'Index',
                        'action' => 'redirectRemotii',
                    ),
                ),
            ),
            'login' => array(
                'type' => 'Literal',
                'options' => array(
                    'route' => '/login',
                    'defaults' => array(
                        'controller' => 'zfcuser',
                        'action' => 'login',
                    ),
                ),
            ),
            'logout' => array(
                'type' => 'Literal',
                'options' => array(
                    'route' => '/logout',
                    'defaults' => array(
                        'controller' => 'zfcuser',
                        'action' => 'logout',
                    ),
                ),
            ),
            'register' => array(
                'type' => 'Literal',
                'options' => array(
                    'route' => '/register',
                    'defaults' => array(
                        'controller' => 'RemotiiDefault\Controller\Index',
                        'action' => 'register',
                    ),
                ),
            ),
            'register-rsp' => array(
                'type' => 'Literal',
                'options' => array(
                    'route' => '/register-rsp',
                    'defaults' => array(
                        'controller' => 'RemotiiDefault\Controller\Index',
                        'action' => 'registerRsp',
                    ),
                ),
            ),
            'info-rsp' => array(
                'type' => 'Zend\Mvc\Router\Http\Literal',
                'options' => array(
                    'route' => '/info-rsp',
                    'defaults' => array(
                        'controller' => 'RemotiiDefault\Controller\Index',
                        'action' => 'infoRsp',
                    ),
                ),
            ),
            'zfcuser' => array(
                'child_routes' => array(
                    'forgotpassword' => array(
                        'type' => 'Literal',
                        'options' => array(
                            'route' => '/forgot-password',
                            'defaults' => array(
                                'controller' => 'RemotiiDefault\Controller\Index',
                                'action' => 'forgot',
                            ),
                        ),
                    ),
                ),
            ),
            'my-remotii' => array(
                'type' => 'Zend\Mvc\Router\Http\Literal',
                'options' => array(
                    'route' => '/client/my-remotii',
                    'defaults' => array(
                        'controller' => 'RemotiiDefault\Controller\Index',
                        'action' => 'all-Remotii',
                    ),
                ),
            ),
        /* 'profile' => array(
          'type' => 'Literal',
          'options' => array(
          'route' => '/admin',
          'defaults' => array(
          'controller' => 'RemotiiAdministrator\Controller\Index',
          'action'     => 'index',
          ),
          ),
          ), */
        ),
    ),
    'service_manager' => array(
        'abstract_factories' => array(
            'Zend\Cache\Service\StorageCacheAbstractServiceFactory',
            'Zend\Log\LoggerAbstractServiceFactory',
        ),
        'aliases' => array(
            'translator' => 'MvcTranslator',
        ),
    ),
    'translator' => array(
        'locale' => 'en_US',
        'translation_file_patterns' => array(
            array(
                'type' => 'gettext',
                'base_dir' => __DIR__ . '/../language',
                'pattern' => '%s.mo',
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
            'tpl/email/spmail' => __DIR__ . '/../view/email/spmail.phtml',
            'tpl/email/spmail-text' => __DIR__ . '/../view/email/spmail-text.phtml',
            'tpl/email/offline-notification-mail' => __DIR__ . '/../view/email/offline-notification-mail.phtml',
            'tpl/email/offline-notification-text' => __DIR__ . '/../view/email/offline-notification-text.phtml',
            'tpl/email/sp_contact' => __DIR__ . '/../view/email/sp-contact.phtml',
            'tpl/email/contact_us_mail' => __DIR__ . '/../view/email/contactus-mail.phtml',
            'tpl/email/eupaymentmail' => __DIR__ . '/../view/email/eupaymentmail.phtml',
            'tpl/email/sppaymentmail' => __DIR__ . '/../view/email/sppaymentmail.phtml',
            'tpl/email/sppaybackmail' => __DIR__ . '/../view/email/sppaybackmail.phtml',
            'tpl/email/stripefailedmail' => __DIR__ . '/../view/email/stripefailedmail.phtml',
            'remotii-default/layout' => __DIR__ . '/../view/layout/layout.phtml',
            'remotii-default/index/index' => __DIR__ . '/../view/remotii-default/index/index.phtml',
            'tpl/email/remotii-delete-mail'=>__DIR__ .'/../view/email/remotii-delete-mail.phtml',
            'tpl/email/closed-loop-mail'=>__DIR__ .'/../view/email/closed-loop-mail.phtml',
            'tpl/email/remotii-delete-text'=>__DIR__ .'/../view/email/remotii-delete-text.phtml',
            'tpl/email/closed-loop-text'=>__DIR__ .'/../view/email/closed-loop-text.phtml',
            'error/404' => __DIR__ . '/../view/error/404.phtml',
            'error/index' => __DIR__ . '/../view/error/index.phtml',
            'zfc-user/user/login' => __DIR__ . '/../view/zfc-user/user/login.phtml',
            'zfc-user/user/login-widget' => __DIR__ . '/../view/zfc-user/user/login-widget.phtml',
        ),
        'template_path_stack' => array(
            'remotii-default' => __DIR__ . '/../view',
        ),
    ),
    'module_layouts' => array(
        'RemotiiDefault' => 'remotii-default/layout',
    ),
    // Placeholder for console routes
    'console' => array(
        'router' => array(
            'routes' => array(
            ),
        ),
    ),
);
