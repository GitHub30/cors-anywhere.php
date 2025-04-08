<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: *');
header('Access-Control-Allow-Headers: *');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Expose-Headers: *');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

$url = ltrim($_SERVER['REQUEST_URI'], '/');

if (!filter_var($url, FILTER_VALIDATE_URL)) {
    header('HTTP/1.1 400 Bad Request');
    exit;
}

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_HEADER, true);

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    $body = file_get_contents('php://input');
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $_SERVER['REQUEST_METHOD']);
    if ($body !== false && strlen($body) > 0) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    }
}

$headers = [];
foreach (getallheaders() as $name => $value) {
    if (in_array(strtolower($name), ['host', 'content-length'])) {
        continue;
    }
    $headers[] = "$name: $value";
}
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

$response = curl_exec($ch);

if (curl_errno($ch)) {
    header('HTTP/1.1 500 Internal Server Error');
    echo curl_error($ch);
    curl_close($ch);
    exit;
}

$http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
curl_close($ch);

$headers = explode("\r\n", substr($response, 0, $header_size));
foreach ($headers as $header) {
    $header = preg_replace("/^HTTP\/\S+/", $_SERVER['SERVER_PROTOCOL'], $header);
    header($header, false);
}

echo substr($response, $header_size);
