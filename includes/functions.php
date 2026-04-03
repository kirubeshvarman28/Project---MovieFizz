<?php
// Common Functions
require_once INCLUDES_PATH . '/terabox_resolver.php';

// Redirect helper
function redirect($url) {
    header("Location: $url");
    exit();
}

// Clean input data
function clean_input($data) {
    return htmlspecialchars(stripslashes(trim($data)));
}

// Check if user is logged in
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

// Check if admin is logged in
function is_admin() {
    return (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin');
}

// Format rating
function format_rating($rating) {
    return number_format((float)$rating, 1, '.', '');
}

// Get setting from database
function get_setting($key, $default = '') {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT $key FROM settings WHERE id = 1");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result[$key] ?? $default;
    } catch (Exception $e) {
        return $default;
    }
}

// Get all settings from database
function get_all_settings() {
    global $pdo;
    try {
        $stmt = $pdo->query("SELECT * FROM settings WHERE id = 1");
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return [];
    }
}

// Check if maintenance mode is active
function is_maintenance_mode() {
    $mode = get_setting('maintenance_mode', 0);
    return ($mode == 1);
}

require_once INCLUDES_PATH . '/class.simple_smtp.php';

// Send Email Notification to Admin
function send_admin_notification($subject, $message, $from_email = '') {
    $settings = get_all_settings();
    $admin_email = $settings['email'] ?? '';
    if (empty($admin_email)) return false;

    $site_name = $settings['site_name'] ?? SITE_NAME;
    $site_url = SITE_URL;
    $host = parse_url($site_url, PHP_URL_HOST);

    $html_message = "
    <html>
    <body style='font-family: Arial, sans-serif; background: #000; color: #fff; padding: 20px;'>
        <div style='background: #141414; padding: 30px; border-radius: 12px; border: 1px solid #333;'>
            <h2 style='color: #E50914; margin-top: 0;'>$site_name - Notification</h2>
            <div style='color: #ccc; line-height: 1.6;'>
                $message
            </div>
            <hr style='border: 0; border-top: 1px solid #333; margin: 20px 0;'>
            <small style='color: #666;'>This is an automated message from $site_name.</small>
        </div>
    </body>
    </html>";

    $from_email = $from_email ?: ((!empty($settings['smtp_user']) && strpos($settings['smtp_user'], '@') !== false) ? $settings['smtp_user'] : ("noreply@" . ($host ?: 'moviefizz.com')));

    if (!empty($settings['smtp_host'])) {
        $mailer = new SimpleSMTP(
            $settings['smtp_host'], 
            $settings['smtp_port'], 
            $settings['smtp_user'], 
            $settings['smtp_pass'], 
            $settings['smtp_crypto']
        );
        return $mailer->send($admin_email, $site_name, $subject, $html_message, $from_email);
    }

    $headers = "From: " . $site_name . " <$from_email>\r\n";
    if (!empty($from_email)) $headers .= "Reply-To: $from_email\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    
    return @mail($admin_email, $subject, $html_message, $headers);
}

// Send general email to user
function send_user_email($to, $subject, $message) {
    $settings = get_all_settings();
    $site_name = $settings['site_name'] ?? SITE_NAME;
    $site_url = SITE_URL;
    $host = parse_url($site_url, PHP_URL_HOST);

    // Cleaner, more transactional template
    $html_message = "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <title>$subject</title>
    </head>
    <body style='margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, Helvetica, Arial, sans-serif; background-color: #f4f4f4; color: #333;'>
        <table width='100%' border='0' cellspacing='0' cellpadding='0' style='background-color: #f4f4f4; padding: 20px;'>
            <tr>
                <td align='center'>
                    <table width='600' border='0' cellspacing='0' cellpadding='0' style='background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.05);'>
                        <tr>
                            <td style='padding: 40px 40px 20px 40px; text-align: center;'>
                                <h1 style='color: #E50914; margin: 0; font-size: 28px;'>$site_name</h1>
                            </td>
                        </tr>
                        <tr>
                            <td style='padding: 0 40px 40px 40px; line-height: 1.6; font-size: 16px; color: #555;'>
                                $message
                            </td>
                        </tr>
                        <tr>
                            <td style='padding: 20px; background-color: #fafafa; text-align: center; font-size: 12px; color: #999;'>
                                &copy; " . date('Y') . " $site_name. All rights reserved.
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </body>
    </html>";

    $from_email = (!empty($settings['smtp_user']) && strpos($settings['smtp_user'], '@') !== false) ? $settings['smtp_user'] : ("noreply@" . ($host ?: 'moviefizz.com'));

    if (!empty($settings['smtp_host'])) {
        $mailer = new SimpleSMTP(
            $settings['smtp_host'], 
            $settings['smtp_port'], 
            $settings['smtp_user'], 
            $settings['smtp_pass'], 
            $settings['smtp_crypto']
        );
        return $mailer->send($to, $site_name, $subject, $html_message, $from_email);
    }

    $headers = "From: " . $site_name . " <$from_email>\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    return @mail($to, $subject, $html_message, $headers);
}

// Generate a random 6-digit verification code
function generate_verification_code() {
    return str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);
}

// API Fetch Helper (cURL)
function fetch_from_api($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_USERAGENT, 'MovieFizz/1.0 (PHP)');
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    $response = curl_exec($ch);
    
    if (curl_errno($ch)) {
        return false;
    }
    
    curl_close($ch);
    return json_decode($response, true);
}

// Download image from URL
function download_image($url, $save_path) {
    if (empty($url)) return false;
    $ch = curl_init($url);
    $fp = fopen($save_path, 'wb');
    curl_setopt($ch, CURLOPT_FILE, $fp);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_USERAGENT, 'MovieFizz/1.0 (PHP)');
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    $success = curl_exec($ch);
    curl_close($ch);
    fclose($fp);
    return $success;
}

// Local SVG Placeholder Generator (Stops infinite loading from external requests)
function get_placeholder($text = 'No Poster', $width = 300, $height = 450) {
    $svg = '<svg width="'.$width.'" height="'.$height.'" viewBox="0 0 '.$width.' '.$height.'" xmlns="http://www.w3.org/2000/svg">
        <rect width="100%" height="100%" fill="#1a1a1a"/>
        <text x="50%" y="50%" font-size="20" fill="#444" text-anchor="middle" dy=".3em" font-family="sans-serif">'.$text.'</text>
    </svg>';
    return 'data:image/svg+xml;base64,' . base64_encode($svg);
}

// Auto-Extract Subtitles and Secondary Audio using FFmpeg
// Auto-Extract Subtitles and Secondary Audio using FFmpeg
function extract_subtitles_from_video($video_path, $content_id, $type = 'movie') {
    global $pdo;
    
    // Define commands
    $ffmpeg_cmd = 'ffmpeg';
    $ffprobe_cmd = 'ffprobe';
    
    $local_ffmpeg = realpath(__DIR__ . '/../ffmpeg_bin/ffmpeg.exe');
    $local_ffprobe = realpath(__DIR__ . '/../ffmpeg_bin/ffprobe.exe');
    
    if ($local_ffmpeg && file_exists($local_ffmpeg)) {
        $ffmpeg_cmd = escapeshellcmd($local_ffmpeg);
        $ffprobe_cmd = escapeshellcmd($local_ffprobe);
    } else {
        // Fallback check
        $ffprobe_check = shell_exec('ffprobe -version 2>&1');
        if (!$ffprobe_check || strpos(strtolower($ffprobe_check), 'ffprobe') === false) {
            return false; // FFprobe not installed
        }
    }

    $is_remote = (strpos($video_path, 'http') === 0);
    $full_path = $video_path;
    $ua_header = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36';

    if (is_terabox_url($video_path)) {
        $resolution = resolve_media_url($video_path);
        $full_path = $resolution['stream'] ?? $video_path;
        $is_remote = true;
    } elseif (!$is_remote) {
        $full_path = realpath(__DIR__ . '/../' . $video_path);
        if (!$full_path || !file_exists($full_path)) return 0;
    }

    // FFmpeg/FFprobe arguments for remote files
    $remote_args = $is_remote ? "-user_agent " . escapeshellarg($ua_header) . " -referer " . escapeshellarg('https://www.terabox.com/') : "";

    // Get Total Duration for progress calculation
    $cmd_duration = "$ffprobe_cmd $remote_args -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 " . escapeshellarg($full_path);
    $duration = (float)shell_exec($cmd_duration);

    // Create necessary directories
    $sub_dir = __DIR__ . '/../uploads/subtitles/';
    $audio_dir = __DIR__ . '/../uploads/audio/';
    $progress_dir = __DIR__ . '/../uploads/progress/';
    if (!is_dir($sub_dir)) mkdir($sub_dir, 0777, true);
    if (!is_dir($audio_dir)) mkdir($audio_dir, 0777, true);
    if (!is_dir($progress_dir)) mkdir($progress_dir, 0777, true);

    $progress_file = $progress_dir . md5($video_path . $content_id) . ".txt";
    file_put_contents($progress_file, "total_duration=$duration\nstatus=probing\nprogress=0\n");

    // Language Mapping for Friendly Labels
    $lang_names = [
        'tam' => 'Tamil', 'tel' => 'Telugu', 'mal' => 'Malayalam', 'kan' => 'Kannada', 
        'hin' => 'Hindi', 'eng' => 'English', 'fra' => 'French', 'spa' => 'Spanish',
        'jpn' => 'Japanese', 'kor' => 'Korean', 'chi' => 'Chinese'
    ];

    // Run ffprobe to get subtitle and audio streams
    $cmd_probe = "$ffprobe_cmd $remote_args -v error -show_entries stream=index,codec_type:stream_tags=language,title -of json " . escapeshellarg($full_path);
    $probe_output = shell_exec($cmd_probe);
    
    if ($probe_output) {
        $probe_data = json_decode($probe_output, true);
        if (isset($probe_data['streams']) && is_array($probe_data['streams'])) {
            $extracted_count = 0;
            $audio_count = 0; // Track audio streams to skip the first (primary) one
            
            foreach ($probe_data['streams'] as $stream) {
                if (!isset($stream['codec_type'])) continue;
                $s_index = $stream['index'];
                $codec_type = $stream['codec_type'];
                $tags = $stream['tags'] ?? [];
                
                if ($codec_type === 'subtitle') {
                    $s_lang_code = $tags['language'] ?? 'eng';
                    $s_lang_name = $lang_names[strtolower($s_lang_code)] ?? strtoupper($s_lang_code);
                    $s_title = $tags['title'] ?? "$s_lang_name Subtitle";
                    
                    $vtt_name = time() . "_sub_{$type}_{$content_id}_{$s_index}.vtt";
                    $vtt_relative_path = 'uploads/subtitles/' . $vtt_name;
                    $vtt_absolute_path = __DIR__ . '/../' . $vtt_relative_path;
                    
                    file_put_contents($progress_file, "status=Extracting Subtitle: $s_lang_name\n", FILE_APPEND);
                    
                    // Extract to VTT - Subtitles are fast
                    $cmd_extract = "$ffmpeg_cmd $remote_args -i " . escapeshellarg($full_path) . " -map 0:$s_index -c:s webvtt " . escapeshellarg($vtt_absolute_path) . " -y 2>&1";
                    shell_exec($cmd_extract);
                    
                    if (file_exists($vtt_absolute_path) && filesize($vtt_absolute_path) > 0) {
                        $table = $type === 'episode' ? 'episode_subtitles' : 'movie_subtitles';
                        $col_id = $type === 'episode' ? 'episode_id' : 'movie_id';
                        try {
                            $stmt_sub = $pdo->prepare("INSERT INTO $table ($col_id, language, label, file_url) VALUES (?, ?, ?, ?)");
                            $stmt_sub->execute([$content_id, $s_lang_code, $s_title, $vtt_relative_path]);
                            $extracted_count++;
                        } catch (Exception $e) {}
                    }
                } elseif ($codec_type === 'audio') {
                    $audio_count++;
                    if ($audio_count > 1) {
                        $s_lang_code = $tags['language'] ?? 'und';
                        $s_lang_name = $lang_names[strtolower($s_lang_code)] ?? strtoupper($s_lang_code);
                        $s_title = $tags['title'] ?? "$s_lang_name Audio";
                        
                        $m4a_name = time() . "_audio_{$type}_{$content_id}_{$s_index}.m4a";
                        $m4a_relative_path = 'uploads/audio/' . $m4a_name;
                        $m4a_absolute_path = __DIR__ . '/../' . $m4a_relative_path;
                        
                        file_put_contents($progress_file, "status=Extracting Audio: $s_lang_name\n", FILE_APPEND);
                        
                        // Extract alternative audio to M4A - WITH LOUDNORM & 192k bitrate
                        $cmd_extract = "$ffmpeg_cmd $remote_args -i " . escapeshellarg($full_path) . " -map 0:$s_index -c:a aac -b:a 192k -af loudnorm -progress " . escapeshellarg($progress_file) . " " . escapeshellarg($m4a_absolute_path) . " -y 2>&1";
                        shell_exec($cmd_extract);
                        
                        if (file_exists($m4a_absolute_path) && filesize($m4a_absolute_path) > 0) {
                            $table = $type === 'episode' ? 'episode_audio' : 'movie_audio';
                            $col_id = $type === 'episode' ? 'episode_id' : 'movie_id';
                            try {
                                $stmt_aud = $pdo->prepare("INSERT INTO $table ($col_id, language, label, file_url) VALUES (?, ?, ?, ?)");
                                $stmt_aud->execute([$content_id, strtoupper($s_lang_code), $s_title, $m4a_relative_path]);
                                $extracted_count++;
                            } catch (Exception $e) {}
                        }
                    }
                }
            }
            @unlink($progress_file); // Clean up
            return $extracted_count;
        }
    }
    return 0;
}

// Check if a URL is a Terabox link
function is_terabox_url($url) {
    return (strpos($url, 'terabox.com') !== false || strpos($url, 'teraboxapp.com') !== false || strpos($url, '1024terabox.com') !== false || strpos($url, 'freeterabox.com') !== false);
}

// Resolve a media URL (handles Terabox and other cloud links at runtime)
function resolve_media_url($url) {
    if (is_terabox_url($url)) {
        $data = TeraboxResolver::resolve($url);
        if ($data) {
            $res = [];
            foreach (['stream', 'download'] as $key) {
                if ($data[$key]) {
                    if (strpos($data[$key], 'terabox') !== false || strpos($data[$key], '1024tera') !== false) {
                        $res[$key] = 'includes/proxy_terabox.php?url=' . urlencode($data[$key]);
                    } else {
                        $res[$key] = $data[$key];
                    }
                }
            }
            return $res;
        }
    }
    return ['stream' => $url, 'download' => $url];
}
// Generate Cloud Player URL based on ID
function get_cloud_player_url($id, $type = 'movie', $season = 1, $episode = 1, $dub = 0) {
    if (empty($id)) return '';
    
    $settings = get_all_settings();
    $autoplay = $settings['autoplay'] ?? 0;
    $provider = $settings['default_provider'] ?? 'vidrock';
    
    $auto_param = ($autoplay == 1) ? "?autoplay=1" : "";

    if ($provider === 'superembed') {
        if ($type === 'movie') {
            return "https://vidsrc.cc/v2/embed/movie/$id";
        } elseif ($type === 'tv') {
            return "https://vidsrc.cc/v2/embed/tv/$id/$season/$episode";
        }
    } elseif ($provider === 'vidlink') {
        if ($type === 'movie') {
            return "https://vidlink.pro/movie/$id" . $auto_param;
        } elseif ($type === 'tv') {
            return "https://vidlink.pro/tv/$id/$season/$episode" . $auto_param;
        }
    } elseif ($provider === 'vidsrc') {
        if ($type === 'movie') {
            return "https://vidsrc.icu/embed/movie/$id" . $auto_param;
        } elseif ($type === 'tv') {
            return "https://vidsrc.icu/embed/tv/$id/$season/$episode" . $auto_param;
        }
    } else {
        // Default: Vidrock.net
        if ($type === 'movie') {
            return "https://vidrock.net/movie/$id" . $auto_param;
        } elseif ($type === 'tv') {
            return "https://vidrock.net/tv/$id/$season/$episode" . $auto_param;
        }
    }
    return '';
}

// Send Movie/Show Request Notification to Admin
function send_media_request_notification($data) {
    $title = $data['title'];
    $type = ucfirst($data['type']);
    $user_email = $data['email'];
    $username = $data['username'];
    
    $subject = "New Content Request: " . $title;
    $message = "
    <h2>New $type Request</h2>
    <p><strong>Title:</strong> $title</p>
    <p><strong>Requested By:</strong> $username ($user_email)</p>
    <p><strong>Time:</strong> " . date('Y-m-d H:i:s') . "</p>
    <hr>
    <p>Please upload this content as soon as possible.</p>
    ";
    
    return send_admin_notification($subject, $message, $user_email);
}
?>
