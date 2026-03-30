<?php
/**
 * Terabox Stream Proxy
 * Bypasses User-Agent and Referrer restrictions by proxying the stream through the server.
 * Supports Range requests for seeking.
 */

// Basic Security - could be enhanced with tokens
$dlink = $_GET['url'] ?? '';
if (empty($dlink)) die("No URL provided");

// Terabox dlinks often require a specific User-Agent
$ua = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36';

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $dlink);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, false); // We use WRITEFUNCTION
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_USERAGENT, $ua);
curl_setopt($ch, CURLOPT_REFERER, 'https://www.terabox.com/');

// Forward Range Header for seeking
if (isset($_SERVER['HTTP_RANGE'])) {
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Range: ' . $_SERVER['HTTP_RANGE']]);
}

// Handle Headers from Terabox
curl_setopt($ch, CURLOPT_HEADERFUNCTION, function($ch, $header) {
    $len = strlen($header);
    $header_lower = strtolower($header);
    
    // Forward important headers to the browser
    if (strpos($header_lower, 'content-type:') === 0 || 
        strpos($header_lower, 'content-length:') === 0 || 
        strpos($header_lower, 'content-range:') === 0 || 
        strpos($header_lower, 'content-disposition:') === 0 || 
        strpos($header_lower, 'accept-ranges:') === 0) {
        header($header);
    }
    return $len;
});

// Stream the data
curl_exec($ch);
curl_close($ch);
