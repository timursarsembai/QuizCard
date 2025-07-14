<?php
session_start();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (isset($input['language']) && in_array($input['language'], ['kk', 'ru', 'en'])) {
        $_SESSION['language'] = $input['language'];
        echo json_encode(['success' => true, 'language' => $input['language']]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid language']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
}
?>
