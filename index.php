<?php
    require_once('./lib/utils.inc.php');
    require_once('./lib/errors.inc.php');
    require_once('./lib/auth.inc.php');
    try {
        $type = null;
        Auth::protect(['customer', 'seller', 'admin'], $type);
    } catch(Response $_) {
    }
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Games And Go</title>
        <link rel="stylesheet" href="https://pyrix25633.github.io/css/style.css">
        <link rel="stylesheet" href="https://pyrix25633.github.io/css/error.css">
        <link rel="stylesheet" href="https://pyrix25633.github.io/css/roboto-condensed-off.css">
        <link rel="stylesheet" href="https://pyrix25633.github.io/css/compact-mode-off.css">
        <link rel="stylesheet" href="https://pyrix25633.github.io/css/sharp-mode-off.css">
        <link rel="icon" href="./img/games-and-go.svg" type="image/png">
    </head>
    <body>
        <nav id="navbar">
            <div class="container navbar">
                <span class="title app-color">Games</span>
                <span class="title">And Go</span>
                <img id="icon" src="./img/games-and-go.svg" alt="Games And Go Icon">
            </div>
        </nav>
        <div class="box panel bottom-margin">
            <a href="./register">Register Here</a>
            <a href="./login">Login Here</a>
            <?php
                if($type != null) echo '<a href="./' . $type . '">Home</a>';
                else echo '<a href="./customer/products">View Products without loggin in</a>';
            ?>
        </div>
    </body>
</html>