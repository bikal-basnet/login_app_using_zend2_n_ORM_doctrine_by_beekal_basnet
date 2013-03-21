<?php
/// <summary>
///  This file contains sign up form definations 
/// </summary>
/// Created : 2013 March 18, Bikal Basnet

namespace User\Form;

use Zend\Form\Form;

class UserSignUpForm extends Form
{
    public function __construct($name = null)
    {
        // we want to ignore the name passed
        parent::__construct('user_sign_up');
        $this->setAttribute('method', 'post');
		$this->setAttribute('id', 'formid');       
        $this->add(array(
            'name' => 'email',
            'attributes' => array(
				'type'  => 'text',
				'id'	=> 'email',							
            ),
            'options' => array(
                'label' => 'Email',
				
            ),
        ));
        $this->add(array(
            'name' => 'password',
            'attributes' => array(
                'type'  => 'password',
				'id'	=> 'password',
            ),
            'options' => array(
                'label' => 'Password',
            ),
        ));
       $this->add(array(
            'name' => 'confirm_password',
            'attributes' => array(
                'type'  => 'password',
				'id'	=> 'confirm_password',
            ),
            'options' => array(
                'label' => 'Confirm Password',
            ),
        ));
 
        $this->add(array(
            'name' => 'submit',
            'attributes' => array(
                'type'  => 'submit',
                'value' => 'Go',
                'id' => 'submitbutton',
            ),
        ));
    }
}
?>