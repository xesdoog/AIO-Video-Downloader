<?php

class bitchute
{
    public $enable_proxies = false;

    public function media_info($url)
    {
        $web_page = url_get_contents($url, $this->enable_proxies);
        $video["title"] = get_string_between($web_page, '<title>', '</title>');
        $video["source"] = "bitchute";
        $video["thumbnail"] = get_string_between($web_page, 'poster="', '"');
        $video["duration"] = get_string_between($web_page, '<span class="video-duration">', '</span>');
        $video_url = get_string_between($web_page, '<source src="', '"');
        $video["links"][0]["url"] = $video_url;
        $video["links"][0]["type"] = "mp4";
        $video["links"][0]["bytes"] = get_file_size($video_url, $this->enable_proxies, false);
        $video["links"][0]["size"] = format_size($video["links"][0]["bytes"]);
        $video["links"][0]["quality"] = "HD";
        $video["links"][0]["mute"] = "no";
        return $video;
    }
}