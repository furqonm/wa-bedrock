<?php
require 'vendor/autoload.php';

use Aws\BedrockRuntime\BedrockRuntimeClient;
use Aws\Exception\AwsException;

// Set the content type to JSON
header('Content-Type: application/json');

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// AWS Bedrock Configuration (Menggunakan IAM Role otomatis)
$awsConfig = [
    'region'  => 'us-east-1', // Sesuaikan dengan region Bedrock Anda
    'version' => 'latest'
];

// Initialize Bedrock Client (IAM Role Otomatis)
$client = new BedrockRuntimeClient($awsConfig);

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the raw POST data
    $inputJson = file_get_contents('php://input');

    // Decode the JSON input
    $inputData = json_decode($inputJson, true);

    // If JSON decoding fails or is empty, fallback to $_POST
    if (json_last_error() !== JSON_ERROR_NONE || empty($inputData)) {
        $inputData = $_POST;
    }

    // Ensure 'message' has a default value if missing
    $inputData['message'] = $inputData['message'] ?? "Hello, this is a test message!";

    // Generate prompt from input
    $prompt = $inputData['message'];

    try {
        // Call Amazon Bedrock with Guardrails and Knowledge Base
        $result = $client->invokeModel([
            'modelId' => 'anthropic.claude-3-5-haiku-20241022-v1:0', // Sesuaikan dengan model Bedrock
            'contentType' => 'application/json',
            'accept' => 'application/json',
            'body' => json_encode([
                'prompt' => $prompt,
                'max_tokens' => 200,
                'temperature' => 0.5 // Kontrol kreativitas respons
                // 'guardrailId' => 'your-guardrail-id', // Gunakan Guardrail yang sudah dibuat
                // 'knowledgeBaseId' => 'your-knowledge-base-id' // Gunakan Knowledge Base internal
            ])
        ]);

        // Decode Bedrock response
        $bedrockResponse = json_decode($result['body']->getContents(), true);
        $replyMessage = $bedrockResponse['completion'] ?? "No response from Bedrock";

    } catch (AwsException $e) {
        // Handle errors
        $replyMessage = "Error calling Amazon Bedrock: " . $e->getMessage();
    }

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