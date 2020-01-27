<?php

namespace RemotiiDefault\Form;

use Zend\InputFilter\InputFilter;
use GoalioForgotPassword\Options\ForgotOptionsInterface;

class ForgotFilter extends InputFilter {

    /**
     * @var ForgotOptionsInterface
     */
    protected $options;

    public function __construct($emailValidator, ForgotOptionsInterface $options) {
        $this->setOptions($options);
        $this->emailValidator = $emailValidator;

        $this->add(array(
            'name' => 'email',
            'required' => true,
//            'validators' => array(
//                array(
//                    'name' => 'EmailAddress'
//                )
//            ),
        ));

        $this->add(array(
            'name' => 'email',
            'validators' => array(
                array(
                    'name' => 'NotEmpty',
                    'options' => array(
                        'messages' => array(
                            \Zend\Validator\NotEmpty::IS_EMPTY => 'Please fill the Email Address',
                        ),
                    ), 'break_chain_on_failure' => true
                ),
                array(
                    'name' => 'Regex',
                    'options' => array(
                        'pattern' => "/^[a-zA-Z0-9.!#$%&'*+\/=?^_`{|}~-]+@[a-zA-Z0-9-]+(?:\.[a-zA-Z0-9-]+)*$/",
                        'messages' => array(
//                                    \Zend\Validator\Regex::ERROROUS => 'Please Enter a Valid Email Address',
//                                    \Zend\Validator\Regex::INVALID => 'Please Enter a Valid Email Address',
                            \Zend\Validator\Regex::NOT_MATCH => 'Please Enter a Valid Email Address',
                        ),
                    ), 'break_chain_on_failure' => true
                ),
            ),
        ));
//        if ($this->options->getValidateExistingRecord()) {
//            $this->add(array(
//                'name' => 'email',
//                'validators' => array(
//                    $this->emailValidator
//                ),
//            ));
//        }
    }

    /**
     * set options
     *
     * @param RegistrationOptionsInterface $options
     */
    public function setOptions(ForgotOptionsInterface $options) {
        $this->options = $options;
    }

    /**
     * get options
     *
     * @return RegistrationOptionsInterface
     */
    public function getOptions() {
        return $this->options;
    }

}
