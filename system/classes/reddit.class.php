<?php

class reddit
{
    public $enable_proxies = false;

    function media_info($url)
    {
        $web_page = url_get_contents($url, $this->enable_proxies);
        $video["source"] = "reddit";
        $video["title"] = get_string_between($web_page, "<title>", "</title>");
        $video["thumbnail"] = get_string_between($web_page, '<meta property="og:image" content="', '"/>');
        if (empty($video["thumbnail"])) {
            $video["thumbnail"] = get_string_between($web_page, '"thumbnailUrl":"', '"');
            $video["thumbnail"] = preg_replace_callback('/\\\\u([0-9a-fA-F]{4})/', function ($match) {
                return mb_convert_encoding(pack('H*', $match[1]), 'UTF-8', 'UCS-2BE');
            }, $video["thumbnail"]);
        }
        $playlist_url = get_string_between($web_page, '"dashUrl":"', '"');
        if (empty($playlist_url)) {
            preg_match_all('/<a href="https:\/\/(youtu.be|www.youtube.com|youtube.com)\/(.*?)"/', $web_page, $output);
            if (count($output) < 3) {
                return false;
            }
            $yt_url = "https://www.youtube.com/" . html_entity_decode($output[2][0]);
            require_once __DIR__ . "/youtube.class.php";
            $download = new youtube();
            return $download->media_info($yt_url);
        }
        $xml_playlist = url_get_contents($playlist_url, $this->enable_proxies);
        preg_match_all('/<BaseURL>(.*)<\/BaseURL>/', $xml_playlist, $medias);
        $video_id = get_string_between(parse_url($playlist_url, PHP_URL_PATH), '/', '/DASHPlaylist.mpd');
        if (empty($medias[1])) {
            return false;
        }
        $medias = $medias[1];
        $video["links"] = array();
        foreach ($medias as $media) {
            $dashType = get_string_between($media, "DASH_", ".");
            $mediaUrl = "https://v.redd.it/" . $video_id . "/" . $media;
            $bytes = get_file_size($mediaUrl, $this->enable_proxies, false);
            array_push($video["links"], [
                "url" => $mediaUrl,
                "type" => $dashType == "audio" ? "m4a" : "mp4",
                "bytes" => $bytes,
                "size" => format_size($bytes),
                "quality" => $dashType == "audio" ? "128 kbps" : $dashType . "p",
                "mute" => true
            ]);
        }
        return $video;
    }
}