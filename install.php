<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>All in One Video Downloader Installer</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.0/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body, html {
            height: 100%
        }

        body {
            display: -ms-flexbox;
            display: flex;
            -ms-flex-align: center;
            align-items: center;
            padding-top: 40px;
            padding-bottom: 40px;
            background-color: #f5f5f5
        }

        .form-signin {
            width: 100%;
            max-width: 500px;
            padding: 15px;
            margin: auto
        }

        .form-signin .checkbox {
            font-weight: 400
        }

        .form-signin .form-control {
            position: relative;
            box-sizing: border-box;
            height: auto;
            padding: 10px;
            font-size: 16px
        }

        .form-signin .form-control:focus {
            z-index: 2
        }

        .form-signin input[type=email] {
            margin-bottom: -1px;
            border-bottom-right-radius: 0;
            border-bottom-left-radius: 0
        }

        .form-signin input[type=password] {
            margin-bottom: 10px;
            border-top-left-radius: 0;
            border-top-right-radius: 0
        }
    </style>
</head>
<body class="text-center">
<form method="post" class="form-signin">
    <?php
    include(__DIR__ . "/admin/functions.php");
    $version_code = "MTU0MTIxLjkzODAxLjEzODExNC42MzUwMA==6";
    $host = gethostbyname(gethostname());
    $server_ip = ($host != "") ? $host : "127.0.0.1";
    $must_be_writeable = array(
        "/db.sql",
        "/system/db.php",
        "/system/storage/",
        "/system/storage/fb-cookie.txt",
        "/system/storage/ig-cookie.txt",
        "/system/storage/soundcloud-api-key.json",
        "/robots.txt"
    );
    foreach ($must_be_writeable as $directory) {
        if (!is_writable(__DIR__ . $directory)) {
            echo '<p class="alert alert-warning">' . $directory . ' must be writeable.</p>';
            exit;
        }
    }
    if (!defined('PHP_MAJOR_VERSION') && PHP_MAJOR_VERSION > 5) {
        echo '<p class="alert alert-danger">PHP 5 not supported. Please upgrade to 7.0 or higher.</p>';
    }
    if (@$_POST) {
        try {
            $dbh = new pdo('mysql:host=' . $_POST["database_host"] . ';dbname=' . $_POST["database_name"],
                $_POST["database_user"],
                $_POST["database_password"],
                array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION));
        } catch (PDOException $ex) {
            die('<p class="alert alert-warning">Could not connect to the database. Please check credentials.</p>');
        }
        if (!filter_var($server_ip, FILTER_VALIDATE_IP)) {
            $server_ip = "127.0.0.1";
        }
        $user_ip = get_user_ip();
        if (!filter_var($user_ip, FILTER_VALIDATE_IP)) {
            $user_ip = "127.0.0.1";
        }

        $site_url = $_POST["url"];
		$lang = $_POST["language"];
		$template = 'material';
		$purchase_code = $_POST["purchase_code"];
		$title = $_POST["title"];
		$description = $_POST["description"];
		$author = $_POST["author"];
		$email = $_POST["email"];
		$fingerprint = create_fingerprint(str_ireplace("www.", "", parse_url($site_url, PHP_URL_HOST)), $purchase_code);

        $response = array('url' => $site_url, 'language' => $lang, 'template' => $template, 'purchase_code' => $purchase_code, 'version' => '1.7.0', 'title' => $title, 'description' => $description, 'author' => $author, 'email' => $email, 'download_suffix' => null, 'fingerprint' => $fingerprint);
		$response = json_encode($response);
		
            $json = $response;
            $response = json_decode($response, true);
            if (!isset($response["error"])) {
                if ($_POST["password"] != $_POST["password_2"]) {
                    echo '<p class="alert alert-danger">Passwords does not same!</p>';
                    die();
                }
                $admin_password = sha1($_POST["password"]);
                $original_db_config = file_get_contents(__DIR__ . "/system/db.php");
                $db_config = str_replace("{{host}}", $_POST["database_host"], $original_db_config);
                $db_config = str_replace("{{name}}", $_POST["database_name"], $db_config);
                $db_config = str_replace("{{user}}", $_POST["database_user"], $db_config);
                $db_config = str_replace("{{pass}}", $_POST["database_password"], $db_config);
                file_put_contents(__DIR__ . "/system/db.php", $db_config);
                $db = new PDO("mysql:host=" . $_POST["database_host"] . ";dbname=" . $_POST["database_name"] . ";charset=utf8mb4", $_POST["database_user"], $_POST["database_password"]);
                $sql = file_get_contents(__DIR__ . '/db.sql');
                $sql = str_replace("{{general_settings}}", $db->quote($json), $sql);
                $sql = str_replace("{{admin_email}}", $_POST["email"], $sql);
                $sql = str_replace("{{admin_pass}}", $admin_password, $sql);
                $sql = str_replace("{{admin_name}}", $_POST["author"], $sql);
                file_put_contents(__DIR__ . "/db.sql", $sql);
                $robots = file_get_contents(__DIR__ . "/robots.txt");
                $robots = str_replace("{{website_url}}", $_POST["url"], $robots);
                file_put_contents(__DIR__ . "/robots.txt", $robots);
                $qr = $db->exec($sql);
                echo '<p class="alert alert-success">Installation completed! <a href="' . $_POST["url"] . '">Go to website</a> <a href="' . $_POST["url"] . '/admin">Go to admin panel</a> </p>';
                echo '<p class="alert alert-warning">Do not forget to delete "install.php" and "db.sql" file!</p>';
                if (file_exists(__DIR__ . '/upload.zip')) {
                    echo '<p class="alert alert-danger">Do not forget to delete "upload.zip"!</p>';
                }
            } else {
                echo '<p class="alert alert-danger">' . $response["error"] . '</p>';
            }
        
    }
    ?>
    <img class="mb-4" src="assets/img/favicon.png" alt="all in one video downloader" width="100" height="100">
    <h1 class="h3 mb-3 font-weight-normal">AIO Video Downloader Installation</h1>
    <p>Version 1.14.0</p>
    <div class="input-group">
        <input name="url" type="url" class="form-control" placeholder="Website URL" required autofocus>
    </div>
    <hr>
    <div class="input-group">
        <input name="author" type="text" class="form-control" placeholder="Website Owner" required>
        <input name="email" type="email" class="form-control" placeholder="Owner's E-mail" required>
    </div>
    <div class="input-group">
        <input name="password" type="password" class="form-control" placeholder="Admin Panel Password" required>
        <input name="password_2" type="password" class="form-control" placeholder="Confirm Password" required>
    </div>
    <hr>
    <input name="purchase_code" type="text" class="form-control" value="NullJungle" placeholder="Purchase Code" required>
    <hr>
    <input name="database_host" type="text" class="form-control" placeholder="Database Host" required>
    <input name="database_name" type="text" class="form-control" placeholder="Database Name" required>
    <input name="database_user" type="text" class="form-control" placeholder="Database User" required>
    <input name="database_password" type="password" class="form-control" placeholder="Database Password" required>
    <input name="title" type="hidden" class="form-control" placeholder="Website Title"
           value="All in One Video Downloader" required>
    <input name="description" type="hidden" value="">
    <input name="language" type="hidden" value="en">
    <input name="template" type="hidden" value="material">
    <input name="tracking" type="hidden" value="on">
    <input name="auto-update" type="hidden" value="on">
    <div class="form-check">
        <input class="form-check-input" type="checkbox" id="eulaCheck" required checked disabled>
        <label class="form-check-label" for="eulaCheck">
            I agree End User License Agreement
        </label>
    </div>
    <input name="checksum" type="hidden" value="<?php echo sha1_file(__DIR__ . "/system/action.php") ?>">
    <button class="btn btn-lg btn-primary btn-block" type="submit">Install</button>
    <p class="mt-5 mb-3 text-muted">&copy; 2018</p>
</form>
</body>
</html>