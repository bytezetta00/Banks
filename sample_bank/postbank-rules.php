<?php
$rules = array(
    'account' => array(
        'required' => true,
        'type' => 'text',
        'field' => 'textbox',
        'title' => 'شماره حساب پست بانک',
    ),
    'username' => array(
        'required' => true,
        'type' => 'text',
        'field' => 'textbox',
        'title' => 'نام کاربری',
        'description' => 'نام کاربری نت بانک پست بانک',
    ),
    'password' => array(
        'required' => true,
        'type' => 'text',
        'field' => 'textbox',
        'title' => 'کلمه عبور',
        'description' => 'کلمه عبور نت بانک پست بانک',
    ),
);
//$needs_custom_login = true;
