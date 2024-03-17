<?php
    declare(strict_types = 1);
    require_once('../../lib/utils.inc.php');
    require_once('../../lib/errors.inc.php');
    require_once('../../lib/auth.inc.php');
    require_once('../../lib/database/product.inc.php');
    require_once('../../lib/database/cart.inc.php');
    require_once('../../lib/database/user.inc.php');
    try {
        $connection = connect();
        $user = Auth::protect($connection, ['customer']);
        try {
            $products = CartProduct::selectAll($connection, $user->id);
        } catch(Response $e) {
            if($e instanceof NotFoundResponse) $products = array();
        }
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
        <title>Games And Go - View Cart</title>
        <link rel="stylesheet" href="https://pyrix25633.github.io/css/style.css">
        <link rel="stylesheet" href="https://pyrix25633.github.io/css/roboto-condensed-off.css">
        <link rel="stylesheet" href="https://pyrix25633.github.io/css/compact-mode-off.css">
        <link rel="stylesheet" href="https://pyrix25633.github.io/css/sharp-mode-off.css">
        <link rel="stylesheet" href="../../css/style.css">
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
            <h3>Cart</h3>
            <table>
                <thead>
                    <tr>
                        <?php echo CartProduct::tableGroups(); ?>
                        <th></th>
                        <th></th>
                    </tr>
                    <tr>
                        <?php echo CartProduct::tableHeaders(); ?>
                        <th>Details</th>
                        <th>Remove</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                        foreach($products as $product) {
                            echo '<tr>' . $product->toTableRow() . '</tr>';
                        }
                        if(sizeof($products) == 0)
                            echo '<tr><td colspan="100">0 Products in Cart</td></tr>';
                    ?>
                </tbody>
            </table>
            <?php
                if(sizeof($products) > 0)
                    echo '
                        <div class="container">
                            <a href="./checkout.php">Checkout</a>
                        </div>
                    ';
            ?>
        </div>
    </body>
</html>