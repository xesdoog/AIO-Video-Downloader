<?php
if (isset($_SESSION["logged"]) === true) {
    $config = json_decode(option(), true);
    if (!empty($_GET["id"]) && is_numeric($_GET["id"]) === true) {
        $content_id = (int)$_GET["id"];
        database::delete_content($content_id);
    }
    printf('<script>window.alert("The content deleted."); window.location.href = "%s"</script>', $config["url"] . "/admin/?view=content");
} else {
    http_response_code(403);
}