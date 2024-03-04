<?php

class periscope
{
    public $enable_proxies = false;

    public function media_info($url)
    {
        $url = strtok($url, '?');
        if (isset(explode("/", $url)[4]) != "") {
            $broadcast_id = explode("/", $url)[4];
        } else {
            return false;
        }
        $website_url = json_decode(option("general_settings"), true)["url"];
        $video["links"] = array();
        $data = url_get_contents("https://proxsee.pscp.tv/api/v2/accessVideoPublic?broadcast_id=$broadcast_id&replay_redirect=false", $this->enable_proxies);
        $data = json_decode($data, true);
        if (!isset($data["replay_url"])) {
            $data["replay_url"] = $data["hls_url"];
        }
        $file_id = $broadcast_id;
        $thumbnail_file = __DIR__ . "/../storage/temp/periscope-" . $file_id . ".jpg";
        file_put_contents($thumbnail_file, url_get_contents($data["broadcast"]["image_url"], $this->enable_proxies));
        $video["title"] = $data["broadcast"]["status"];
        $video["source"] = "periscope";
        $video["thumbnail"] = $website_url . "/system/storage/temp/periscope-" . $file_id . ".jpg";
        $playlist = url_get_contents($data["replay_url"], $this->enable_proxies);
        $parsed_url = parse_url($data["replay_url"]);
        $playlist_host = $parsed_url["host"];
        $playlist_path = $parsed_url["path"];
        preg_match_all('/(\d{2,5}),CODECS.*?\s(.*?)\s/', $playlist, $matches);
        if (isset($matches[2][0]) == "") {
            preg_match_all('/(.*?).ts/', $playlist, $matches);
            if (isset($matches[0][0]) == "") {
                return false;
            }
            for ($i = 0; $i < count($matches[0]); $i++) {
                $matches[0][$i] = preg_replace('/(\w{3,50}).m3u8/', $matches[0][$i], "https://" . $playlist_host . $playlist_path);
            }
            file_put_contents(__DIR__ . "/../storage/temp/periscope-" . $file_id . ".json", json_encode($matches[0]));
            $chunk_size = $this->chunk_size($matches[0][0]);
            $file_size = $chunk_size * count($matches[0]);
            array_push($video["links"], array(
                "url" => $website_url . "/system/storage/temp/periscope-" . $file_id . ".json",
                "type" => "mp4",
                "quality" => "720p",
                "bytes" => $file_size,
                "size" => format_size($file_size),
                "mute" => false
            ));
            return $video;
        }
        $playlists = array();
        for ($i = 0; $i < count($matches[2]); $i++) {
            if ($i > 0) {
                break;
            }
            $playlists[$i]["quality"] = $matches[1][$i];
            $playlists[$i]["url"] = "https://" . $playlist_host . $matches[2][$i];
            preg_match_all('/(.*).ts/', url_get_contents($playlists[$i]["url"], $this->enable_proxies), $matches2);
            if (isset($matches2[0][0])) {
                for ($j = 0; $j < count($matches2[0]); $j++) {
                    $playlists[$i]["chunks"][$j] = preg_replace('/(\w{3,50}).m3u8/', $matches2[0][$j], $playlists[$i]["url"]);
                }
                array_push($playlists[$i]["chunks"], $playlists[$i]["url"]);
                file_put_contents(__DIR__ . "/../storage/temp/periscope-" . $file_id . "-" . $playlists[$i]["quality"] . ".json", json_encode($playlists[$i]["chunks"]));
            }
            $chunk_size = $this->chunk_size($playlists[$i]["chunks"][0]);
            $file_size = $chunk_size * count($playlists[$i]["chunks"]);
            array_push($video["links"], array(
                "url" => $website_url . "/system/storage/temp/periscope-" . $file_id . "-" . $playlists[$i]["quality"] . ".json",
                "type" => "mp4",
                "quality" => "720p",
                //"quality" => $playlists[$i]["quality"] . "p",
                "size" => format_size($file_size),
                "mute" => false
            ));
        }
        return $video;
    }

    private function chunk_size($url)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HEADER, TRUE);
        curl_setopt($ch, CURLOPT_NOBODY, TRUE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        $data = curl_exec($ch);
        $size = curl_getinfo($ch, CURLINFO_CONTENT_LENGTH_DOWNLOAD);
        curl_close($ch);
        return $size;
    }

    private function merge_parts($stream_playlist, $merged_file)
    {
        $merged = "";
        $context_options = array(
            "ssl" => array(
                "verify_peer" => false,
                "verify_peer_name" => false,
            )
        );
        foreach ($stream_playlist as $stream_url) {
            //$merged .= url_get_contents($stream_url, $this->enable_proxies);
            //$merged .= copyfile_chunked();
            file_put_contents($merged_file, url_get_contents($stream_url, $this->enable_proxies), FILE_APPEND);
        }
        //file_put_contents($merged_file, $merged);
    }
}