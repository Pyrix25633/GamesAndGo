<?php
    declare(strict_types = 1);
    require_once('../../lib/utils.inc.php');
    require_once('../../lib/errors.inc.php');
    require_once('../../lib/validation.inc.php');
    require_once('../../lib/auth.inc.php');
    require_once('../../lib/database/product.inc.php');
    require_once('../../lib/database/user.inc.php');
    require_once('../../lib/database/feedback.inc.php');
    try {
        $connection = connect();
        try {
            $user = Auth::protect($connection, ['customer']);
        } catch(Response $_) {
            $user = null;
        }
        $validator = new Validator($_GET);
        $id = $validator->getPositiveInt('id');
        $product = Product::select($connection, $id);
        if($user != null) $purchased = $user->purchased($connection, $id);
        else $purchased = false;
        $feedbacks = Feedback::selectAll($connection, $id);
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
        <title>Games And Go - View Product</title>
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
            <?php
                if($user != null)
                    echo '
                        <div class="container">
                            <form action="../cart/add.php" method="POST">
                                <div class="container section">
                                    <h2>Add to Cart</h2>
                                    <img class="icon" src="../../img/add-to-cart.svg" alt="Add to Cart Icon">
                                </div>
                                <input type="hidden" name="id" value="' . $product->id . '">
                                <div class="container space-between">
                                    <label for="quantity">Quantity:</label>
                                    <input class="small" type="number" name="quantity" id="quantity" value="1">
                                </div>
                                <div class="container">
                                    <button type="submit">Add to Cart</button>
                                </div>
                            </form>
                        </div>
                    ';
            ?>
            <?php
                if($purchased)
                    echo '
                        <div class="container">
                            <form action="./new-feedback.php" method="POST">
                                ' . Feedback::formNew() .'
                                <input type="hidden" name="product-id" value="' . $id . '">
                                <div class="container">
                                    <button type="submit">Post</button>
                                </div>
                            </form>
                        </div>
                    ';
            ?>
            <div class="container">
                <div class="box">
                    <div class="container">
                        <h3>Feedbacks</h3>
                        <img class="icon" src="../../img/feedback.svg" alt="Feedback Icon">
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <?php
                                    echo Feedback::tableGroups();
                                ?>
                            </tr>
                            <tr>
                                <?php
                                    echo Feedback::tableHeaders();
                                ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                                foreach($feedbacks as $feedback) {
                                    echo '<tr>' . $feedback->toTableRow() . '</tr>';
                                }
                                if(sizeof($feedbacks) == 0)
                                    echo '<tr><td colspan="100">0 Feedbacks</td></tr>';
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </body>
</html>