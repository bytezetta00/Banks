<?php
$rules = array(
    'account' => array(
        'required' => true,
        'type' => 'text',
        'field' => 'textbox',
        'title' => 'شماره حساب بانک پارسیان',
    ),
    'username' => array(
        'required' => true,
        'type' => 'text',
        'field' => 'textbox',
        'title' => 'نام کاربری',
        'description' => 'نام کاربری نت بانک بانک پارسیان',
    ),
    'password' => array(
        'required' => true,
        'type' => 'text',
        'field' => 'textbox',
        'title' => 'کلمه عبور',
        'description' => 'کلمه عبور نت بانک بانک پارسیان',
    ),
    'secondPass' => array(
        'required' => false,
        'type' => 'text',
        'field' => 'textbox',
        'title' => 'رمز دوم',
        'description' => 'رمز دوم انتقال پایا بانک پارسیان',
    ),
);