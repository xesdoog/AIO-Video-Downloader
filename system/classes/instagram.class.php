<?php

class instagram
{
    public $enable_proxies = false;
    public $url;
    public static $COOKIE_FILE = __DIR__ . "/../storage/ig-cookie.txt";
    public static $USER_AGENT = _REQUEST_USER_AGENT;
    private $post_page;

    function media_info($url)
    {
        $url = $url = strtok($url, '?');
        if (substr($url, -1) != '/') {
            $url .= "/";
        }
        $this->post_page = $this->url_get_contents($url . "embed/captioned/", $this->enable_proxies);
        if (strpos($this->post_page, "WatchOnInstagram")) {
            return $this->media_info_legacy($url);
        }
        preg_match_all('/window.__additionalDataLoaded\(\'extra\',(.*?)\);<\/script>/', $this->post_page, $matches);
        if (!isset($matches[1][0]) || empty($matches[1][0])) {
            return false;
        }
        $data = json_decode($matches[1][0], true);
        $video["links"] = array();
        if (!isset($data["shortcode_media"])) {
            preg_match_all('/<img class="EmbeddedMediaImage" alt=".*" src="(.*?)"/', $this->post_page, $matches);
            if (isset($matches[1][0]) != "") {
                $video["title"] = get_string_between($this->post_page, '<img class="EmbeddedMediaImage" alt="', '"');
                $video["source"] = "instagram";
                $video["thumbnail"] = $matches[1][0];
                $media_url = html_entity_decode($matches[1][0]);
                $bytes = get_file_size($media_url, $this->enable_proxies, false);
                array_push($video["links"], [
                    "url" => $media_url,
                    "type" => "jpg",
                    "bytes" => $bytes,
                    "size" => format_size($bytes),
                    "quality" => "HD",
                    "mute" => 0
                ]);
            } else {
                return false;
            }
        } else {
            $video["title"] = $data["shortcode_media"]["edge_media_to_caption"]["edges"][0]["node"]["text"] ?? "";
            if (empty($video["title"]) && isset($data["shortcode_media"]["owner"]["username"]) != "") {
                $video["title"] = "Instagram Post from " . $data["shortcode_media"]["owner"]["username"];
            } else {
                $video["title"] = "Instagram Post";
            }
            //$video["data"] = $data;
            $video["source"] = "instagram";
            $video["thumbnail"] = $data["shortcode_media"]["display_resources"][0]["src"];
            if ($data['shortcode_media']['__typename'] == "GraphImage") {
                $images_data = $data['shortcode_media']['display_resources'];
                $length = count($images_data);
                $bytes = get_file_size($images_data[$length - 1]['src'], $this->enable_proxies, false);
                array_push($video["links"], [
                    "url" => $images_data[$length - 1]['src'],
                    "type" => "jpg",
                    "bytes" => $bytes,
                    "size" => format_size($bytes),
                    "quality" => "HD",
                    "mute" => 0
                ]);
            } else {
                if ($data['shortcode_media']['__typename'] == "GraphSidecar") {
                    $multiple_data = $data['shortcode_media']['edge_sidecar_to_children']['edges'];
                    foreach ($multiple_data as $media) {
                        if ($media['node']['is_video'] == "true") {
                            $media_url = $media['node']['video_url'];
                            $type = "mp4";
                        } else {
                            $length = count($media['node']['display_resources']);
                            $media_url = $media['node']['display_resources'][$length - 1]['src'];
                            $type = "jpg";
                        }
                        $bytes = get_file_size($media_url, $this->enable_proxies, false);
                        array_push($video["links"], [
                            "url" => $media_url,
                            "type" => $type,
                            "bytes" => $bytes,
                            "size" => format_size($bytes),
                            "quality" => "HD",
                            "mute" => 0
                        ]);
                    }
                } else {
                    if ($data['shortcode_media']['__typename'] == "GraphVideo") {
                        $bytes = get_file_size($data['shortcode_media']['video_url'], $this->enable_proxies, false);
                        array_push($video["links"], [
                            "url" => $data['shortcode_media']['video_url'],
                            "type" => "mp4",
                            "bytes" => $bytes,
                            "size" => format_size($bytes),
                            "quality" => "HD",
                            "mute" => 0
                        ]);
                    }
                }
            }
        }
        return $video;
    }

    private static function save_media($file, $url)
    {
        file_put_contents($file, self::get_media($url));
        return $file;
    }

    private static function get_media($url)
    {
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false,
        ));
        $response = curl_exec($curl);
        curl_close($curl);
        return $response;
    }

    private static function get_size($url)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_NOBODY, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 0);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 3);
        curl_setopt($ch, CURLOPT_USERAGENT, _REQUEST_USER_AGENT);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_exec($ch);
        $filesize = curl_getinfo($ch, CURLINFO_CONTENT_LENGTH_DOWNLOAD);
        curl_close($ch);
        return format_size($filesize);
    }

    function media_info_legacy($url)
    {
        /*
        if (strpos($url, "https://www.instagram.com/p") === 0 || strpos($url, "https://instagram.com/p") === 0) {
            $url = str_replace($url, "http", "https");
        }
        */
        $this->post_page = $this->url_get_contents($url, $this->enable_proxies);
        $media_info = $this->media_data($this->post_page);
        $video["title"] = $this->get_title($this->post_page);
        $video["source"] = "instagram";
        $video["thumbnail"] = $this->get_thumbnail($this->post_page);
        $i = 0;
        foreach ($media_info["links"] as $link) {
            if (!isset($link["type"])) {
                $link["type"] = null;
            }
            switch ($link["type"]) {
                case "video":
                    $video["links"][$i]["url"] = $link["url"];
                    $video["links"][$i]["type"] = "mp4";
                    $video["links"][$i]["size"] = get_file_size($video["links"][$i]["url"], $enable_proxies = false);
                    $video["links"][$i]["quality"] = "HD";
                    $video["links"][$i]["mute"] = "no";
                    $i++;
                    break;
                case "image":
                    $video["links"][$i]["url"] = $link["url"];
                    $video["links"][$i]["type"] = "jpg";
                    $video["links"][$i]["size"] = get_file_size($video["links"][$i]["url"], $enable_proxies = false);
                    $video["links"][$i]["quality"] = "HD";
                    $video["links"][$i]["mute"] = "yes";
                    $i++;
                    break;
                default:
                    break;
            }
        }
        return $video;
    }

    function media_info_beta($url)
    {
        $this->post_page = $this->url_get_contents($url, $this->enable_proxies);
        $video["title"] = $this->get_title($this->post_page);
        $video["source"] = "instagram";
        //$video["thumbnail"] = $this->get_thumbnail($this->post_page);
        $video["thumbnail"] = get_string_between($this->post_page, '"display_url":"', '"');
        $video["thumbnail"] = str_replace("\u0026", "&", $video["thumbnail"]);
        $video["links"][0]["url"] = $this->getVideoUrl();
        $video["links"][0]["type"] = "mp4";
        $video["links"][0]["size"] = get_file_size($video["links"]["0"]["url"], $enable_proxies = false);
        $video["links"][0]["quality"] = "HD";
        $video["links"][0]["mute"] = "no";
        return $video;
    }

    function getPostShortcode($url)
    {
        if (substr($url, -1) != '/') {
            $url .= '/';
        }
        preg_match('/\/(p|tv)\/(.*?)\//', $url, $output);
        return ($output['2'] ?? '');
    }

    function getVideoUrl($postShortcode = "")
    {
        //$pageContent = $this->url_get_contents('https://www.instagram.com/p/' . $postShortcode);
        preg_match_all('/"video_url":"(.*?)",/', $this->post_page, $out);
        if (!empty($out[1][0])) {
            return str_replace('\u0026', '&', $out[1][0]);
        } else {
            return null;
        }
    }

    function url_get_contents($url, $enable_proxies = false)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        curl_setopt($ch, CURLOPT_USERAGENT, self::$USER_AGENT);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        if (file_exists(self::$COOKIE_FILE)) {
            curl_setopt($ch, CURLOPT_COOKIEFILE, self::$COOKIE_FILE);
            //curl_setopt($ch, CURLOPT_COOKIEJAR, self::$COOKIE_FILE);
        }
        if ($enable_proxies) {
            if (!empty($_SESSION["proxy"] ?? null)) {
                $proxy = $_SESSION["proxy"];
            } else {
                $proxy = get_proxy();
                $_SESSION["proxy"] = $proxy;
            }
            curl_setopt($ch, CURLOPT_PROXY, $proxy['ip'] . ":" . $proxy['port']);
            if (!empty($proxy['username']) && !empty($proxy['password'])) {
                curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxy['username'] . ":" . $proxy['password']);
            }
            $chunkSize = 1000000;
            curl_setopt($ch, CURLOPT_TIMEOUT, (int)ceil(3 * (round($chunkSize / 1048576, 2) / (1 / 8))));
        }
        $data = curl_exec($ch);
        curl_close($ch);
        return $data;
    }

    function media_data($post_page)
    {
        preg_match_all("/window.__additionalDataLoaded.'.{5,}',(.*).;/", $post_page, $matches);
        if (!$matches) {
            return false;
        } else {
            preg_match_all("/window.__additionalDataLoaded.'.{5,}',(.*).;/", $post_page, $matches);
            preg_match_all('/<script type="text\/javascript">window._sharedData = (.*?);<\/script>/', $post_page, $output);
            if (isset($matches[1][0]) != '') {
                $json = $matches[1][0];
            } else if (isset($output[1][0]) != '') {
                $json = $output[1][0];
            }
            $data = json_decode($json, true);
            if ($data['graphql']['shortcode_media']['__typename'] == "GraphImage") {
                $imagesdata = $data['graphql']['shortcode_media']['display_resources'];
                $length = count($imagesdata);
                $media_info['links'][0]['type'] = 'image';
                $media_info['links'][0]['url'] = $imagesdata[$length - 1]['src'];
                $media_info['links'][0]['status'] = 'success';
            } else {
                if ($data['graphql']['shortcode_media']['__typename'] == "GraphSidecar") {
                    $counter = 0;
                    $multipledata = $data['graphql']['shortcode_media']['edge_sidecar_to_children']['edges'];
                    foreach ($multipledata as &$media) {
                        if ($media['node']['is_video'] == "true") {
                            $media_info['links'][$counter]["url"] = $media['node']['video_url'];
                            $media_info['links'][$counter]["type"] = 'video';
                        } else {
                            $length = count($media['node']['display_resources']);
                            $media_info['links'][$counter]["url"] = $media['node']['display_resources'][$length - 1]['src'];
                            $media_info['links'][$counter]["type"] = 'image';
                        }
                        $counter++;
                        $media_info['type'] = 'media';
                    }
                    $media_info['status'] = 'success';
                } else {
                    if ($data['graphql']['shortcode_media']['__typename'] == "GraphVideo") {
                        $videolink = $data['graphql']['shortcode_media']['video_url'];
                        $media_info['links'][0]['type'] = 'video';
                        $media_info['links'][0]['url'] = $videolink;
                        $media_info['links'][0]['status'] = 'success';
                    } else {
                        $media_info['links']['status'] = 'fail';
                    }
                }
            }
            return $media_info;
        }
    }

    function get_thumbnail($post_page)
    {
        preg_match_all("/window.__additionalDataLoaded.'.{5,}',(.*).;/", $post_page, $matches);
        if (!$matches) {
            return false;
        }
        $json = $matches[1][0];
        $data = json_decode($json, true)["graphql"];
        return $data["shortcode_media"]['display_resources'][0]["src"];
    }

    function get_title($curl_content)
    {
        if (preg_match_all('@<title>(.*?)</title>@si', $curl_content, $match)) {
            return $match[1][0];
        }
    }
}