<?php

class imdb
{
    public $enable_proxies = false;

    function orderArray($arrayToOrder, $keys)
    {
        $ordered = array();
        foreach ($keys as $key) {
            if (isset($arrayToOrder[$key])) {
                $ordered[$key] = $arrayToOrder[$key];
            }
        }
        return $ordered;
    }

    function find_video_id($url)
    {
        preg_match('/vi\d{4,20}/', $url, $match);
        return $match[0];
    }

    function media_info($url)
    {
        $video_id = $this->find_video_id($url);
        $embed_url = "https://www.imdb.com/video/imdb/$video_id/imdb/embed";
        $embed_source = url_get_contents($embed_url, $this->enable_proxies);
        $video_data = get_string_between($embed_source, '<script class="imdb-player-data" type="text/imdb-video-player-json">', '</script>');
        $video_data = json_decode($video_data, true);
        $video["title"] = get_string_between($embed_source, '<meta property="og:title" content="', '"/>');
        $video["source"] = "imdb";
        $video["thumbnail"] = get_string_between($embed_source, '<meta property="og:image" content="', '">');
        if ($video["title"] != "") {
            $streams = $video_data["videoPlayerObject"]["video"]["videoInfoList"];
            $i = 0;
            foreach ($streams as $stream) {
                if ($stream["videoMimeType"] == "video/mp4") {
                    $video["links"][$i]["url"] = $stream["videoUrl"];
                    $video["links"][$i]["type"] = "mp4";
                    $video["links"][$i]["bytes"] = get_file_size($stream["videoUrl"], $this->enable_proxies, false);
                    $video["links"][$i]["size"] = format_size($video["links"][$i]["bytes"]);
                    $video["links"][$i]["quality"] = "hd";
                    $video["links"][$i]["mute"] = "no";
                    $i++;
                }
            }
            return $video;
        } else {
            return $this->media_info_legacy($url);
        }
    }

    function media_info_legacy($url)
    {
        $web_page = url_get_contents($url);
        preg_match('/"playbackData":\[(.*)\],"videoInfoKey"/', $web_page, $matches);
        if (count($matches) < 2) {
            return false;
        }
        $video["title"] = get_string_between($web_page, '<title>', '</title>');
        $video["source"] = "imdb";
        $video["thumbnail"] = get_string_between($web_page, 'property="og:image" content="', '">');
        $json = "[" . $matches[1] . "]";
        $streams = json_decode(json_decode($json, true)[0], true)[0]["videoLegacyEncodings"];
        if (count($streams) < 2) {
            return false;
        }
        $i = 0;
        foreach ($streams as $stream) {
            if ($stream["mimeType"] == "video/mp4") {
                $video["links"][$i]["url"] = $stream["url"];
                $video["links"][$i]["type"] = "mp4";
                $video["links"][$i]["bytes"] = get_file_size($stream["url"], $this->enable_proxies, false);
                $video["links"][$i]["size"] = format_size($video["links"][$i]["bytes"]);
                $video["links"][$i]["quality"] = $stream["definition"];
                $video["links"][$i]["mute"] = false;
                $i++;
            }
        }
        return $video;
    }
}