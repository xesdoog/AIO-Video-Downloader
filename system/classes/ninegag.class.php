<?php

class ninegag
{
    public $enable_proxies = false;

    public function media_info($url)
    {
        $videoId = ninegag::get_id($url);
        if ($videoId != false && $videoId != "") {
            $video["title"] = "9GAG Video";
            $videoUrl = "https://img-9gag-fun.9cache.com/photo/" . $videoId . "_460sv.mp4";
            $videoSize = get_file_size($videoUrl, $this->enable_proxies, false);
            if ($videoSize > 1000) {
                $video["links"][0]["url"] = $videoUrl;
                $video["links"][0]["type"] = "mp4";
                $video["links"][0]["bytes"] = $videoSize;
                $video["links"][0]["size"] = format_size($videoSize);
                $video["links"][0]["quality"] = "HD";
                $video["links"][0]["mute"] = "no";
            }
            $video["thumbnail"] = "http://images-cdn.9gag.com/photo/" . $videoId . "_460s.jpg";
            $video["source"] = "9gag";
            return $video;
        } else {
            return false;
        }
    }

    public function media_info_beta($url)
    {
        $web_page = file_get_contents($url);
        $json_data = get_string_between($web_page, 'JSON.parse("', '")');
        $json_data = str_replace('\"', '"', $json_data);
        $data = json_decode($json_data, true)["data"];
        $video["title"] = $data["post"]["title"];
        foreach ($data["post"]["images"] as $image) {
            if (isset($image["duration"]) != "") {
                $video["links"][0]["url"] = $this->convert_url($image["url"]);
                $video["links"][0]["type"] = "mp4";
                $video["links"][0]["size"] = get_file_size($video["links"][0]["url"]);
                $video["links"][0]["quality"] = min($image["height"], $image["width"]) . "p";
                $video["links"][0]["mute"] = "no";
                $video["duration"] = format_seconds($image["duration"]);
            } else if (isset($video["thumbnail"])) {
                $video["thumbnail"] = $this->convert_url($image["url"]);
            }
        }
        $video["source"] = "9gag";
        return $video;
    }

    public static function get_id($url)
    {
        preg_match('/gag\/(\w+)/', $url, $output);
        return isset($output[1]) != "" ? $output[1] : false;
    }

    private function convert_url($url)
    {
        return str_replace("\\", "", $url);
    }
}