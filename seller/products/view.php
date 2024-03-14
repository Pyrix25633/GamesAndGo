<?php
    declare(strict_types = 1);
    require_once('../../lib/utils.inc.php');
    require_once('../../lib/errors.inc.php');
    require_once('../../lib/validation.inc.php');
    require_once('../../lib/auth.inc.php');
    require_once('../../lib/database/product.inc.php');
    $settings = new Settings();
    try {
        $userId = Auth::protect(['seller']);
        $connection = connect($settings);
        $validator = new Validator($_GET);
        $productType = $validator->getProductType('product-type');
        try {
            $page = $validator->getPositiveInt('page');
        } catch(Response $_) {
            $page = 0;
        }
    } catch(Response $error) {
        $connection->close();
        $error->send();
    }
    switch($productType) {
        case ProductType::CONSOLE: $pages = Console::selectNumberOfPages($connection, $settings); break;
        case ProductType::VIDEOGAME: $pages = Videogame::selectNumberOfPages($connection, $settings); break;
        case ProductType::ACCESSORY: $pages = Accessory::selectNumberOfPages($connection, $settings); break;
        case ProductType::GUIDE: $pages = Guide::selectNumberOfPages($connection, $settings); break;
    }
    $pageHelper = new PageHelper($page, $pages);
    $basePath = '../..';
    //$connection->close();
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
        <link rel="icon" href="../../img/games-and-go.svg" type="image/png">
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
                                case ProductType::CONSOLE: echo Console::tableGroups($basePath); break;
                                case ProductType::VIDEOGAME: echo Videogame::tableGroups($basePath); break;
                                case ProductType::ACCESSORY: echo Accessory::tableGroups($basePath); break;
                                case ProductType::GUIDE: echo Guide::tableGroups($basePath); break;
                            }
                        ?>
                        <th></th>
                    </tr>
                    <tr>
                        <?php
                            switch($productType) {
                                case ProductType::CONSOLE: echo Console::tableHeaders($basePath); break;
                                case ProductType::VIDEOGAME: echo Videogame::tableHeaders($basePath); break;
                                case ProductType::ACCESSORY: echo Accessory::tableHeaders($basePath); break;
                                case ProductType::GUIDE: echo Guide::tableHeaders($basePath); break;
                            }
                        ?>
                        <th>Edit</th>
                    </tr>
                </thead>
                <tbody>
                    <?php var_dump(Console::selectPage($connection, $page)); ?>
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