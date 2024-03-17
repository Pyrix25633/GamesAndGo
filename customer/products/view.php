<?php
    declare(strict_types = 1);
    require_once('../../lib/utils.inc.php');
    require_once('../../lib/errors.inc.php');
    require_once('../../lib/validation.inc.php');
    require_once('../../lib/database/product.inc.php');
    try {
        $connection = connect();
        $validator = new Validator($_GET);
        $productType = $validator->getProductType('product-type');
        try {
            $page = $validator->getPositiveInt('page');
        } catch(Response $_) {
            $page = 0;
        }
        switch($productType) {
            case ProductType::CONSOLE:
                $pages = Console::selectNumberOfPages($connection);
                $products = Console::selectPage($connection, $page); 
                break;
            case ProductType::VIDEOGAME:
                $pages = Videogame::selectNumberOfPages($connection);
                $products = Videogame::selectPage($connection, $page); 
                break;
            case ProductType::ACCESSORY:
                $pages = Accessory::selectNumberOfPages($connection);
                $products = Accessory::selectPage($connection, $page); 
                break;
            case ProductType::GUIDE:
                $pages = Guide::selectNumberOfPages($connection);
                $products = Guide::selectPage($connection, $page); 
                break;
        }
        $pageHelper = new PageHelper($page, $pages);
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
            <h3>
                <?php
                    switch($productType) {
                        case ProductType::CONSOLE: echo 'Consoles'; break;
                        case ProductType::VIDEOGAME: echo 'Videogames'; break;
                        case ProductType::ACCESSORY: echo 'Accessories'; break;
                        case ProductType::GUIDE: echo 'Guides'; break;
                    }
                ?>
            </h3>
            <table>
                <thead>
                    <tr>
                        <?php
                            switch($productType) {
                                case ProductType::CONSOLE: echo Console::tableGroups(); break;
                                case ProductType::VIDEOGAME: echo Videogame::tableGroups(); break;
                                case ProductType::ACCESSORY: echo Accessory::tableGroups(); break;
                                case ProductType::GUIDE: echo Guide::tableGroups(); break;
                            }
                        ?>
                        <th></th>
                    </tr>
                    <tr>
                        <?php
                            switch($productType) {
                                case ProductType::CONSOLE: echo Console::tableHeaders(); break;
                                case ProductType::VIDEOGAME: echo Videogame::tableHeaders(); break;
                                case ProductType::ACCESSORY: echo Accessory::tableHeaders(); break;
                                case ProductType::GUIDE: echo Guide::tableHeaders(); break;
                            }
                        ?>
                        <th>Details</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                        foreach($products as $product) {
                            echo '<tr>' . $product->toCustomerTableRow() . '</tr>';
                        }
                    ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="100">
                            <div class="container">
                                <a href="<?php echo 'view.php?product-type=' . $productType->value; ?>" class="img">
                                    <img src="https://pyrix25633.github.io/css/img/page-first.svg" alt="Page First Icon" class="button">
                                </a>
                                <a href="<?php echo 'view.php?product-type=' . $productType->value .'&page=' . $pageHelper->previousPage; ?>" class="img">
                                    <img src="https://pyrix25633.github.io/css/img/page-previous.svg" alt="Page Previous Icon" class="button">
                                </a>
                                <?php echo '<span>Page '.  ($page + 1) . '/'. ($pageHelper->lastPage + 1) . '</span>'; ?>
                                <a href="<?php echo 'view.php?product-type=' . $productType->value .'&page=' . $pageHelper->nextPage; ?>" class="img">
                                    <img src="https://pyrix25633.github.io/css/img/page-next.svg" alt="Page Next Icon" class="button">
                                </a>
                                <a href="<?php echo 'view.php?product-type=' . $productType->value .'&page=' . $pageHelper->lastPage; ?>" class="img">
                                    <img src="https://pyrix25633.github.io/css/img/page-last.svg" alt="Page Last Icon" class="button">
                                </a>
                            </div>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </body>
</html>