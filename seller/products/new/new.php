<?php
    declare(strict_types = 1);
    require_once('../../../lib/utils.inc.php');
    require_once('../../../lib/errors.inc.php');
    require_once('../../../lib/validation.inc.php');
    require_once('../../../lib/auth.inc.php');
    require_once('../../../lib/database/product.inc.php');
    $settings = new Settings();
    try {
        $userId = Auth::protect(['seller']);
        $connection = connect($settings);
        $validator = new Validator($_POST);
        $productType = $validator->getProductType('product-type');
        switch($productType) {
            case ProductType::CONSOLE: $product = Console::fromForm($validator); break;
            case ProductType::VIDEOGAME: $product = Videogame::fromForm($validator); break;
            case ProductType::ACCESSORY: $product = Accessory::fromForm($validator); break;
        }
        $product->insert($connection);
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
        <title>Games And Go - Product Created</title>
        <link rel="stylesheet" href="https://pyrix25633.github.io/css/style.css">
        <link rel="stylesheet" href="https://pyrix25633.github.io/css/roboto-condensed-off.css">
        <link rel="stylesheet" href="https://pyrix25633.github.io/css/compact-mode-off.css">
        <link rel="stylesheet" href="https://pyrix25633.github.io/css/sharp-mode-off.css">
        <link rel="icon" href="../../../img/games-and-go.svg" type="image/png">
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
            <h2>Product Creation Succesful</h2>
            <a href="../../">Home</a>
        </div>
    </body>
</html>