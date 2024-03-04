<?php

class facebook
{
    public $enable_proxies = false;
    public $hide_dash_videos = false;
    public $url;
    public static $COOKIE_FILE = __DIR__ . "/../storage/fb-cookie.txt";
    public static $USER_AGENT = _REQUEST_USER_AGENT;
    public $enable_cookies = false;

    function media_info($url)
    {
        $url = unshorten($this->remove_m($url));
        $web_page = $this->url_get_contents($url);
        file_put_contents(__DIR__ . "/../../fb.html", $web_page);
        preg_match_all('/<script type="application\/ld\+json" nonce="\w{3,10}">(.*?)<\/script><link rel="canonical"/', $web_page, $matches);
        preg_match_all('/"video":{(.*?)},"video_home_www_injection_related_chaining_section"/', $web_page, $matches2);
        preg_match_all('/"playable_url":"(.*?)"/', $web_page, $matches3);
        $video["source"] = "facebook";
        $video["links"] = [];
        if (isset($matches[1][0]) != "") {
            $data = json_decode($matches[1][0], true);
            if (empty($data) || $data["@type"] != "VideoObject") {
                echo "fail 1";
                print_r($matches[1][0]);
                return false;
            }
            $video["title"] = $data["name"];
            $video["thumbnail"] = $data["thumbnailUrl"];
            if (isset($data["contentUrl"]) != "") {
                $bytes = get_file_size($data["contentUrl"], $this->enable_proxies, false);
                array_push($video["links"], [
                    "url" => $data["contentUrl"],
                    "type" => "mp4",
                    "bytes" => $bytes,
                    "size" => format_size($bytes),
                    "quality" => "SD",
                    "mute" => false
                ]);
            }
            $hd_link = get_string_between($web_page, 'hd_src:"', '"');
            if (!empty($hd_link)) {
                $bytes = get_file_size($hd_link, $this->enable_proxies, false);
                array_push($video["links"], [
                    "url" => $hd_link,
                    "type" => "mp4",
                    "bytes" => $bytes,
                    "size" => format_size($bytes),
                    "quality" => "HD",
                    "mute" => false
                ]);
            }
        } else if (isset($matches2[1][0]) != "") {
            $json = "{" . $matches2[1][0] . "}";
            $data = json_decode($json, true);
            if (empty($data) || !isset($data["story"]["attachments"][0]["media"]["__typename"]) || $data["story"]["attachments"][0]["media"]["__typename"] != "Video") {
                echo "fail 2";
                return false;
            }
            $video["title"] = $data["story"]["message"]["text"];
            $video["thumbnail"] = $data["story"]["attachments"][0]["media"]["thumbnailImage"]["uri"];
            if (isset($data["story"]["attachments"][0]["media"]["playable_url"])) {
                $bytes = get_file_size($data["story"]["attachments"][0]["media"]["playable_url"], $this->enable_proxies, false);
                array_push($video["links"], [
                    "url" => $data["story"]["attachments"][0]["media"]["playable_url"],
                    "type" => "mp4",
                    "bytes" => $bytes,
                    "size" => format_size($bytes),
                    "quality" => "SD",
                    "mute" => false
                ]);
            }
            if (isset($data["story"]["attachments"][0]["media"]["playable_url_quality_hd"])) {
                $bytes = get_file_size($data["story"]["attachments"][0]["media"]["playable_url_quality_hd"], $this->enable_proxies, false);
                array_push($video["links"], [
                    "url" => $data["story"]["attachments"][0]["media"]["playable_url_quality_hd"],
                    "type" => "mp4",
                    "bytes" => $bytes,
                    "size" => format_size($bytes),
                    "quality" => "HD",
                    "mute" => false
                ]);
            }
        } else if (isset($matches3[1][0]) != "") {
            preg_match('/"preferred_thumbnail":{"image":{"uri":"(.*?)"/', $web_page, $thumbnail);
            preg_match_all('/"playable_url_quality_hd":"(.*?)"/', $web_page, $hd_link);
            $video["title"] = "Facebook Video";
            $video["thumbnail"] = isset($thumbnail[1]) ? $this->decode_json_text($thumbnail[1]) : "";
            $sd_link = $this->decode_json_text($matches3[1][0]);
            $bytes = get_file_size($sd_link, $this->enable_proxies, false);
            array_push($video["links"], [
                "url" => $sd_link,
                "type" => "mp4",
                "bytes" => $bytes,
                "size" => format_size($bytes),
                "quality" => "SD",
                "mute" => false
            ]);
            if (isset($hd_link[1][0]) != "") {
                $hd_link = $this->decode_json_text($hd_link[1][0]);
                $bytes = get_file_size($hd_link, $this->enable_proxies, false);
                array_push($video["links"], [
                    "url" => $hd_link,
                    "type" => "mp4",
                    "bytes" => $bytes,
                    "size" => format_size($bytes),
                    "quality" => "HD",
                    "mute" => false
                ]);
            }
        } else {
            echo "fail 3";
            return false;
        }
        return $video;
    }

    function url_get_contents($url)
    {
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                'authority: www.facebook.com',
                'cache-control: max-age=0',
                'sec-ch-ua: "Google Chrome";v="89", "Chromium";v="89", ";Not A Brand";v="99"',
                'sec-ch-ua-mobile: ?0',
                'upgrade-insecure-requests: 1',
                'user-agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/89.0.4389.114 Safari/537.36',
                'accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9',
                'sec-fetch-site: none',
                'sec-fetch-mode: navigate',
                'sec-fetch-user: ?1',
                'sec-fetch-dest: document',
                'accept-language: en-GB,en;q=0.9,tr-TR;q=0.8,tr;q=0.7,en-US;q=0.6',
                'cookie: ' . file_get_contents(self::$COOKIE_FILE)
            ),
        ));
        $response = curl_exec($curl);
        curl_close($curl);
        return $response;
    }

    function decode_json_text($text)
    {
        $json = '{"text":"' . $text . '"}';
        $json = json_decode($json, 1);
        return $json["text"];
    }

    function remove_m($url)
    {
        $url = str_replace("m.facebook.com", "www.facebook.com", $url);
        return $url;
    }
}