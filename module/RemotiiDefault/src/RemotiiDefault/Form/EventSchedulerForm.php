<?php

namespace RemotiiDefault\Form;

use Zend\Form\Form;
use Zend\Form\Element;

class EventSchedulerForm extends Form {

    protected $remotii_id;
    protected $mac_address;
    protected $output_bits;
    protected $input_bits;
    protected $dout_set;
    protected $dout_clr;
    protected $time;
    protected $type;
    protected $date;
    protected $days;
    protected $month;
    protected $input_type;
    protected $input_cond;
    protected $datepicker;

    public function __construct() {

        parent::__construct('eventscheduler');

        $this->setAttribute('method', 'post');
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
                'class' => 'check',
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
                'class' => 'check',
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

