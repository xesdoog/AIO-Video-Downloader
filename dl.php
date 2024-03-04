<?php
require_once __DIR__ . "/system/config.php";
set_time_limit(0);
ini_set("zlib.output_compression", "Off");
//apache_setenv('no-gzip', '1');
if (!empty($_GET["source"]) && !empty($_GET["dl"])) {
    $current_result = $_SESSION['result'][$_SESSION["token"]];
    $i = (int)base64_decode($_GET["dl"]);
    $parsed_remote_url = parse_url($current_result["links"][$i]["url"]);
    $remote_domain = str_ireplace("www.", "", $parsed_remote_url["host"] ?? "");
    $local_domain = str_ireplace("www.", "", parse_url($config["url"], PHP_URL_HOST));
    if (filter_var($current_result["links"][$i]["url"] ?? "", FILTER_VALIDATE_URL)) {
        if (!empty($config["download_suffix"])) {
            $config["download_suffix"] = "-" . $config["download_suffix"];
        }
        session_write_close();
        $chunked_sources = ["periscope", "gaana", "soundcloud", "puhutv", "blutv"];
        switch (true) {
            case($_GET["source"] == "bilibili"):
                force_download_legacy($current_result["links"][$i]["url"], $current_result["title"] . $config["download_suffix"], $current_result["links"][$i]["type"], $current_result["links"][$i]["bytes"], false);
                break;
            case($_GET["source"] != "ok.ru" && isset($config["bandwidth_saving"]) != "" || $remote_domain == "dailymotion.aiovideodl.ml"):
                redirect($current_result["links"][$i]["url"]);
                break;
            case($local_domain == $remote_domain):
                if (in_array($_GET["source"], $chunked_sources)) {
                    $paths = explode("/", $parsed_remote_url["path"]);
                    $file_name = end($paths);
                    $chunks = json_decode(file_get_contents(__DIR__ . "/system/storage/temp/" . $file_name), true);
                    force_download_chunks($chunks, $current_result["title"] . $config["download_suffix"], $current_result["links"][$i]["type"], $current_result["links"][$i]["bytes"]);
                } else {
                    force_download_legacy(__DIR__ . $parsed_remote_url["path"], $current_result["title"] . $config["download_suffix"], $current_result["links"][$i]["type"], $current_result["links"][$i]["bytes"], -1);
                }
                break;
            default:
                $referer = "";
                if ($_GET["source"] == "mxtakatak") {
                    $referer = "https://www.mxtakatak.com/";
                }
                force_download($current_result["links"][$i]["url"], $current_result["title"] . $config["download_suffix"], $current_result["links"][$i]["type"], $current_result["links"][$i]["bytes"], $referer);
                break;
        }
    } else {
        http_response_code("404");
    }
} else {
    http_response_code("404");
}