<?php

// Formee/View/Helper/ControllerName.php

namespace RemotiiAdministrator\View\Helper;

use Zend\View\Helper\AbstractHelper;

class IsActive extends AbstractHelper {

    protected $routeMatch;
    protected $rbac;

    public function __construct($routeMatch) {
        $this->routeMatch = $routeMatch;
    }

    public function __invoke($params) {
        if ($this->routeMatch) {
            $controller = $this->routeMatch->getParam('controller', 'index');
            $action = $this->routeMatch->getParam('action', 'index');
        }

        $active = array();
        foreach ($params as $navRoots => $controllerActions) {
            foreach ($controllerActions as $c => $a) {
                if ($c == $controller) {
                    if (in_array($action, $a)) {
                        $active[$navRoots] = true;
                        $active[$navRoots . "_" . $action] = true;
                    }
                }
                if (!empty($active)) {
                    break;
                }
            }
            if (!empty($active)) {
                break;
            }
        }
        return $active;
    }

}