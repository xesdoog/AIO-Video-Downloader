<?php

class pinterest
{
    public $enable_proxies = false;

    function media_info($url)
    {
        $parsed_url = parse_url($url);
        if ($parsed_url['host'] == 'pin.it') {
            $original_url = unshorten($url, $this->enable_proxies);
            if (isset($original_url) != "") {
                $url = strtok($original_url, '?');
            }
        }
        $page_source = url_get_contents($url, $this->enable_proxies);
        $video["title"] = get_string_between($page_source, "<title>", "</title>");
        $video["source"] = "pinterest";
        $video["thumbnail"] = get_string_between($page_source, '"image_cover_url":"', '"');
        $video_data = get_string_between($page_source, '<script id="initial-state" type="application/json">', '</script>');
        $video_data = json_decode($video_data, true);
        if (isset($video_data["resourceResponses"][0]["response"]["data"]["videos"]["video_list"])) {
            $streams = $video_data["resourceResponses"][0]["response"]["data"]["videos"]["video_list"];
        } elseif (isset(reset($video_data["resources"]["data"]["PinResource"])["data"]["videos"]["video_list"])) {
            $streams = reset($video_data["resources"]["data"]["PinResource"])["data"]["videos"]["video_list"];
            $video["title"] = reset($video_data["resources"]["data"]["PinResource"])["data"]["title"];
        } else {
            return false;
        }
        if (count($streams) > 0) {
            $i = 0;
            foreach ($streams as $stream) {
                $ext = pathinfo(parse_url($stream["url"])["path"], PATHINFO_EXTENSION);
                if ($ext != "m3u8") {
                    $video["links"][$i]["url"] = $stream["url"];
                    $video["links"][$i]["type"] = $ext;
                    $video["links"][$i]["bytes"] = get_file_size($stream["url"], $this->enable_proxies, false);
                    $video["links"][$i]["size"] = format_size($video["links"][$i]["bytes"]);
                    $video["links"][$i]["quality"] = min($stream["height"], $stream["width"]) . "p";
                    $video["links"][$i]["mute"] = "no";
                    $i++;
                }
            }
            return $video;
        } else {
            return false;
        }
    }
}