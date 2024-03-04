<?php

class animeto
{
    public $enable_proxies = false;

    public function media_info($url)
    {
        $web_page = url_get_contents($url, $this->enable_proxies);
        $video_url = get_string_between($web_page, '<source src="', '" type="video/mp4">');
        $video["title"] = get_string_between($web_page, '<title data-react-helmet="true">', '</title>');
        $anime_title = get_string_between($web_page, 'id="titleleft">', '</a>');
        $anime_title = str_replace(":", "", $anime_title);
        $anime_title = str_replace(" ", "-", $anime_title);
        $video["source"] = "4anime";
        $video["thumbnail"] = "https://4anime.to/image/" . $anime_title . ".jpg";
        $video["links"][0]["url"] = $video_url;
        $video["links"][0]["type"] = "mp4";
        $video["links"][0]["bytes"] = get_file_size($video_url, $this->enable_proxies, false);
        $video["links"][0]["size"] = format_size($video["links"][0]["bytes"]);
        $video["links"][0]["quality"] = "HD";
        $video["links"][0]["mute"] = false;
        return $video;
    }
}