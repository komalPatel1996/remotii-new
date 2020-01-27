<?php

/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2013 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace RemotiiDefault;

use Zend\Mvc\ModuleRouteListener;
use Zend\Mvc\MvcEvent;

class Module {

    public function onBootstrap(MvcEvent $e) {
        $eventManager = $e->getApplication()->getEventManager();
        $sharedManager = $eventManager->getSharedManager();
        $moduleRouteListener = new ModuleRouteListener();
        $moduleRouteListener->attach($eventManager);

        //  function is used to render the template Module wise
        $sharedManager->attach('Zend\Mvc\Controller\AbstractActionController', 'dispatch', function($e) {
                    if (isset($_SESSION['old_captcha']) && !empty($_SESSION['old_captcha'])) {
                        @unlink(BASE_PATH . '/images/captcha/' . $_SESSION['old_captcha']);
                        $_SESSION['old_captcha'] = '';
                    }
                    $controller = $e->getTarget();
                    $controllerClass = get_class($controller);
                    $moduleNamespace = substr($controllerClass, 0, strpos($controllerClass, '\\'));
                    $config = $e->getApplication()->getServiceManager()->get('config');
                    $controller->layout($config['module_layouts'][$moduleNamespace]);
                }, 100);

        //  getting the current route
        $uriRoute = ltrim($_SERVER["REQUEST_URI"], '/');

        $explodeURL = explode('/', $uriRoute);
        $route = $explodeURL[0];
        $cronChkAction = $explodeURL[2];

        /* H.O.W.D.Y. Media - 11/14/2019 */
        file_put_contents('/var/www/html/php72/remotii/module/RemotiiDefault/remotiidefault_module.log',date('Y-m-d H:i:s').': '.$_SERVER["REQUEST_URI"].', '.$route.', 1: '.$explodeURL[1].', 2: '.$cronChkAction."\n",FILE_APPEND);


        /* End HM */

        //$route = $explodeURL[1];        //  STATIC USED FOR REMOTI SERVER FOR LIVE UNCOMMENT THIS
        //$cronChkAction = $explodeURL[3];
        //  Current route END

        if ($cronChkAction == 'cron-bill-service-providers' || $cronChkAction == 'cron-offline-notification' ||
          $cronChkAction == 'cron-notification-mail' || $cronChkAction == 'cron-rmib-data' || $cronChkAction == 'cron-rmob-data' ||
          $cronChkAction == 'cron-remotii-event' || $cronChkAction == 'cron-rm-event-cron-data') {
            return true;
        }

        //  Get the user role
        $sm = $e->getApplication()->getServiceManager();
        $auth = $sm->get('zfcuser_auth_service');

        $cmnSessionService = $sm->get('common_session_service');
        /**
         * 20 minutes session timeout for admin
         */
        $cmnSessionService->setTimeout(0);

        if (in_array($route, array(
                    '',
                    'about',
                    'about-us',
                    'home',
                    'about-remotii',
                    'features',
                    'contact',
                    'careers',
                    'where-to-buy',
                    'remotii-uses',
                    'getting-started',
                    'terms-and-conditions'))) {
            return;
        }

        if ($auth->hasIdentity()) {
            $userInfo = $auth->getIdentity();
            //_pre($userInfo);
            if (!empty($userInfo)) {
                $userRoleId = $userInfo->getUserRoleId();
            } else {
                $auth->clearIdentity();
            }
        } else {
            if ($e->getRequest()->isXmlHttpRequest()) {
                echo json_encode(array('session_expired' => '1'));
                die();
            }
            if ($route == 'admin' || $route == 'sp' || $route == 'client') {
                $sharedManager->attach('Zend\Mvc\Controller\AbstractActionController', 'dispatch', function($e) {
                            $controller = $e->getTarget();
                            return $controller->plugin('redirect')->toRoute('login');
                        }, 100);
                // Redirect End
            }
        }

        if ($userRoleId == 1) {

            /**
             * 20 minutes session timeout for admin
             */
            $cmnSessionService->setTimeout(ADMIN_SESSION_TIMEOUT * 60);

            //  admin role
            if ($route != 'admin') {
                $sharedManager->attach('Zend\Mvc\Controller\AbstractActionController', 'dispatch', function($e) {
                            $controller = $e->getTarget();
                            $controller->plugin('redirect')->toRoute('remotii-administrator');
                        }, 100);
                // Redirect End
            }
        }
        if ($userRoleId == 2) {
            /**
             * 10 minutes session timeout for service provider
             */
            $cmnSessionService->setTimeout(SP_SESSION_TIMEOUT * 60);
            //  SP
            if ($route != 'sp') {
                $sharedManager->attach('Zend\Mvc\Controller\AbstractActionController', 'dispatch', function($e) {
                            $controller = $e->getTarget();
                            $controller->plugin('redirect')->toRoute('remotii-service-provider');
                        }, 100);
                // Redirect End
            }
        }
        if ($userRoleId == 3) {
            /**
             * 20 minutes session timeout for service provider
             */
            $cmnSessionService->setTimeout(EU_SESSION_TIMEOUT * 60);
            // client role
            if ($route != 'client') {
                $sharedManager->attach('Zend\Mvc\Controller\AbstractActionController', 'dispatch', function( \Zend\EventManager\Event $e) {
                            $controller = $e->getTarget();
                            $controller->plugin('redirect')->toRoute('redirect-remotii');
                        }, 100);
                // Redirect End
            }
        }

        $sharedManager->attach('Zend\Mvc\Controller\AbstractActionController', 'dispatch', function( \Zend\EventManager\Event $e) use($sm) {
                    $controller = $e->getTarget();
                    $action = $controller->params('action');
                    $request = $controller->getRequest();

                    if ($request->isPost() && $action == 'login') {
                        $post = $request->getPost();
                        $db = $sm->get('db');
                        $_modelUsers = new \RemotiiModels\Model\ManageUsers($db);
                        $accstatus = $_modelUsers->checkSPAccountStatus($post->identity);
                        if ($accstatus == SUSPENDED) {
                            header("location:" . BASE_URL . '/login?msg=as');
                            die();
                        }
                    }
                }, 100);
    }

    public function getConfig() {
        return include __DIR__ . '/config/module.config.php';
    }

    public function getAutoloaderConfig() {
        return array(
            'Zend\Loader\StandardAutoloader' => array(
                'namespaces' => array(
                    __NAMESPACE__ => __DIR__ . '/src/' . __NAMESPACE__,
                ),
            ),
        );
    }

    public function getServiceConfig() {
        return array(
            'factories' => array(
                'goalioforgotpassword_forgot_form' => function($sm) {
                    $options = $sm->get('goalioforgotpassword_module_options');
                    $form = new Form\Forgot(null, $options);
                    $form->setInputFilter(new Form\ForgotFilter(new \ZfcUser\Validator\RecordExists(array(
                        'mapper' => $sm->get('zfcuser_user_mapper'),
                        'key' => 'email'
                            )), $options));
                    return $form;
                },
            ),
        );
    }

}
