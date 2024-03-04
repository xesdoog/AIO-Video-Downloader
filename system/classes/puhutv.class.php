<?php

class puhutv
{
    public $enable_proxies = false;

    private function find_best_quality($videos)
    {
        $best = $videos[0];
        for ($i = 1; $i < count($videos); $i++) {
            if ($videos[$i]["quality"] != null & $videos[$i]["quality"] > $best["quality"]) {
                $best = $videos[$i];
            }
        }
        return $best;
    }

    function media_info($url)
    {
        $url = preg_replace('/(.*)-detay/', '$1-izle', $url);
        $web_page = url_get_contents($url, $this->enable_proxies);
        $video_id = get_string_between($web_page, 'data-asset-id="', '"');
        if (empty($video_id)) {
            return false;
        }
        //$data["title"] = get_string_between($web_page, 'data-video-title="', '"');
        $data["title"] = get_string_between($web_page, "<title>", " |");
        $data["thumbnail"] = get_string_between($web_page, "property='og:image' content='", "'");
        $data["source"] = "puhutv";
        $data["links"] = array();
        $website_url = json_decode(option("general_settings"), true)["url"];
        $api_url = "https://puhutv.com/api/assets/" . $video_id . "/videos";
        $api_response = url_get_contents($api_url);
        $api_data = json_decode($api_response, true);
        $main_playlist = $this->find_best_quality($api_data["data"]["videos"])["url"];
        $main_playlist_data = url_get_contents($main_playlist, $this->enable_proxies);
        preg_match_all('/.*?.m3u8.*/', $main_playlist_data, $matches);
        if (isset($matches[0]) == "") {
            return false;
        }
        $playlists = $matches[0];
        preg_match('/(.*?)playlist.m3u8/', $main_playlist, $matches);
        if (count($matches) !== 2) {
            return false;
        }
        $cdn_url = $matches[1];
        /*
        if (is_contains($main_playlist, 'playlist.m3u8')) {
            $main_playlist_data = url_get_contents($main_playlist, $this->enable_proxies);
            preg_match_all('/.*?.m3u8/', $main_playlist_data, $matches);
            if (isset($matches[0]) == "") {
                return false;
            }
            $playlists = $matches[0];
            preg_match('/(.*?)playlist.m3u8/', $main_playlist, $matches);
            if (count($matches) !== 2) {
                return false;
            }
            $cdn_url = $matches[1];
        } else {
            $playlists = [];
            foreach ($api_data["data"]["videos"] as $playlist) {
                preg_match('/(.*)chunklist(.*)/', $playlist['url'], $matches);
                print_r($matches);
                $cdn_url = $matches[1];
                array_push($playlists, "chunklist" . $matches[2]);
            }
        }
        */
        /*
        $playlist_data = url_get_contents($cdn_url . $playlists[0], $this->enable_proxies);
        preg_match_all('/.*\.ts/', $playlist_data, $matches);
        print_r($matches);
        return $data;
        */
        foreach ($playlists as $playlist) {
            $playlist_data = url_get_contents($cdn_url . $playlist, $this->enable_proxies);
            preg_match_all('/.*\.ts/', $playlist_data, $matches);
            if (isset($matches[0]) != "" && count($matches[0]) > 1) {
                preg_match('/(\d{3,4})p/', $playlist, $quality);
                if (count($quality) === 2) {
                    $quality = $quality[1];
                    $format = true;
                } else {
                    preg_match('/P(\d{3,4}).mp4/', $playlist, $quality);
                    $quality = $quality[1];
                    $format = false;
                }
                $chunks = $matches[0];
                for ($i = 0; $i < count($chunks); $i++) {
                    if ($format) {
                        $chunks[$i] = $cdn_url . $quality . "p/" . $chunks[$i];
                    } else {
                        $chunks[$i] = $cdn_url . $chunks[$i];
                    }
                }
                $chunk_size = get_file_size($chunks[1], $this->enable_proxies, false);
                $chunk_file = "/../storage/temp/puhutv-" . $video_id . "-" . $quality . ".json";
                file_put_contents(__DIR__ . $chunk_file, json_encode($chunks));
                $file_size = $chunk_size * count($chunks);
                array_push($data["links"], [
                    "url" => $website_url . "/system/storage/temp/puhutv-" . $video_id . "-" . $quality . ".json",
                    "type" => "mp4",
                    "bytes" => $file_size,
                    "size" => format_size($file_size),
                    "quality" => $quality . "p",
                    "mute" => false
                ]);
            }
        }
        return $data;
    }
}