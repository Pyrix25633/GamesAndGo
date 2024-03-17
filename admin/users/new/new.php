<?php
    declare(strict_types = 1);
    require_once('../../../lib/utils.inc.php');
    require_once('../../../lib/errors.inc.php');
    require_once('../../../lib/validation.inc.php');
    require_once('../../../lib/auth.inc.php');
    require_once('../../../lib/database/product.inc.php');
    require_once('../../../lib/database/user.inc.php');
    try {
        $connection = connect();
        Auth::protect($connection, ['admin']);
        $validator = new Validator($_POST);
        $userType = $validator->getUserType('user-type');
        switch($userType) {
            case UserType::CUSTOMER: $user = Customer::fromForm($validator); break;
            case UserType::SELLER: $user = SELLER::fromForm($validator); break;
            case UserType::ADMIN: $user = ADMIN::fromForm($validator); break;
        }
        $user->insert($connection);
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
        <title>Games And Go - User Created</title>
        <link rel="stylesheet" href="https://pyrix25633.github.io/css/style.css">
        <link rel="stylesheet" href="https://pyrix25633.github.io/css/roboto-condensed-off.css">
        <link rel="stylesheet" href="https://pyrix25633.github.io/css/compact-mode-off.css">
        <link rel="stylesheet" href="https://pyrix25633.github.io/css/sharp-mode-off.css">
        <link rel="icon" href="../../../img/games-and-go.svg" type="image/svg">
    </head>
    <body>
        <nav id="navbar">
            <div class="container navbar">
                <span class="title app-color">Games</span>
                <span class="title">And Go</span>
                <img id="icon" src="../../../img/games-and-go.svg" alt="Games And Go Icon">
            </div>
        </nav>
        <div class="panel box">
            <h2>User Creation Succesful</h2>
            <a href="../index.php">Back</a>
            <a href="../../">Home</a>
        </div>
    </body>
</html>