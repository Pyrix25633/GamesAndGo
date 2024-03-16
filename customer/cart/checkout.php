<?php
    declare(strict_types = 1);
    require_once('../../lib/utils.inc.php');
    require_once('../../lib/errors.inc.php');
    require_once('../../lib/auth.inc.php');
    require_once('../../lib/database/cart.inc.php');
    require_once('../../lib/database/user.inc.php');
    require_once('../../lib/database/purchase.inc.php');
    try {
        $connection = connect();
        $user = Auth::protect($connection, ['customer']);
        if(ProductOnCart::count($connection, $user->id) == 0) throw new ForbiddenResponse();
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
        <title>Games And Go - Checkout</title>
        <link rel="stylesheet" href="https://pyrix25633.github.io/css/style.css">
        <link rel="stylesheet" href="https://pyrix25633.github.io/css/roboto-condensed-off.css">
        <link rel="stylesheet" href="https://pyrix25633.github.io/css/compact-mode-off.css">
        <link rel="stylesheet" href="https://pyrix25633.github.io/css/sharp-mode-off.css">
        <link rel="icon" href="../../img/games-and-go.svg" type="image/svg">
    </head>
    <body>
        <nav id="navbar">
            <div class="container navbar">
                <span class="title app-color">Games</span>
                <span class="title">And Go</span>
                <img id="icon" src="../../img/games-and-go.svg" alt="Games And Go Icon">
            </div>
        </nav>
        <div class="panel box">
            <form action="../purchases/new.php" method="POST">
                <div class="container section">
                    <h2>Purchase</h2>
                    <img id="icon" src="../../img/checkout.svg" alt="Games And Go Icon">
                </div>
                <?php echo PaymentType::formSelect(); ?>
                <div class="container space-between">
                    <label for="paymentCode">Payment Code:</label>
                    <input type="text" name="payment-code" id="payment-code">
                </div>
                <div class="container">
                    <button type="submit">Checkout</button>
                </div>
            </form>
        </div>
    </body>
</html>