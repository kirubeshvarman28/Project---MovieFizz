<?php
/**
 * Terabox Resolver Class
 * Handles extraction of direct download/stream links from Terabox share URLs.
 */
class TeraboxResolver {
    private static $ua = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36';

    /**
     * Resolve a Terabox share URL to a direct streamable link.
     */
    public static function resolve($url) {
        $surl = self::extractSurl($url);
        if (!$surl) return false;

        // Try the new robust V3 resolution first
        $v3Result = self::resolveV3($surl);
        if ($v3Result) return $v3Result;

        // Try Apify next if token is provided
        $apifyResult = self::resolveViaApify($url);

        // Try Bridge APIs first
        $bridges = [
            "https://terabox-dl.qtcloud.workers.dev/api/get-info?url=" . urlencode($url),
            "https://www.1024terabox.com/share/list?shorturl=$surl&root=1&clienttype=0&web=1&app_id=250528"
        ];

        foreach ($bridges as $bridgeUrl) {
            $response = self::fetch($bridgeUrl);
            if (isset($response['success']) && $response['success'] == true && isset($response['file_info'])) {
                $info = $response['file_info'];
                return [
                    'stream' => $info['stream_url'] ?? false,
                    'download' => $info['direct_download'] ?? $info['download_url'] ?? false,
                    'name' => $info['file_name'] ?? 'video.mp4'
                ];
            }
            if (isset($response['errno']) && $response['errno'] == 0 && isset($response['list'][0])) {
                $file = $response['list'][0];
                return ['stream' => $file['dlink'], 'download' => $file['dlink'], 'name' => $file['server_filename']];
            }
        }

        // Secondary Fallback: Scrape INITIAL_STATE directly 
        $html = self::fetchRaw($url);
        if ($html && preg_match('/window\.__INITIAL_STATE__\s*=\s*({.*?});/s', $html, $matches)) {
            $state = json_decode($matches[1], true);
            $fileList = $state['shareInfo']['fileList'] ?? $state['share']['fileList'] ?? null;
            if ($fileList && isset($fileList[0])) {
                $file = $fileList[0];
                return [
                    'stream' => $file['dlink'] ?? false,
                    'download' => $file['dlink'] ?? false,
                    'name' => $file['server_filename'] ?? 'video.mp4'
                ];
            }
        }

        return false;
    }

    /**
     * Resolve via Apify Actor (Reliable but requires token)
     */
    public static function resolveViaApify($url) {
        if (!defined('APIFY_TOKEN') || APIFY_TOKEN === 'YOUR_APIFY_TOKEN_HERE' || empty(APIFY_TOKEN)) {
            return false;
        }

        $input = [
            "links" => [$url],
            "proxyConfiguration" => [
                "useApifyProxy" => true,
                "apifyProxyGroups" => ["RESIDENTIAL"]
            ]
        ];

        $apiUrl = "https://api.apify.com/v2/acts/igview-owner~terabox-fast-video-downloader/run-sync-get-dataset-items?token=" . APIFY_TOKEN;
        
        $ch = curl_init($apiUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($input));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60); 
        $response = curl_exec($ch);
        curl_close($ch);

        $items = json_decode($response, true);
        if ($items && isset($items[0]['file_info'])) {
            $info = $items[0]['file_info'];
            return [
                'stream' => $info['stream_url'] ?? false,
                'download' => $info['direct_download'] ?? $info['download_url'] ?? false,
                'name' => $info['file_name'] ?? 'video.mp4'
            ];
        }
        return false;
    }

    /**
     * Fetch raw HTML with browser-like headers.
     */
    private static function fetchRaw($url) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_ENCODING, "");
        curl_setopt($ch, CURLOPT_USERAGENT, self::$ua);
        curl_setopt($ch, CURLOPT_REFERER, 'https://www.terabox.com/');
        $res = curl_exec($ch);
        curl_close($ch);
        return $res;
    }

    /**
     * Extract surl from various Terabox link formats.
     */
    private static function extractSurl($url) {
        if (preg_match('/surl=([^&]+)/', $url, $matches)) {
            return $matches[1];
        }
        if (preg_match('/\/s\/([a-zA-Z0-9_-]+)/', $url, $matches)) {
            return $matches[1];
        }
        return false;
    }

    /**
     * Get metadata (streams) from a direct URL using ffprobe.
     */
    public static function getMetadata($directUrl) {
        $ffprobe_cmd = 'ffprobe';
        $local_ffprobe = realpath(__DIR__ . '/../ffmpeg_bin/ffprobe.exe');
        if ($local_ffprobe && file_exists($local_ffprobe)) $ffprobe_cmd = escapeshellcmd($local_ffprobe);

        $cmd = "$ffprobe_cmd -v error -show_entries stream=index,codec_type,codec_name:stream_tags=language,title -of json " . escapeshellarg($directUrl);
        $output = shell_exec($cmd);
        return json_decode($output, true);
    }

    /**
     * Get the FFmpeg/FFprobe command path.
     */
    public static function getBin($type = 'ffmpeg') {
        $bin = $type;
        $local_bin = realpath(__DIR__ . "/../ffmpeg_bin/$type.exe");
        if ($local_bin && file_exists($local_bin)) $bin = escapeshellcmd($local_bin);
        return $bin;
    }

    /**
     * Resolve via Terabox API V3 methodology (Most robust)
     */
    public static function resolveV3($surl) {
        try {
            // 1. Fetch Sign and Timestamp from Worker API
            $signData = self::fetch("https://terabox.hnn.workers.dev/api/get-info?shorturl=$surl");
            if (!$signData || !isset($signData['sign'])) return false;

            // 2. Fetch File Metadata from Terabox official API
            $metaUrl = "https://www.terabox.com/api/shorturlinfo?app_id=250528&shorturl=$surl&root=1";
            $metadata = self::fetch($metaUrl);
            
            if (!$metadata || !isset($metadata['list'][0])) return false;
            $file = $metadata['list'][0];

            // 3. Request Direct Download Link via Worker (POST)
            $payload = [
                "shareid" => $metadata['shareid'],
                "uk" => $metadata['uk'],
                "sign" => $signData['sign'],
                "timestamp" => $signData['timestamp'],
                "fs_id" => $file['fs_id']
            ];

            $dlData = self::fetchPost("https://terabox.hnn.workers.dev/api/get-download", $payload);
            
            if ($dlData && isset($dlData['downloadLink'])) {
                return [
                    'stream' => $dlData['downloadLink'],
                    'download' => $dlData['downloadLink'],
                    'name' => $file['server_filename'] ?? 'video.mp4'
                ];
            }
        } catch (Exception $e) {
            error_log("Terabox V3 Error: " . $e->getMessage());
        }
        return false;
    }

    /**
     * Helper to fetch JSON from URL with comprehensive headers (GET).
     */
    private static function fetch($url) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "User-Agent: " . self::$ua,
            'Accept: application/json, text/plain, */*',
            'Referer: https://www.terabox.com/',
            'Origin: https://www.terabox.com',
            'Accept-Language: en-US,en;q=0.9'
        ]);
        
        $response = curl_exec($ch);
        curl_close($ch);
        return json_decode($response, true);
    }

    /**
     * Helper to fetch JSON via POST.
     */
    private static function fetchPost($url, $data) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "User-Agent: " . self::$ua,
            'Content-Type: application/json',
            'Accept: application/json',
            'Referer: https://www.terabox.com/'
        ]);
        
        $response = curl_exec($ch);
        curl_close($ch);
        return json_decode($response, true);
    }
}
