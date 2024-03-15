<?php
    declare(strict_types = 1);
    require_once('../../lib/utils.inc.php');
    require_once('../../lib/errors.inc.php');
    require_once('../../lib/validation.inc.php');
    require_once('../../lib/auth.inc.php');
    require_once('../../lib/database/product.inc.php');
    $settings = new Settings();
    try {
        $userId = Auth::protect(['customer']);
        $connection = connect($settings);
        $validator = new Validator($_GET);
        $id = $validator->getPositiveInt('id');
        $product = Product::select($connection, $id);
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
        <title>Games And Go - View Products</title>
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
                    <?php
                        switch($product->productType) {
                            case ProductType::CONSOLE: echo 'Console'; break;
                            case ProductType::VIDEOGAME: echo 'Videogame'; break;
                            case ProductType::ACCESSORY: echo 'Accessory'; break;
                            case ProductType::GUIDE: echo 'Guide'; break;
                        }
                    ?>
                    Details
                </h2>
            </div>
            <?php echo $product->toDetails(); ?>
            <div class="container">
                <form action="../cart/add.php" method="POST">
                    <div class="container space-between">
                        <label for="quantity">Quantity:</label>
                        <input class="small" type="number" name="quantity" id="quantity" value="1">
                    </div>
                    <div class="container">
                        <button type="submit">Add to Cart</button>
                    </div>
                </form>
            </div>
        </div>
    </body>
</html>