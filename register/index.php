<?php
    declare(strict_types = 1);
    require_once('../lib/utils.inc.php');
    require_once('../lib/errors.inc.php');
    require_once('../lib/database/user.inc.php');
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
        <link rel="icon" href="../img/games-and-go.svg" type="image/png">
    </head>
    <body>
        <nav id="navbar">
            <div class="container navbar">
                <span class="title app-color">Games</span>
                <span class="title">And Go</span>
                <img id="icon" src="../img/games-and-go.svg" alt="Games And Go Icon">
            </div>
        </nav>
        <div class="panel box bottom-margin">
            <form action="./register.php" method="POST">
                <?php echo Customer::formNew('../'); ?>
                <div class="container section">
                    <h2>Submit</h2>
                    <img class="icon" src="../img/submit.svg" alt="Submit Icon">
                </div>
                <div class="container">
                    <button type="submit">Register</button>
                </div>
            </form>
        </div>
    </body>
</html>