<?php
/**
 * A simple PHP proxy script.
 *
 * SECURITY WARNING: This is a simple proxy. An open proxy is a security risk.
 * This script uses a whitelist to restrict access to specific domains.
 * Do NOT deploy this on a public server without fully understanding the security implications.
 *
 * How to use:
 * Call this script with a `url` parameter.
 * Example: http://localhost/onlinebizpermit/simple_proxy.php?url=https://api.example.com/data.json
 */

// --- SECURITY: Define a whitelist of allowed domains ---
// This is the most important security measure. Only URLs from these domains will be proxied.
$allowed_domains = [
    'api.example.com',
    'some-other-service.com',
    'www.php.net' // For testing
];

// 1. Get the target URL from the query string
$url = $_GET['url'] ?? null;

if (!$url) {
    http_response_code(400); // Bad Request
    die('ERROR: No URL provided.');
}

// 2. Validate the URL and check against the whitelist
$url_parts = parse_url($url);

if ($url_parts === false || !isset($url_parts['host'])) {
    http_response_code(400); // Bad Request
    die('ERROR: Invalid URL format.');
}

if (!in_array($url_parts['host'], $allowed_domains, true)) {
    http_response_code(403); // Forbidden
    die('ERROR: Access to this domain is not allowed.');
}

// 3. Initialize cURL to fetch the remote content
$ch = curl_init();

// Set cURL options
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Return the transfer as a string
curl_setopt($ch, CURLOPT_HEADER, true);         // We need to get the headers
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // Follow redirects
curl_setopt($ch, CURLOPT_USERAGENT, 'SimplePHPProxy/1.0'); // Set a user agent

// 4. Execute the cURL request
$response = curl_exec($ch);
$error = curl_error($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if ($error) {
    http_response_code(500); // Internal Server Error
    die("cURL Error: " . htmlspecialchars($error));
}

// 5. Separate headers and body from the response
$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
$header_string = substr($response, 0, $header_size);
$body = substr($response, $header_size);

// Close the cURL session
curl_close($ch);

// 6. Find and forward the Content-Type header
$headers = explode("\r\n", $header_string);
$content_type_found = false;
foreach ($headers as $header) {
    // Check for the Content-Type header (case-insensitive)
    if (stripos($header, 'Content-Type:') === 0) {
        header($header); // Forward the exact Content-Type header
        $content_type_found = true;
        break;
    }
}

// If no Content-Type is found, default to plain text to be safe
if (!$content_type_found) {
    header('Content-Type: text/plain');
}

// 7. Output the body of the remote page
echo $body;

?>