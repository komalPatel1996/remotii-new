<?php

namespace RemotiiDefault\Form;

use Zend\Form\Element;
use ZfcBase\Form\ProvidesEventsForm;
use GoalioForgotPassword\Options\ForgotOptionsInterface;
use GoalioForgotPassword\Form\Forgot as GoalioForgotPasswordForm;

class Forgot extends GoalioForgotPasswordForm {

    /**
     * @var AuthenticationOptionsInterface
     */
    protected $forgotOptions;

    public function __construct($name = null, ForgotOptionsInterface $options) {
        parent::__construct($name, $options);

        $this->add(array(
            'name' => 'username',
            'options' => array(
                'label' => 'Username',
            ),
        ));
    }

}
