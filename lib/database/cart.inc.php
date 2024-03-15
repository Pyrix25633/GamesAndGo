<?php
    class Cart {
        static function insertIgnore(mysqli $connection, int $userId): void {
            try {
                $sql = "
                    INSERT IGNORE INTO Carts
                    (customerId)
                    VALUES (?);
                ";
                $statement = $connection->prepare($sql);
                $statement->bind_param('i', $userId);
                $statement->execute();
            } catch(mysqli_sql_exception $_) {
                throw new InternalServerErrorResponse();
            }
        }

        static function selectId(mysqli $connection, int $userId): int {
            try {
                $sql = "
                    SELECT id
                    FROM Carts
                    WHERE customerId = ?;
                ";
                $statement = $connection->prepare($sql);
                $statement->bind_param('i', $userId);
                $statement->execute();
                $result = $statement->get_result();
                $row = $result->fetch_assoc();
                if($row == null) throw new InternalServerErrorResponse();
                return intval($row['id']);
            } catch(mysqli_sql_exception $_) {
                throw new InternalServerErrorResponse();
            }
        }
    }

    class ProductOnCart {
        public int $id;
        public int $productId;
        public int $cartId;
        public int $quantity;

        function __construct(int $id, int $productId, int $cartId, int $quantity) {
            $this->id = $id;
            $this->productId = $productId;
            $this->cartId = $cartId;
            $this->quantity = $quantity;
        }

        static function fromRow(array $row): ProductOnCart {
            return new ProductOnCart(intval($row['id']),
                                     intval($row['productId']),
                                     intval($row['cartId']),
                                     intval($row['quantity']));
        }

        static function insert(mysqli $connection, int $userId, int $productId, int $quantity): void {
            Cart::insertIgnore($connection, $userId);
            $cartId = Cart::selectId($connection, $userId);
            try {
                $sql = "
                    INSERT INTO ProductsOnCarts
                    (productId, cartId, quantity)
                    VALUES (?, ?, ?);
                ";
                $statement = $connection->prepare($sql);
                $statement->bind_param('iii', $productId, $cartId, $quantity);
                $statement->execute();
            } catch(mysqli_sql_exception $_) {
                throw new InternalServerErrorResponse();
            }
        }

        static function select(mysqli $connection, int $id): ProductOnCart {
            try {
                $sql = "
                    SELECT *
                    FROM ProductsOnCarts
                    WHERE id = ?;
                ";
                $statement = $connection->prepare($sql);
                $statement->bind_param('i', $id);
                $statement->execute();
                $result = $statement->get_result();
                $row = $result->fetch_assoc();
                if($row == null) throw new NotFoundResponse('id');
                return self::fromRow($row);
            } catch(mysqli_sql_exception $_) {
                throw new InternalServerErrorResponse();
            }
        }

        function delete(mysqli $connection): void {
            try {
                $sql = "
                    DELETE FROM ProductsOnCarts
                    WHERE id = ?;
                ";
                $statement = $connection->prepare($sql);
                $statement->bind_param('i', $this->id);
                $statement->execute();
            } catch(mysqli_sql_exception $_) {
                throw new InternalServerErrorResponse();
            }
        }
    }

    class CartProduct {
        public int $id;
        public int $code;
        public ProductType $productType;
        public int $priceInCents;
        public int $discount;
        public int $quantity;
        public string $nameOrTitle;

        function __construct(int $id, int $code, ProductType $productType, int $priceInCents, int $discount, int $quantity, string $nameOrTitle) {
            $this->id = $id;
            $this->code = $code;
            $this->productType = $productType;
            $this->priceInCents = $priceInCents;
            $this->discount = $discount;
            $this->quantity = $quantity;
            $this->nameOrTitle = $nameOrTitle;
        }

        static function tableGroups(): string {
            return getFileContent(Settings::LIB_ABSOLUTE_PATH . '/tables/cart-product-groups.html');
        }

        static function tableHeaders(): string {
            return getFileContent(Settings::LIB_ABSOLUTE_PATH . '/tables/cart-product-headers.html');
        }

        static function fromRow(array $row): CartProduct {
            return new CartProduct(intval($row['id']),
                                   intval($row['code']),
                                   ProductType::fromMysqlString($row['productType']),
                                   intval($row['priceInCents']),
                                   intval($row['discount']),
                                   intval($row['quantity']),
                                   $row['nameOrTitle']);
        }

        static function selectAll(mysqli $connection, int $userId): array {
            $cartId = Cart::selectId($connection, $userId);
            try {
                $sql = "
                    SELECT R.pocId AS id, R.code, R.productType, R.priceInCents, R.discount, R.quantity, S.nameOrTitle
                    FROM (
                        SELECT P.id, POC.id AS pocId, P.code, P.productType, P.price * 100 AS priceInCents, P.discount, POC.quantity
                        FROM ProductsOnCarts AS POC
                        INNER JOIN Products AS P
                        ON POC.productId = P.id
                        WHERE cartId = ?
                    ) AS R
                    LEFT JOIN (
                        SELECT id, name AS nameOrTitle FROM Consoles
                        UNION ALL SELECT id, title AS nameOrTitle FROM Videogames
                        UNION ALL SELECT id, name AS nameOrTitle FROM Accessories
                        UNION ALL SELECT id, title AS nameOrTitle FROM Guides
                    ) AS S
                    ON S.id = R.id;
                ";
                $statement = $connection->prepare($sql);
                $statement->bind_param('i', $cartId);
                $statement->execute();
                $result = $statement->get_result();
                while($row = $result->fetch_assoc())
                    $cartProducts[] = self::fromRow($row);
                return $cartProducts ?? array();
            } catch(mysqli_sql_exception $_) {
                throw new InternalServerErrorResponse();
            }
        }

        static function select(mysqli $connection, int $productOnCartId, int $userId): CartProduct {
            try {
                $sql = "
                    SELECT R.pocId AS id, R.cartId, R.code, R.productType, R.priceInCents, R.discount, R.quantity, S.nameOrTitle
                    FROM (
                        SELECT P.id, POC.id AS pocId, POC.cartId, P.code, P.productType, P.price * 100 AS priceInCents, P.discount, POC.quantity
                        FROM ProductsOnCarts AS POC
                        INNER JOIN Products AS P
                        ON POC.productId = P.id
                        WHERE POC.id = ?
                    ) AS R
                    LEFT JOIN (
                        SELECT id, name AS nameOrTitle FROM Consoles
                        UNION ALL SELECT id, title AS nameOrTitle FROM Videogames
                        UNION ALL SELECT id, name AS nameOrTitle FROM Accessories
                        UNION ALL SELECT id, title AS nameOrTitle FROM Guides
                    ) AS S
                    ON S.id = R.id;
                ";
                $statement = $connection->prepare($sql);
                $statement->bind_param('i', $productOnCartId);
                $statement->execute();
                $result = $statement->get_result();
                $row = $result->fetch_assoc();
                if($row == null) throw new NotFoundResponse('id');
                if(intval($row['cartId']) != Cart::selectId($connection, $userId)) throw new ForbiddenResponse();
                return self::fromRow($row);
            } catch(mysqli_sql_exception $_) {
                throw new InternalServerErrorResponse();
            }
        }

        function toTableRow(): string {
            $row = getFileContent(Settings::LIB_ABSOLUTE_PATH . '/tables/cart-product-row.html');
            $price = $this->priceInCents / 100;
            $row = str_replace('{$price}', number_format($price, 2), $row);
            $finalPrice = $price * (100 - $this->discount) / 100;
            $row = str_replace('{$finalPrice}', number_format($finalPrice, 2), $row);
            foreach($this as $property => $value) {
                switch($property) {
                    case 'productType': $row = str_replace('{$' . $property . '}', $value->toUiString(), $row); break;
                    default: $row = str_replace('{$' . $property . '}', $value, $row); break;
                }
            }
            $row .= '<td><a href="../products/details.php?id=' . $this->id . '">Details</a></td>';
            $row .= '<td><a href="./details.php?id=' . $this->id . '">Remove</a></td>';
            return $row;
        }

        function toDetails(): string {
            $row = getFileContent(Settings::LIB_ABSOLUTE_PATH. '/details/cart-product.html');
            $price = $this->priceInCents / 100;
            $row = str_replace('{$price}', number_format($price, 2), $row);
            $finalPrice = $price * (100 - $this->discount) / 100;
            $row = str_replace('{$finalPrice}', number_format($finalPrice, 2), $row);
            foreach($this as $property => $value) {
                switch($property) {
                    case 'productType': $row = str_replace('{$' . $property . '}', $value->toUiString(), $row); break;
                    default: $row = str_replace('{$' . $property . '}', $value, $row); break;
                }
            }
            return $row;
        }
    }
?>