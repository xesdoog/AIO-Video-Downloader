<?php

class bandcamp
{
    public $enable_proxies = false;

    public function media_info($url)
    {
        $web_page = url_get_contents($url, $this->enable_proxies);
        $data = json_decode(get_string_between($web_page, '<script type="application/ld+json">', '</script>'), true);
        if (!isset($data["additionalProperty"]) || empty($data["additionalProperty"])) {
            return false;
        }
        $track["source"] = "bandcamp";
        $track["title"] = $data["name"] . " " . $data["byArtist"]["name"];
        $track["thumbnail"] = get_string_between($web_page, 'property="og:image" content="', '">');
        $track["links"] = [];
        if ($data["@type"] == "MusicAlbum") {
            if (isset($data["track"]["itemListElement"]) != "") {
                for ($i = 0; $i < count($data["track"]["itemListElement"]); $i++) {
                    $item = $data["track"]["itemListElement"][$i]["item"];
                    $property = [];
                    for ($j = 0; $j < count($item["additionalProperty"]); $j++) {
                        $property[$item["additionalProperty"][$j]["name"]] = $item["additionalProperty"][$j]["value"];
                    }
                    if (isset($property["file_mp3-128"]) != "") {
                        $file_size = get_file_size($property["file_mp3-128"], $this->enable_proxies, false);
                        array_push($track["links"], [
                            "url" => $property["file_mp3-128"],
                            "type" => "mp3",
                            "bytes" => $file_size,
                            "size" => format_size($file_size),
                            "quality" => "128kbps",
                            "mute" => false
                        ]);
                    }
                }
            }
        } else {
            $property = [];
            for ($i = 0; $i < count($data["additionalProperty"]); $i++) {
                $property[$data["additionalProperty"][$i]["name"]] = $data["additionalProperty"][$i]["value"];
            }
            $track["duration"] = format_seconds($property["duration_secs"]);
            $file_size = get_file_size($property["file_mp3-128"], $this->enable_proxies, false);
            array_push($track["links"], [
                "url" => $property["file_mp3-128"],
                "type" => "mp3",
                "bytes" => $file_size,
                "size" => format_size($file_size),
                "quality" => "128kbps",
                "mute" => false
            ]);
        }
        return $track;
    }
}