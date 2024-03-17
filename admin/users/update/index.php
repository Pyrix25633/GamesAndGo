<?php
    declare(strict_types = 1);
    require_once('../../../lib/utils.inc.php');
    require_once('../../../lib/errors.inc.php');
    require_once('../../../lib/validation.inc.php');
    require_once('../../../lib/auth.inc.php');
    require_once('../../../lib/database/user.inc.php');
    try {
        $connection = connect();
        Auth::protect($connection, ['admin']);
        $validator = new Validator($_GET);
        $id = $validator->getPositiveInt('id');
        $user = User::selectUser($connection, $id);
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
        <title>Games And Go - Update User</title>
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
            <form action="./update.php" method="POST">
                <?php
                    echo $user->formUpdate();
                ?>
                <input type="hidden" name="user-type" value="<?php echo $user->userType->value; ?>">
                <div class="container section">
                    <h2>Submit</h2>
                    <img class="icon" src="../../../img/submit.svg" alt="Submit Icon">
                </div>
                <div class="container">
                    <button type="submit">Update</button>
                </div>
            </form>
        </div>
    </body>
</html>