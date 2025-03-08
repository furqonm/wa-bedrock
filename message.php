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

    try {
        // Prepare the request body for Amazon Nova with Guardrail & Knowledge Base
        $body = [
            "inferenceConfig" => [
                "max_new_tokens" => 1000 // Set max token limit
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
            ],
            // "guardrailId" => $guardrailId, // Attach Guardrail for safe responses
            // "knowledgeBaseId" => $knowledgeBaseId // Use Knowledge Base for domain-specific answers
        ];

        // Call Amazon Bedrock (Amazon Nova model)
        $result = $client->invokeModel([
            'modelId' => 'amazon.nova-micro-v1:0', // Amazon Nova Model
            'contentType' => 'application/json',
            'accept' => 'application/json',
            'body' => json_encode($body)
        ]);

        // Decode Bedrock response
        $bedrockResponse = json_decode($result['body']->getContents(), true);
        
        // Extract response content
        $replyMessage = $bedrockResponse['messages'][0]['content'][0]['text'] ?? "No response from Amazon Nova";

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
