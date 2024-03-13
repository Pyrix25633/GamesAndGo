<?php
    declare(strict_types = 1);
    require_once('../../../lib/errors.inc.php');
    require_once('../../../lib/auth.inc.php');
    try {
        $userId = Auth::protect(['seller']);
    } catch(Response $error) {
        $error->send();
    }
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Games And Go - New Product</title>
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
            <form action="form.php" method="GET">
                <div class="container space-between">
                    <label for="product-type">Product Type:</label>
                    <select name="product-type" id="product-type">
                        <option value="console">Console</option>
                        <option value="videogame">Videogame</option>
                        <option value="accessory">Accessory</option>
                        <option value="guide">Guide</option>
                    </select>
                </div>
                <div class="container">
                    <button type="submit">Next</button>
                </div>
            </form>
        </div>
    </body>
</html>