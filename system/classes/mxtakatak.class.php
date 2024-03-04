<?php

class mxtakatak
{
    public $enable_proxies = false;

    public function media_info($url)
    {
        $url = unshorten($url, $this->enable_proxies);
        preg_match('/video\/(\w+)/', $url, $matches);
        if (!isset($matches[1]) != "") {
            return false;
        }
        $video_id = $matches[1];
        $web_page = url_get_contents($url, $this->enable_proxies);
        $data = get_string_between($web_page, 'window._state =', 'window.clientTime');
        $data = json_decode($data, 1);
        if (empty($data) || !isset($data["entities"][$video_id])) {
            return false;
        }
        $video["source"] = "mxtakatak";
        $video["title"] = $data["entities"][$video_id]["desc"];
        $video["thumbnail"] = $data["entities"][$video_id]["thumbnail"];
        $video["links"] = [];
        if (isset($data["entities"][$video_id]["audio"]["url"]) != "") {
            $bytes = get_file_size($data["entities"][$video_id]["audio"]["url"], $this->enable_proxies, false, "https://www.mxtakatak.com/");
            array_push($video["links"], [
                "url" => $data["entities"][$video_id]["audio"]["url"],
                "type" => "m4a",
                "bytes" => $bytes,
                "size" => format_size($bytes),
                "quality" => "128kbps",
                "mute" => false
            ]);
        }
        if (isset($data["entities"][$video_id]["download_url"]) != "") {
            $bytes = get_file_size($data["entities"][$video_id]["download_url"], $this->enable_proxies, false, "https://www.mxtakatak.com/");
            array_push($video["links"], [
                "url" => $data["entities"][$video_id]["download_url"],
                "type" => "mp4",
                "bytes" => $bytes,
                "size" => format_size($bytes),
                "quality" => $data["entities"][$video_id]["origin_height"] . "p",
                "mute" => false
            ]);
        }
        return $video;
    }
}