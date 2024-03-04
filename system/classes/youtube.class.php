<?php
require_once __DIR__ . "/vendor/autoload.php";

use YouTube\YouTubeDownloader;

class youtube
{
    public $m4a_mp3 = true;
    public $hide_dash_videos = false;
    public $enable_proxies = false;
    public $enable_redirector = false;

    function media_info($url)
    {
        $yt = new YouTubeDownloader();
        if ($this->enable_proxies) {
            $proxy = get_proxy();
            if (!empty($proxy['ip'])) {
                $yt->getBrowser()->setProxy($proxy);
            }
        }
        $data = $yt->getDownloadLinks($url);
        $links = $data["links"];
        $json = $data["json"];
        $video["title"] = $json["videoDetails"]["title"];
        $video["thumbnail"] = "https://i.ytimg.com/vi/" . $json["videoDetails"]["videoId"] . "/mqdefault.jpg";
        $video["duration"] = format_seconds($json["videoDetails"]["lengthSeconds"]);
        $video["source"] = "youtube";
        $video["links"] = array();
        $j = 0;
        for ($i = 0; $i < count($links); $i++) {
            $link = $links[$i];
            preg_match('/(video|audio)\/(.*?);/', $link["mimeType"], $matches);
            $isDash = $matches[1] == "video" && $link["mute"];
            if (count($matches) === 3 && !($isDash && $this->hide_dash_videos)) {
                $video["links"][$j] = array(
                    "url" => $link["url"],
                    "type" => $matches[1] == "video" ? $matches[2] : ($matches[2] == "mp4" ? "m4a" : $matches[2]),
                    "itag" => $link["itag"],
                    "quality" => !empty($link["quality"]) ? $link["quality"] : $this->format_bitrate($link["averageBitrate"]),
                    "mute" => $matches[1] == "video" && $link["mute"],
                    "size" => !empty($link['contentLength']) ? format_size($link['contentLength']) : $this->calculate_video_size($link["itag"], $json["videoDetails"]["lengthSeconds"])
                );
                if ($this->m4a_mp3 && $video["links"][$j]["type"] == "m4a") {
                    $video["links"][$j + 1] = array(
                        "url" => $link["url"],
                        "type" => "mp3",
                        "itag" => $link["itag"],
                        "quality" => !empty($link["quality"]) ? $link["quality"] : $this->format_bitrate($link["averageBitrate"]),
                        "mute" => $matches[1] == "video" && $link["mute"],
                        "size" => !empty($link['contentLength']) ? format_size($link['contentLength']) : $this->calculate_video_size($link["itag"], $json["videoDetails"]["lengthSeconds"])
                    );
                    $j++;
                }
                $j++;
            }
        }
        usort($video["links"], 'sort_by_quality');
        return $video;
    }

    private static function round_bitrate($bitrate)
    {
        $bitrates = [48, 64, 128, 256, 512, 1024];
        $rounded = $bitrate;
        for ($i = 0; $i < 5; $i++) {
            if (abs($bitrates[$i] - $bitrate) < abs($bitrates[$i + 1] - $bitrate)) {
                $rounded = $bitrates[$i];
                break;
            }
        }
        return $rounded;
    }

    private static function format_bitrate($bitrate)
    {
        $factor = floor((strlen($bitrate) - 1) / 3);
        $bitrate = $bitrate / pow(1024, $factor);
        $kb = self::round_bitrate((int)$bitrate);
        return $kb . ' kbps';
    }

    private static function calculate_video_size($itag, $duration)
    {
        $reference_duration = 3221;
        $reference_sizes = [
            "249" => 20401121,
            "250" => 27038123,
            "140" => 52127912,
            "394" => 25683927,
            "278" => 32759389,
            "160" => 18337619,
            "251" => 53123830,
            "395" => 48886254,
            "242" => 62683866,
            "133" => 41932753,
            "134" => 144272120,
            "396" => 99801940,
            "243" => 127107404,
            "18" => 252908754,
            "244" => 246450788,
            "135" => 324295771,
            "397" => 198229761,
            "22" => 774335049,
            "398" => 435093541,
            "247" => 528682502,
            "136" => 722450659,
            "399" => 792493924,
            "248" => 963643999,
            "137" => 1419248836,
            "400" => 2747571150,
            "271" => 3134539217,
            "313" => 6715225612,
            "401" => 5770829704,
            "299" => 792093924,
            "303" => 772493924,
            "298" => 435063541,
            "302" => 432083541
        ];
        if (isset($reference_sizes[$itag]) == "") {
            return "";
        }
        $size = ($reference_sizes[$itag] / $reference_duration) * $duration;
        return format_size($size);
    }
}