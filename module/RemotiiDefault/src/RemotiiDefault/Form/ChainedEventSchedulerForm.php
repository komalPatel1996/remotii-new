<?php

namespace RemotiiDefault\Form;

use Zend\Form\Form;
//use Zend\Form\Element;

class ChainedEventSchedulerForm extends Form {

    protected $remotii_id;
    protected $mac_address;

    public function __construct($name=null) {
        parent::__construct('chainedeventscheduler');

        // Setting post method for this form
        $this->setAttribute('method', 'post');
        // Adding Hidden element to the form for ID
        $this->add(array(
            'name' => 'id',
            'attributes' => array(
                'type' => 'hidden',
            ),
        ));
        $this->add(array(
            'type' => 'Zend\Form\Element\Checkbox',
            'name' => 'outputname[]',
            'options' => array(
//                     'label' => 'Output name1', 
                'use_hidden_element' => false,
                'checked_value' => '1',
                'unchecked_value' => '0',
            ),
            'attributes' => array(
                'class' => 'check outputname',
                'id' => 'check1',
            )
        ));

        $this->add(array(
            'type' => 'Zend\Form\Element\Checkbox',
            'name' => 'inputname[]',
            'options' => array(
                'use_hidden_element' => false,
//                     'label' => 'Input Name1',
                'checked_value' => '1',
                'unchecked_value' => '0',
            ),
            'attributes' => array(
                'class' => 'check inputname',
            )
        ));


        $this->add(array(
            'name' => 'save',
            'attributes' => array(
                'type' => 'submit',
                'value' => 'Save',
                'class' => 'btn3 save',
            ),
        ));
        $this->add(array(
            'name' => 'cancel',
            'attributes' => array(
                'type' => 'reset',
                'value' => 'Cancel',
                'class' => 'btn2 ',
            ),
        ));
    }

}

