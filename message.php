<?php
require 'vendor/autoload.php';

// Set the content type to JSON
header('Content-Type: application/json');

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the raw POST data
    $inputJson = file_get_contents('php://input');

    // Decode the JSON input
    $inputData = json_decode($inputJson, true);

    // If input is empty, use sample data
    if (empty($inputData)) {
        $inputData = [
            "app" => "WhatsApp",
            "sender" => "John Doe",
            "message" => "Hello, this is a test message!",
            "group_name" => "Test Group",
            "phone" => "+1234567890"
        ];
    }

    // Extract fields from the input data
    $app = $inputData['app'] ?? 'Unknown App';
    $sender = $inputData['sender'] ?? 'Unknown Sender';
    $message = $inputData['message'] ?? 'No message provided';
    $groupName = $inputData['group_name'] ?? 'No group name provided';
    $phone = $inputData['phone'] ?? 'No phone number provided';

    // Generate a response message
    $replyMessage = "Hello $sender, your message '$message' from $app in the group '$groupName' has been received.";

    // Construct the response JSON
    $response = [
        "reply" => $replyMessage
    ];

    // Output the response as JSON
    echo json_encode($response);
} else {
    // Handle non-POST requests
    http_response_code(405); // Method Not Allowed
    echo json_encode(["error" => "Only POST requests are allowed"]);
}