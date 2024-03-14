<?php
    require_once('./lib/errors.inc.php');
    require_once('./lib/validation.inc.php');
    try {
        $validator = new Validator($_GET);
        $code = $validator->getPositiveInt('code');
        $message = $validator->getNonEmptyString('message');
        try {
            $field = $validator->getNonEmptyString('field');
        } catch(Response $_) {
            $field = null;
        }
    } catch(Response $error) {
        $error->send();
    }
    $text = $code . ' ' . $message;
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Games And Go - <?php echo $text; ?></title>
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
            <img class="error" src="./img/error.svg" alt="Error Icon">
            <h2 class="error"><?php echo $text; ?></h2>
            <?php if($field != null) echo '<span class="text error">Field: ' . $field . '</span>'; ?>
        </div>
    </body>
</html>