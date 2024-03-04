<?php
if (isset($_SESSION["logged"]) === true) {
    $config = json_decode(option(), true);
    if (!empty($_GET["id"]) && is_numeric($_GET["id"]) === true) {
        $proxy_id = (int)$_GET["id"];
        database::delete_proxy($proxy_id);
    }
    printf('<script>window.alert("The proxy deleted."); window.location.href = "%s"</script>', $config["url"] . "/admin/?view=proxy");
} else {
    http_response_code(403);
}