<?php
// api/helpers/response.php

function json_response($data, $status_code = 200) {
    http_response_code($status_code);
    echo json_encode([
        'success' => true,
        'data' => $data,
        'message' => 'OK'
    ]);
    exit;
}

function success_response($message, $data = null, $status_code = 200) {
    http_response_code($status_code);
    $response = ['success' => true, 'message' => $message];
    if ($data !== null) {
        $response['data'] = $data;
    }
    echo json_encode($response);
    exit;
}

function error_response($message, $status_code = 400) {
    http_response_code($status_code);
    echo json_encode([
        'success' => false,
        'message' => $message
    ]);
    exit;
}

function get_json_input() {
    $input = json_decode(file_get_contents('php://input'), true);
    if ($input === null && json_last_error() !== JSON_ERROR_NONE) {
        error_response('Invalid JSON input', 400);
    }
    return $input ?? [];
}
