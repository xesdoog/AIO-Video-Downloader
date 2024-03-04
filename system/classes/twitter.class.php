<?php

class twitter
{
    public $enable_proxies = false;
    public $access_token = "AAAAAAAAAAAAAAAAAAAAANRILgAAAAAAnNwIzUejRCOuH5E6I8xnZz4puTs%3D1Zv7ttfk8LF81IUq16cHjhLTvJu4FA33AGWWjCpTnA";
    public $website_url = "";

    public function __construct()
    {
        $this->get_url();
    }

    function get_url()
    {
        $this->website_url = json_decode(database::find("SELECT * FROM options WHERE option_name='general_settings' LIMIT 1")[0]["option_value"], true)["url"];
        return $this->website_url;
    }

    function find_id($url)
    {
        $domain = str_ireplace("www.", "", parse_url($url, PHP_URL_HOST));
        $last_char = substr($url, -1);
        if ($last_char == "/") {
            $url = substr($url, 0, -1);
        }
        switch ($domain) {
            case "twitter.com":
                $arr = explode("/", $url);
                return end($arr);
                break;
            case "mobile.twitter.com":
                $arr = explode("/", $url);
                return end($arr);
                break;
            default:
                $arr = explode("/", $url);
                return end($arr);
                break;
        }
    }

    function get_token()
    {
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $this->website_url . "/assets/js/codebird-cors-proxy/oauth2/token",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => "grant_type=client_credentials",
            CURLOPT_HTTPHEADER => array(
                "Content-Type: application/x-www-form-urlencoded",
                "x-authorization: Basic SzB3OHJsRENCNnpCQjczOVRHdDFCTFkybjozZGs5b3FjN0NRb0k5MGZDeWs5SmNaRXZTODhidmtQMVlIeEkzeWx5b3JsMWNOYUQ1SA=="
            ),
        ));
        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);
        if ($err) {
            return false;
        } else {
            return json_decode($response, true);
        }
    }

    function codebird_request($path)
    {
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $this->website_url . "/assets/js/codebird-cors-proxy/" . $path,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => array(
                "x-authorization: Bearer " . $this->access_token
            ),
        ));
        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);
        if ($err) {
            return false;
        } else {
            return json_decode($response, true);
        }
    }

    function tweet_data($tweet_id)
    {
        return $this->codebird_request("1.1/statuses/show/$tweet_id.json?tweet_mode=extended&include_entities=true");
    }

    function broadcast_data($broadcast_id)
    {
        return $this->codebird_request("1.1/broadcasts/show.json?ids=$broadcast_id&include_events=true");
    }

    function media_info($url)
    {
        //$url = str_replace("mobile.twitter.com", "twitter.com", $url);
        $url = preg_replace('/\?.*/', '', $url);
        $tweet_data = $this->tweet_data($this->find_id($url));
        if (!isset($tweet_data["entities"]["media"]) && isset($tweet_data["entities"]["urls"][0]["expanded_url"]) && is_contains($tweet_data["entities"]["urls"][0]["expanded_url"], "https://twitter.com/i/broadcasts/")) {
            preg_match('/https:\/\/twitter.com\/i\/broadcasts\/(.*)/', $tweet_data["entities"]["urls"][0]["expanded_url"], $matches);
            if (count($matches) < 2) {
                return false;
            }
            $broadcast_id = $matches[1];
            print_r($this->broadcast_data($broadcast_id));
        }
        $data["title"] = $this->clean_title($tweet_data["full_text"]);
        $data["thumbnail"] = $tweet_data["entities"]["media"][0]["media_url_https"];
        $i = 0;
        if (isset($tweet_data["extended_entities"]["media"][0]) != "") {
            foreach ($tweet_data["extended_entities"]["media"][0]["video_info"]["variants"] as $video) {
                if ($video["content_type"] == "video/mp4") {
                    $data["links"][$i]["url"] = $video["url"];
                    $data["links"][$i]["type"] = "mp4";
                    $data["links"][$i]["bytes"] = get_file_size($video["url"], $this->enable_proxies, false);
                    $data["links"][$i]["size"] = format_size($data["links"][$i]["bytes"]);
                    $data["links"][$i]["quality"] = $this->get_quality($data["links"][$i]["url"]);
                    $data["links"][$i]["mute"] = false;
                    $i++;
                }
            }
            $data["source"] = "twitter";
            usort($data["links"], "sort_by_quality");
            return $data;
        } else {
            return false;
        }
    }

    function clean_title($string)
    {
        $title = preg_replace('/(https?:\/\/([-\w\.]+[-\w])+(:\d+)?(\/([\w\/_\.#-]*(\?\S+)?[^\.\s])?).*$)|(\n)/', '', $string);
        return !empty($title) ? $title : $string;
    }

    function get_quality($url)
    {
        preg_match_all('/vid\/(.*?)x(.*?)\//', $url, $output);
        if (!empty($output[2][0])) {
            return $output[2][0] . "p";
        } else {
            return "gif";
        }
    }
}