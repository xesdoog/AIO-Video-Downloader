<?php
include __DIR__ . "/functions.php";
require_once __DIR__ . "/../system/db.php";
session_start();
switch (true) {
    case(isset($_SESSION["logged"]) && $_SESSION["logged"]):
        include(__DIR__ . "/includes/dashboard.php");
        break;
    case(isset($_POST["email"]) && isset($_POST["password"])):
        $config = json_decode(option(), true);
        $email = $_POST["email"];
        $password = $_POST["password"];
        $token = $_POST["token"] ?? "";
        if (!empty($email) && !empty($password) && !empty($token)) {
            $find_user = database::check_password($email, sha1($password));
            if (($find_user["user_level"] ?? null) == 1) {
                $_SESSION["user"] = $find_user;
                $_SESSION["logged"] = true;
                redirect($config["url"] . "/admin");
            } else if ($token == "legacy") {
                if (get_token() == "200") {
                    $_SESSION["user"] = $find_user;
                    $_SESSION["logged"] = true;
                    redirect($config["url"] . "/admin");
                } else {
                    redirect($config["url"] . "/admin/?failed-login=1");
                }
            } else {
                redirect($config["url"] . "/admin/?failed-login=1");
            }
        } else {
            redirect($config["url"] . "/admin/?failed-login=1");
        }
        break;
    default:
        include(__DIR__ . "/includes/login.php");
        break;
}