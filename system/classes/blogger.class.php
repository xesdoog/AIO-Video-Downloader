<?php

class blogger
{
    public $enable_proxies = false;

    public function media_info($url)
    {
        $web_page = url_get_contents($url, $this->enable_proxies);
        preg_match_all('/src="https:\/\/www.blogger\.com\/video\.g\?token=(.*?)"/', $web_page, $tokens);
        $video["title"] = get_string_between($web_page, '<title>', '</title>');
        $video["source"] = "blogger";
        $video["links"] = array();
        $itags = array(5 => array('extension' => 'flv', 'video' => array('width' => 400, 'height' => 240,),), 6 => array('extension' => 'flv', 'video' => array('width' => 450, 'height' => 270,),), 13 => array('extension' => '3gp',), 17 => array('extension' => '3gp', 'video' => array('width' => 176, 'height' => 144,),), 18 => array('extension' => 'mp4', 'video' => array('width' => 640, 'height' => 360,),), 22 => array('extension' => 'mp4', 'video' => array('width' => 1280, 'height' => 720,),), 34 => array('extension' => 'flv', 'video' => array('width' => 640, 'height' => 360,),), 35 => array('extension' => 'flv', 'video' => array('width' => 854, 'height' => 480,),), 36 => array('extension' => '3gp', 'video' => array('width' => 320, 'height' => 240,),), 37 => array('extension' => 'mp4', 'video' => array('width' => 1920, 'height' => 1080,),), 38 => array('extension' => 'mp4', 'video' => array('width' => 4096, 'height' => 3072,),), 43 => array('extension' => 'webm', 'video' => array('width' => 640, 'height' => 360,),), 44 => array('extension' => 'webm', 'dash' => false, 'video' => array('width' => 854, 'height' => 480,),), 45 => array('extension' => 'webm', 'video' => array('width' => 1280, 'height' => 720,),), 46 => array('extension' => 'webm', 'video' => array('width' => 1920, 'height' => 1080,),), 59 => array('extension' => 'mp4', 'video' => array('width' => 854, 'height' => 480,),), 78 => array('extension' => 'mp4', 'video' => array('width' => 854, 'height' => 480,),), 82 => array('extension' => 'mp4', 'video' => array('3d' => true, 'width' => 640, 'height' => 360,),), 83 => array('extension' => 'mp4', 'video' => array('3d' => true, 'width' => 854, 'height' => 480,),), 84 => array('extension' => 'mp4', 'video' => array('3d' => true, 'width' => 1280, 'height' => 720,),), 85 => array('extension' => 'mp4', 'video' => array('3d' => true, 'width' => 1920, 'height' => 1080,),), 100 => array('extension' => 'webm', 'video' => array('3d' => true, 'width' => 640, 'height' => 360,),), 101 => array('extension' => 'webm', 'video' => array('3d' => true, 'width' => 854, 'height' => 480,),), 102 => array('extension' => 'webm', 'video' => array('3d' => true, 'width' => 1280, 'height' => 720,),), 133 => array('extension' => 'mp4', 'dash' => 'video', 'video' => array('width' => 426, 'height' => 240,),), 134 => array('extension' => 'mp4', 'dash' => 'video', 'video' => array('width' => 640, 'height' => 360,),), 135 => array('extension' => 'mp4', 'dash' => 'video', 'video' => array('width' => 854, 'height' => 480,),), 136 => array('extension' => 'mp4', 'dash' => 'video', 'video' => array('width' => 1280, 'height' => 720,),), 137 => array('extension' => 'mp4', 'dash' => 'video', 'video' => array('width' => 1920, 'height' => 1080,),), 138 => array('extension' => 'mp4', 'dash' => 'video', 'video' => array('width' => 4096, 'height' => 2304,),), 394 => array('extension' => 'mp4', 'dash' => 'video', 'video' => array('width' => 256, 'height' => 144,),), 395 => array('extension' => 'mp4', 'dash' => 'video', 'video' => array('width' => 426, 'height' => 240,),), 396 => array('extension' => 'mp4', 'dash' => 'video', 'video' => array('width' => 640, 'height' => 360,),), 397 => array('extension' => 'mp4', 'dash' => 'video', 'video' => array('width' => 854, 'height' => 480,),), 398 => array('extension' => 'mp4', 'dash' => 'video', 'video' => array('width' => 1280, 'height' => 720,),), 399 => array('extension' => 'mp4', 'dash' => 'video', 'video' => array('width' => 1920, 'height' => 1080,),), 139 => array('extension' => 'm4a', 'dash' => 'audio', 'audio' => array('bitrate' => 48000, 'frequency' => 22050,),), 140 => array('extension' => 'm4a', 'dash' => 'audio', 'audio' => array('bitrate' => 128000, 'frequency' => 44100,),), 141 => array('extension' => 'm4a', 'dash' => 'audio', 'audio' => array('bitrate' => 256000, 'frequency' => 44100,),), 160 => array('extension' => 'mp4', 'dash' => 'video', 'video' => array('width' => 256, 'height' => 144,),), 167 => array('extension' => 'webm', 'dash' => 'video', 'video' => array('width' => 640, 'height' => 360,),), 168 => array('extension' => 'webm', 'dash' => 'video', 'video' => array('width' => 854, 'height' => 480,),), 169 => array('extension' => 'webm', 'dash' => 'video', 'video' => array('width' => 1280, 'height' => 720,),), 170 => array('extension' => 'webm', 'dash' => 'video', 'video' => array('width' => 1920, 'height' => 1080,),), 171 => array('extension' => 'webm', 'dash' => 'audio', 'audio' => array('bitrate' => 128000, 'frequency' => 44100,),), 172 => array('extension' => 'webm', 'dash' => 'audio', 'audio' => array('bitrate' => 192000, 'frequency' => 44100,),), 218 => array('extension' => 'webm', 'dash' => 'video', 'video' => array('width' => 854, 'height' => 480,),), 219 => array('extension' => 'webm', 'dash' => 'video', 'video' => array('width' => 854, 'height' => 480,),), 242 => array('extension' => 'webm', 'dash' => 'video', 'video' => array('width' => 427, 'height' => 240,),), 243 => array('extension' => 'webm', 'dash' => 'video', 'video' => array('width' => 640, 'height' => 360,),), 244 => array('extension' => 'webm', 'dash' => 'video', 'video' => array('width' => 854, 'height' => 480,),), 245 => array('extension' => 'webm', 'dash' => 'video', 'video' => array('width' => 854, 'height' => 480,),), 246 => array('extension' => 'webm', 'dash' => 'video', 'video' => array('width' => 854, 'height' => 480,),), 247 => array('extension' => 'webm', 'dash' => 'video', 'video' => array('width' => 1280, 'height' => 720,),), 248 => array('extension' => 'webm', 'dash' => 'video', 'video' => array('width' => 1920, 'height' => 1080,),), 249 => array('extension' => 'webm', 'dash' => 'audio', 'audio' => array('bitrate' => 50000, 'frequency' => 48000,),), 250 => array('extension' => 'webm', 'dash' => 'audio', 'audio' => array('bitrate' => 65000, 'frequency' => 48000,),), 251 => array('extension' => 'webm', 'dash' => 'audio', 'audio' => array('bitrate' => 158000, 'frequency' => 48000,),), 264 => array('extension' => 'mp4', 'dash' => 'video', 'video' => array('width' => 2560, 'height' => 1440,),), 266 => array('extension' => 'mp4', 'dash' => 'video', 'video' => array('width' => 3840, 'height' => 2160,),), 271 => array('extension' => 'webm', 'dash' => 'video', 'video' => array('3d' => false, 'width' => 2560, 'height' => 1440,),), 272 => array('extension' => 'webm', 'dash' => 'video', 'video' => array('width' => 3840, 'height' => 2160,),), 278 => array('extension' => 'webm', 'dash' => 'video', 'video' => array('width' => 256, 'height' => 144,),), 298 => array('extension' => 'mp4', 'dash' => 'video', 'video' => array('width' => 1280, 'height' => 720,),), 299 => array('extension' => 'mp4', 'dash' => 'video', 'video' => array('width' => 1920, 'height' => 1080,),), 302 => array('extension' => 'webm', 'dash' => 'video', 'video' => array('width' => 1280, 'height' => 720,),), 303 => array('extension' => 'webm', 'dash' => 'video', 'video' => array('width' => 1920, 'height' => 1080,),), 308 => array('extension' => 'webm', 'dash' => 'video', 'video' => array('width' => 2560, 'height' => 1440,),), 313 => array('extension' => 'webm', 'dash' => 'video', 'video' => array('width' => 3840, 'height' => 2026,),), 400 => array('extension' => 'mp4', 'dash' => 'video', 'video' => array('width' => 2560, 'height' => 1440,),), 401 => array('extension' => 'mp4', 'dash' => 'video', 'video' => array('width' => 3840, 'height' => 2160,),),);
        if (empty($tokens[1])) {
            return false;
        }
        foreach ($tokens[1] as $iframe_token) {
            $iframe_url = "https://www.blogger.com/video.g?token=" . $iframe_token;
            $iframe_page = url_get_contents($iframe_url, $this->enable_proxies);
            preg_match_all('/var VIDEO_CONFIG = (.*)/', $iframe_page, $video_data);
            if (!empty(($video_data[1][0]) ?? "")) {
                $video_data = json_decode($video_data[1][0], true);
                if (empty($video["thumbnail"])) {
                    $video["thumbnail"] = $video_data["thumbnail"];
                }
                foreach ($video_data["streams"] as $stream) {
                    $bytes = get_file_size($stream["play_url"], $this->enable_proxies, false);
                    array_push($video["links"], array(
                        "url" => $stream["play_url"],
                        "type" => $itags[$stream["format_id"]]["extension"],
                        "bytes" => $bytes,
                        "size" => format_size($bytes),
                        "quality" => $itags[$stream["format_id"]]["video"]["height"] . "p",
                        "mute" => false
                    ));
                }
            }
        }
        usort($video["links"], 'sort_by_quality');
        return $video;
    }
}