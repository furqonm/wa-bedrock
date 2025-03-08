<?php
require 'vendor/autoload.php';

use Aws\BedrockRuntime\BedrockRuntimeClient;
use Aws\Exception\AwsException;

// Set the content type to JSON
header('Content-Type: application/json');

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// AWS Bedrock Configuration (Using IAM Role in Beanstalk)
$awsConfig = [
    'region'  => 'us-east-1', // Adjust based on your AWS region
    'version' => 'latest'
];

// Initialize Bedrock Client
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
    $inputMessage = $inputData['message'] ?? "Hello, this is a test message!";

    // Set Guardrail and Knowledge Base IDs (Replace with actual values)
    $guardrailId = ""; // Add your Bedrock Guardrail ID here
    $knowledgeBaseId = ""; // Add your Knowledge Base ID here

    // Prepare the request body for Amazon Nova
    $body = [
        "inferenceConfig" => [
            "max_new_tokens" => 1000
        ],
        "messages" => [
            [
                "role" => "user",
                "content" => [
                    [
                        "text" => $inputMessage
                    ]
                ]
            ]
        ]
    ];

    // Check if Guardrails and Knowledge Base should be included
    if (!empty($guardrailId)) {
        $body["guardrailId"] = $guardrailId;
    }

    if (!empty($knowledgeBaseId)) {
        $body["knowledgeBaseId"] = $knowledgeBaseId;
    }

    try {
        // Call Amazon Bedrock (Amazon Nova model)
        $result = $client->invokeModel([
            'modelId' => 'amazon.nova-micro-v1:0', // Amazon Nova Model
            'contentType' => 'application/json',
            'accept' => 'application/json',
            'body' => json_encode($body)
        ]);

        // Decode Bedrock response
        $responseBody = $result['body']->getContents();
        $bedrockResponse = json_decode($responseBody, true);

        // Log full response for debugging
        error_log("Bedrock Response: " . json_encode($bedrockResponse));

        // Extract response content safely
        if (!empty($bedrockResponse['messages'][0]['content'][0]['text'])) {
            $replyMessage = $bedrockResponse['messages'][0]['content'][0]['text'];
        } else {
            $replyMessage = "No valid response from Amazon Nova.";
        }

    } catch (AwsException $e) {
        // Handle errors and log them
        error_log("Error calling Amazon Bedrock: " . $e->getMessage());
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
