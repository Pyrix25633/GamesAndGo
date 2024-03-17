<?php
    declare(strict_types = 1);
    require_once('../../lib/utils.inc.php');
    require_once('../../lib/errors.inc.php');
    require_once('../../lib/validation.inc.php');
    require_once('../../lib/auth.inc.php');
    require_once('../../lib/database/product.inc.php');
    require_once('../../lib/database/user.inc.php');
    try {
        $connection = connect();
        Auth::protect($connection, ['admin']);
        $validator = new Validator($_GET, true);
        try {
            $page = $validator->getPositiveInt('page');
        } catch(Response $_) {
            $page = 0;
        }
        $pages = User::selectNumberOfPages($connection);
        $users = User::selectPage($connection, $page); 
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
        <title>Games And Go - View Users</title>
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
            <h3>Users</h3>
            <table>
                <thead>
                    <tr>
                        <?php
                            echo User::tableGroups();
                        ?>
                        <th></th>
                    </tr>
                    <tr>
                        <?php
                            echo User::tableHeaders();
                        ?>
                        <th>Update</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                        foreach($users as $user) {
                            echo '<tr>' . $user->toAdminTableRow() . '</tr>';
                        }
                    ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="100">
                            <div class="container">
                                <a href="<?php echo 'index.php'; ?>" class="img">
                                    <img src="https://pyrix25633.github.io/css/img/page-first.svg" alt="Page First Icon" class="button">
                                </a>
                                <a href="<?php echo 'index.php?page=' . $pageHelper->previousPage; ?>" class="img">
                                    <img src="https://pyrix25633.github.io/css/img/page-previous.svg" alt="Page Previous Icon" class="button">
                                </a>
                                <?php echo '<span>Page '.  ($page + 1) . '/'. ($pageHelper->lastPage + 1) . '</span>'; ?>
                                <a href="<?php echo 'index.php?page=' . $pageHelper->nextPage; ?>" class="img">
                                    <img src="https://pyrix25633.github.io/css/img/page-next.svg" alt="Page Next Icon" class="button">
                                </a>
                                <a href="<?php echo 'index.php?page=' . $pageHelper->lastPage; ?>" class="img">
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