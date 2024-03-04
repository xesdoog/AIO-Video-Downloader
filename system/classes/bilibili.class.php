<?php

class bilibili
{
    public $enable_proxies = false;

    private static function get_contents($url)
    {
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_USERAGENT => _REQUEST_USER_AGENT,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER => 0
        ));
        $response = curl_exec($curl);
        curl_close($curl);
        return $response;
    }

    public function media_info($url)
    {
        $web_page = self::get_contents($url);
        file_put_contents(__DIR__ . '/../storage/temp/bilibili.html', $web_page);
        preg_match_all('/window\.__playinfo__=(.*)<\/script><script>/', $web_page, $matches);
        if (!isset($matches[1][0]) || empty($matches[1][0])) {
            return false;
        }
        $data = json_decode(get_string_between($web_page, 'window.__playinfo__=', '</script>'), true);
        $video["title"] = get_string_between($web_page, 'itemprop="name" name="title" content="', '"');
        $video["source"] = "bilibili";
        $video["thumbnail"] = get_string_between($web_page, 'data-vue-meta="true" itemprop="image" content="', '"');
        $sha1_url = sha1($url);
        file_put_contents(__DIR__ . "/../storage/temp/bilibili-" . $sha1_url . ".jpg", self::get_contents($video["thumbnail"]));
        $video["thumbnail"] = json_decode(option("general_settings"), true)["url"] . "/system/storage/temp/bilibili-" . $sha1_url . ".jpg";
        $video["duration"] = format_seconds($data["data"]["dash"]["duration"]);
        $video["links"] = array();
        for ($i = 0; $i < count($data["data"]["dash"]["video"]); $i++) {
            $file_size = $this->estimate_video_size($data["data"]["dash"]["video"][$i]["bandwidth"], $data["data"]["dash"]["duration"]);
            $video["links"][$i] = [
                "url" => $data["data"]["dash"]["video"][$i]["base_url"],
                "type" => "mp4",
                "bytes" => $file_size,
                "size" => format_size($file_size),
                "quality" => $data["data"]["dash"]["video"][$i]["height"] . "p",
                "mute" => true
            ];
        }
        for ($i = 0; $i < count($data["data"]["dash"]["audio"]); $i++) {
            $file_size = $this->estimate_video_size($data["data"]["dash"]["audio"][$i]["bandwidth"], $data["data"]["dash"]["duration"]);
            $video["links"][$i] = [
                "url" => $data["data"]["dash"]["audio"][$i]["base_url"],
                "type" => "m4a",
                "bytes" => $file_size,
                "size" => format_size($file_size),
                "quality" => $this->format_bitrate($data["data"]["dash"]["audio"][$i]["bandwidth"]),
                "mute" => true
            ];
        }
        usort($video["links"], 'sort_by_quality');
        return $video;
    }

    private function estimate_video_size($bandwidth, $duration)
    {
        $minutes = (double)$duration / 60;
        $bitrate = (double)$bandwidth * 10;
        return $minutes * $bitrate;
    }

    private function format_bitrate($bitrate)
    {
        if ($bitrate >= 1073741824) {
            $bitrate = number_format($bitrate / 1073741824, 2) . ' GB';
        } elseif ($bitrate >= 1048576) {
            $bitrate = number_format($bitrate / 1048576, 2) . ' MB';
        } elseif ($bitrate >= 1024) {
            $bitrate = number_format($bitrate / 1024, 2) . ' KB';
        } elseif ($bitrate > 1) {
            $bitrate = $bitrate . ' bytes';
        } elseif ($bitrate === 1) {
            $bitrate = $bitrate . ' byte';
        } else {
            $bitrate = '0 bytes';
        }
        $kb = (int)$bitrate;
        return $kb . ' kbps';
    }
}