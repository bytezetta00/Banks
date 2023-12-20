<?php

$token = '238T40AmVCJpZO78P1R1SIMfdCDqa0fHt6AeWhsAFzKcvGWqZEcjCsc6B6LcJEXR';

// check token in every request
if (!isset($_GET['token']) || $_GET['token'] !== $token) {
    header('HTTP/1.0 401 Unauthorized');
    echo "cHTTP/1.0 401 Unauthorized";
    exit();
}

// verification request
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    echo "verification request";
    exit($_GET['challenge']);
}

// access the body of a POST request
$data = json_decode(file_get_contents('php://input'));

header('Content-Type: application/json');

$response = array(
    // return custom attributes object
    'attributes' => array(
        'name' => 'John',
        'surname' => 'Example'
    ),
    // return responses
    'responses' => array(
        array(
            'type' => 'text',
            'message' => array('Message from webhook')
        )
    )
);

echo json_encode($response);

?>