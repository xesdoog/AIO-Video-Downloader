<?php

class blutv
{
    public $enable_proxies = false;

    private function get_permission($path)
    {
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://www.blutv.com/actions/account/getpermission',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_USERAGENT => _REQUEST_USER_AGENT,
            CURLOPT_POSTFIELDS => http_build_query(['package' => 'ALL', 'platform' => 'com.blu', 'segment' => 'default', 'url' => $path, 'usetoken' => true]),
            //CURLOPT_POSTFIELDS => 'package=ALL&platform=com.blu&segment=default&sessionid=&url=ment-cozer-dizisi-1-bolum-izle&usetoken=true',
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/x-www-form-urlencoded; charset=UTF-8'
            ),
        ));
        $response = curl_exec($curl);
        curl_close($curl);
        return json_decode($response, true);
    }

    function media_info($url)
    {
        $path = parse_url($url, PHP_URL_PATH);
        $permission_data = $this->get_permission($path);
        if (!isset($permission_data["status"]) || $permission_data["status"] != "ok") {
            return false;
        }
        $video["title"] = $permission_data["model"]["Title"];
        $video["thumbnail"] = "https://blutv-images.mncdn.com/q/t/i/bluv2/100/0x0/" . $permission_data["model"]["Image"];
        $video["source"] = "blutv";
        $video["links"] = array();
        $embed_url = "https://www.blutv.com/actions/player/create/" . $permission_data["model"]["Id"] . "?seek=&platform=com.blu&token=undefined";
        $embed_page = url_get_contents($embed_url, $this->enable_proxies);
        preg_match_all('/var qplayer_config = (.*)/', $embed_page, $matches);
        if (!isset($matches[1][0]) || empty($matches[1][0])) {
            return false;
        }
        $embed_data = json_decode(substr($matches[1][0], 0, -1), true);
        if(!isset($embed_data["model"]["MediaFiles"])){
            return false;
        }
        $playlist = $embed_data["model"]["MediaFiles"][0]["Path"];
        $playlist_data = url_get_contents($playlist, $this->enable_proxies);
        preg_match_all('/RESOLUTION=.*?x(.*?),AUDIO=".*?\s(.*.m3u8)/', $playlist_data, $matches);
        if (!isset($matches[1]) || !isset($matches[2]) || empty($matches[1]) || empty($matches[2]) || count($matches[1]) != count($matches[2])) {
            return false;
        }
        $website_url = json_decode(option("general_settings"), true)["url"];
        $playlist_name = pathinfo($playlist, PATHINFO_BASENAME);
        for ($i = 0; $i < count($matches[1]); $i++) {
            $chunk_playlist = str_replace($playlist_name, $matches[2][$i], $playlist);
            $chunk_list = url_get_contents($chunk_playlist, $this->enable_proxies);
            preg_match_all('/.*.ts/', $chunk_list, $matches2);
            if (isset($matches2[0]) != "") {
                $chunks = $matches2[0];
                for ($j = 0; $j < count($chunks); $j++) {
                    $chunks[$j] = str_replace(pathinfo($chunk_playlist, PATHINFO_BASENAME), $chunks[$j], $chunk_playlist);
                }
                $chunk_size = get_file_size($chunks[0], $this->enable_proxies, false);
                $chunk_file = "/../storage/temp/blutv-" . $permission_data["model"]["IId"] . "-" . $matches[1][$i] . ".json";
                file_put_contents(__DIR__ . $chunk_file, json_encode($chunks));
                $file_size = $chunk_size * count($chunks);
                $video["links"][$i] = [
                    "url" => $website_url . "/system/storage/temp/blutv-" . $permission_data["model"]["IId"] . "-" . $matches[1][$i] . ".json",
                    "type" => "mp4",
                    "bytes" => $file_size,
                    "size" => format_size($file_size),
                    "quality" => $matches[1][$i] . "p",
                    "mute" => false
                ];
            }
        }
        return $video;
    }
}