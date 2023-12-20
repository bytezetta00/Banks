<?php
// inja ham ye seri variable tarif mikonim ke asli tarinesh $rules hastesh ke baraye afzudane hesab tu panel karbord dare
$rules = array(
    'account' => array(
        'required' => true,
        'type' => 'text',
        'field' => 'textbox',
        'title' => 'شماره حساب بانک سمپل',
    ),
    'username' => array(
        'required' => true,
        'type' => 'text',
        'field' => 'textbox',
        'title' => 'نام کاربری',
        'description' => 'نام کاربری نت بانک بانک سمپل',
    ),
    'password' => array(
        'required' => true,
        'type' => 'text',
        'field' => 'textbox',
        'title' => 'کلمه عبور',
        'description' => 'کلمه عبور نت بانک بانک سمپل',
    ),
);
