<?php
    declare(strict_types = 1);
    require_once('../lib/utils.inc.php');
    require_once('../lib/errors.inc.php');
    require_once('../lib/validation.inc.php');
    require_once('../lib/database/user.inc.php');
    require_once('../lib/auth.inc.php');
    try {
        $connection = connect();
        $validator = new Validator($_POST);
        $username = $validator->getNonEmptyString('username');
        $password = $validator->getNonEmptyString('password');
        User::login($connection, $username, $password);
    } catch(Response $error) {
        $connection->close();
        $error->send();
    }
    $connection->close();
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Games And Go - Register</title>
        <link rel="stylesheet" href="https://pyrix25633.github.io/css/style.css">
        <link rel="stylesheet" href="https://pyrix25633.github.io/css/roboto-condensed-off.css">
        <link rel="stylesheet" href="https://pyrix25633.github.io/css/compact-mode-off.css">
        <link rel="stylesheet" href="https://pyrix25633.github.io/css/sharp-mode-off.css">
    </head>
    <body>
        <nav id="navbar">
            <div class="container navbar">
                <span class="title app-color">Games</span>
                <span class="title">And Go</span>
                <img id="icon" src="../img/games-and-go.svg" alt="Games And Go Icon">
            </div>
        </nav>
        <div class="panel box">
            <h2>Login Succesful</h2>
            <span class="text">You are being redirected</span>
        </div>
    </body>
</html>