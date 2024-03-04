<?php

class douyin
{
    public $enable_proxies = false;
    private $cookie_file = __DIR__ . "/../storage/douyin-cookie.txt";

    public function find_video_id($url)
    {
        $url = unshorten($url, $this->enable_proxies);
        $url = strtok($url, '?');
        $last_char = substr($url, -1);
        if ($last_char == "/") {
            $url = substr($url, 0, -1);
        }
        $arr = explode("/", $url);
        return end($arr);
    }

    private function download_video($url, $file_path)
    {
        $fp = fopen($file_path, 'w+');
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookie_file);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $this->cookie_file);
        curl_setopt($ch, CURLOPT_REFERER, "https://www.tiktok.com/");
        curl_setopt($ch, CURLOPT_USERAGENT, _REQUEST_USER_AGENT);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'authority: v3-dy-o.zjcdn.com',
            'accept: */*',
            'sec-fetch-site: cross-site',
            'sec-fetch-mode: no-cors',
            'sec-fetch-dest: video',
            'referer: https://www.iesdouyin.com/'
        ));
        curl_exec($ch);
        curl_close($ch);
        fclose($fp);
    }

    private function download_video_nwm($url, $file_path)
    {
        $fp = fopen($file_path, 'w+');
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'User-Agent: Mozilla/5.0 (iPhone; CPU iPhone OS 13_2_3 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/13.0.3 Mobile/15E148 Safari/604.1',
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8',
            'Accept-Language: zh-CN,zh;q=0.8,en-US;q=0.5,en;q=0.3',
            'Connection: keep-alive'
        ));
        curl_exec($ch);
        curl_close($ch);
        fclose($fp);
    }

    public function media_info($url)
    {
        $video_id = $this->find_video_id($url);
        if (empty($video_id)) {
            return false;
        }
        $video_info = $this->get_video_info($video_id);
        if (empty($video_info)) {
            return false;
        }
        $video["title"] = $video_info["item_list"][0]["desc"];
        $video["source"] = "douyin";
        $video["thumbnail"] = $video_info["item_list"][0]["video"]["cover"]["url_list"][0];
        $video["duration"] = format_seconds($video_info["item_list"][0]["video"]["duration"] / 1000);
        $i = 0;
        /*
        $video_url = $this->get_video_url($video_info["item_list"][0]["video"]["play_addr"]["url_list"][0]);
        if (!empty($video_url)) {
            $track_id = rand(0, 4);
            $cache_file = __DIR__ . "/../storage/temp/douyin-" . $track_id . ".mp4";
            $website_url = json_decode(option("general_settings"), true)["url"];
            $this->download_video($video_url, $cache_file);
            $video["links"][$i]["url"] = $website_url . "/system/storage/temp/douyin-" . $track_id . ".mp4";
            $video["links"][$i]["type"] = "mp4";
            $video["links"][$i]["quality"] = $video_info["item_list"][0]["video"]["ratio"];
            $video["links"][$i]["bytes"] = filesize($cache_file);
            $video["links"][$i]["size"] = format_size($video["links"][$i]["bytes"]);
            $video["links"][$i]["mute"] = false;
            $i++;
        }
        */
        if (!empty($video_info["item_list"][0]["video"]["vid"])) {
            $video_url = "https://aweme.snssdk.com/aweme/v1/play/?video_id=" . $video_info["item_list"][0]["video"]["vid"] . "&ratio=default&line=0";
            $track_id = rand(0, 4);
            $cache_file = __DIR__ . "/../storage/temp/douyin-" . $track_id . ".mp4";
            $website_url = json_decode(option("general_settings"), true)["url"];
            $this->download_video_nwm($video_url, $cache_file);
            $video["links"][$i]["url"] = $website_url . "/system/storage/temp/douyin-" . $track_id . ".mp4";
            $video["links"][$i]["type"] = "mp4";
            $video["links"][$i]["quality"] = $video_info["item_list"][0]["video"]["ratio"];
            $video["links"][$i]["bytes"] = filesize($cache_file);
            $video["links"][$i]["size"] = format_size($video["links"][$i]["bytes"]);
            $video["links"][$i]["mute"] = false;
            $i++;
        }
        $music_url = $video_info["item_list"][0]["music"]["play_url"]["uri"];
        if (!empty($music_url) && !empty($video["links"][0])) {
            $video["links"][$i]["url"] = $music_url;
            $video["links"][$i]["type"] = "mp3";
            $video["links"][$i]["bytes"] = get_file_size($music_url, $this->enable_proxies, false);
            $video["links"][$i]["size"] = format_size($video["links"][$i]["bytes"]);
            $video["links"][$i]["quality"] = "128kbps";
            $video["links"][$i]["mute"] = false;
        }
        return $video;
    }

    function get_video_url($player_url)
    {
        return $player_url;
        $headers = get_headers($player_url, 1);
        return $headers["location"];
    }

    function get_video_info($video_id)
    {
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://www.iesdouyin.com/web/api/v2/aweme/iteminfo/?item_ids=" . $video_id,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
        ));
        $response = curl_exec($curl);
        curl_close($curl);
        return json_decode($response, true);
    }
}