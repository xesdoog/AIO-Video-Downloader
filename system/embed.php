<?php
require_once("config.php");
$website_domain = str_ireplace("www.", "", parse_url($config["url"], PHP_URL_HOST));
$api_settings = option("api_settings");
$api_settings = json_decode($api_settings, true);
$stmt = isset($_GET["key"]) && !empty($_GET["key"]) && hash_equals($api_settings["key_2"], $_GET["key"]) && hash_equals($config["fingerprint"], create_fingerprint($website_domain, $config["purchase_code"]));
if ($stmt) {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>All in One Video Downloader API</title>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bulma@0.9.1/css/bulma.min.css">
    </head>
    <style>
        <?php

if(isset($_GET["bg_color"]) != "" && filter_var($_GET["bg_color"], FILTER_SANITIZE_STRING) && strlen($_GET["bg_color"]) === 6){
    printf("html{background-color:#%s;}", $_GET["bg_color"]);
}
 ?>
        .download-btn {
            margin-right: 5px;
            margin-bottom: 0px;
            height: 100%;
            text-transform: uppercase;
        }
    </style>
    <body>
    <section class="section">
        <div class="container">
            <div class="field has-addons">
                <div class="control is-expanded">
                    <input class="input is-medium" type="url" id="url" name="url" placeholder="Enter here an URL"
                           style="width: 100%">
                </div>
                <div class="control">
                    <a class="button is-link is-medium" id="submit">
                        Download
                    </a>
                </div>
            </div>
        </div>
    </section>
    <section class="section" id="result">

    </section>
    <template id="download">
        <div class="columns">
            <div class="column is-one-third"><h1 id="video-title" class="title"></h1><img
                        src="//via.placeholder.com/640x320" alt="" title="" class="image"
                        id="thumbnail" style="width: 100%"></div>
            <div class="buttons" id="links">
            </div>
        </div>
    </template>
    </body>
    </html>
    <script>
        function isValidURL(str) {
            var pattern = new RegExp('^(https?:\\/\\/)?' + // protocol
                '((([a-z\\d]([a-z\\d-]*[a-z\\d])*)\\.)+[a-z]{2,}|' + // domain name
                '((\\d{1,3}\\.){3}\\d{1,3}))' + // OR ip (v4) address
                '(\\:\\d+)?(\\/[-a-z\\d%_.~+]*)*' + // port and path
                '(\\?[;&a-z\\d%_.~+=-]*)?' + // query string
                '(\\#[-a-z\\d_]*)?$', 'i'); // fragment locator
            return !!pattern.test(str);
        }

        function getData(url, token) {
            var params = "url=" + encodeURIComponent(url) + "&token=" + token;
            var request = new XMLHttpRequest();
            request.open('POST', 'action.php', false);  // `false` makes the request synchronous
            request.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
            request.send(params);

            if (request.status === 200) {
                return JSON.parse(request.responseText);
            } else {
                alert("Unknown error occurred.");
            }
        }

        document.getElementById("submit").addEventListener("click", (e) => {
            var url = document.getElementById("url").value;
            if (isValidURL(url)) {
                document.getElementById("submit").disabled = true;
                document.getElementById("submit").innerText = "Loading";
                var data = getData(url, "<?php echo $_SESSION["token"]; ?>");
                var template = document.getElementsByTagName("template")[0];
                var result = template.content.cloneNode(true);
                document.getElementById("result").innerHTML = "";
                document.getElementById("result").appendChild(result);
                document.getElementById("video-title").textContent = data["title"];
                document.getElementById("thumbnail").src = data["thumbnail"];
                for (var i = 0; i < data["links"].length; i++) {
                    var title = data["links"][i]["quality"] + "<br>" + data["links"][i]["type"] + "<br>" + data["links"][i]["size"];
                    var colorClass = "is-link";
                    if (data["links"][i]["quality"].includes("kbps")) {
                        colorClass = "is-success";
                    } else if (data["links"][i]["mute"]) {
                        colorClass = "is-info";
                    }
                    var url = "./../dl.php?source=" + data.source + "&dl=" + btoa(i);
                    var button = '<div class=""><a class="button ' + colorClass + ' download-btn" href="' + url +
                        '">' + title + '</a></div>';
                    document.getElementById("links").innerHTML += button;
                }
                document.getElementById("submit").disabled = false;
                document.getElementById("submit").innerText = "Download";
            }
            e.preventDefault();
        });
    </script>
    <?php
}