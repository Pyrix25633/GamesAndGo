<?php
    declare(strict_types = 1);
    require_once('../lib/utils.inc.php');
    require_once('../lib/errors.inc.php');
    require_once('../lib/auth.inc.php');
    require_once('../lib/database/user.inc.php');
    require_once('../lib/database/purchase.inc.php');
    try {
        $connection = connect();
        Auth::protect($connection, ['admin']);
        $totalMontlyRevenue = Purchase::selectTotalMonthlyRevenue($connection);
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
        <title>Games And Go - Admin Home</title>
        <link rel="stylesheet" href="https://pyrix25633.github.io/css/style.css">
        <link rel="stylesheet" href="https://pyrix25633.github.io/css/roboto-condensed-off.css">
        <link rel="stylesheet" href="https://pyrix25633.github.io/css/compact-mode-off.css">
        <link rel="stylesheet" href="https://pyrix25633.github.io/css/sharp-mode-off.css">
        <link rel="icon" href="../img/games-and-go.svg" type="image/svg">
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
            <h2>Admin Home</h2>
            Total Montly Revenue: <?php echo number_format($totalMontlyRevenue, 2); ?>
            <a href="./users/new">New User</a>
            <a href="./users/">View and Update Users</a>
            <a href="../customer/products">View Products and Feedbacks</a>
        </div>
    </body>
</html>