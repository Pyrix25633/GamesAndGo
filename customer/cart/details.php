<?php
    declare(strict_types = 1);
    require_once('../../lib/utils.inc.php');
    require_once('../../lib/errors.inc.php');
    require_once('../../lib/validation.inc.php');
    require_once('../../lib/auth.inc.php');
    require_once('../../lib/database/product.inc.php');
    require_once('../../lib/database/cart.inc.php');
    try {
        $userId = Auth::protect(['customer']);
        $connection = connect();
        $validator = new Validator($_GET);
        $id = $validator->getPositiveInt('id');
        $cartId = Cart::selectId($connection, $userId);
        $product = CartProduct::select($connection, $id, $userId);
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
        <title>Games And Go - View Cart Product</title>
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
            <div class="container section">
                <h2>
                    Cart Product Details
                </h2>
            </div>
            <?php echo $product->toDetails(); ?>
            <div class="container">
                <form action="../cart/remove.php" method="POST">
                    <div class="container section">
                        <h2>Remove from Cart</h2>
                        <img class="icon" src="../../img/remove-from-cart.svg" alt="Remove from Cart Icon">
                    </div>
                    <input type="hidden" name="id" value="<?php echo $product->id; ?>">
                    <div class="container">
                        <button type="submit">Remove from Cart</button>
                    </div>
                </form>
            </div>
        </div>
    </body>
</html>