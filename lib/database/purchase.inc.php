<?php
    class Purchase {
        public ?int $id;
        public int $customerId;
        public ?DateTime $createdAt;
        public PaymentType $paymentType;
        public string $paymentCode;
        public ?int $productsTotalInCents;
        public int $commission;

        function __construct(?int $id, int $customerId, ?DateTime $createdAt, PaymentType $paymentType,
                             string $paymentCode, ?int $productsTotalInCents, int $commission) {
            $this->id = $id;
            $this->customerId = $customerId;
            $this->createdAt = $createdAt;
            $this->paymentType = $paymentType;
            $this->paymentCode = $paymentCode;
            $this->productsTotalInCents = $productsTotalInCents;
            $this->commission = $commission;
        }

        static function tableGroups(): string {
            return getFileContent(Settings::LIB_ABSOLUTE_PATH . '/tables/purchase-groups.html');
        }

        static function tableHeaders(): string {
            return getFileContent(Settings::LIB_ABSOLUTE_PATH . '/tables/purchase-headers.html');
        }

        static function fromRow(array $row): Purchase {
            return new Purchase(intval($row['id']),
                                intval($row['customerId']),
                                new DateTime($row['createdAt']),
                                PaymentType::fromMysqlString($row['paymentType']),
                                $row['paymentCode'],
                                intval($row['productsTotalInCents']),
                                intval($row['commission']));
        }

        static function fromForm(Validator $validator, int $customerId): Purchase {
            $paymentType = $validator->getPaymentType('payment-type');
            return new Purchase(null,
                                $customerId,
                                null,
                                $paymentType,
                                $validator->getNonEmptyString('payment-code'),
                                null,
                                $paymentType == PaymentType::CHEQUE ? 10 : 0);
        }

        function insert(mysqli $connection): void {
            $sql = "
                INSERT INTO Purchases
                (customerId, paymentType, paymentCode, commission)
                VALUES (?, ?, ?, ?);
            ";
            $statement = $connection->prepare($sql);
            $formattedPaymentType = $this->paymentType->toMysqlString();
            $statement->bind_param('issi', $this->customerId, $formattedPaymentType, $this->paymentCode, $this->commission);
            $statement->execute();
            $this->id = $connection->insert_id;
            $statement->close();
        }

        static function selectAll(mysqli $connection, int $customerId): array {
            try {
                $sql = "
                    SELECT P.id, P.customerId, P.createdAt, P.paymentType, P.paymentCode, 
                        ROUND(SUM(POC.piecePrice * POC.quantity) * 100) AS productsTotalInCents, P.commission
                    FROM ProductsOnPurchases AS POC
                    INNER JOIN Purchases AS P
                    ON P.id = POC.purchaseId
                    WHERE customerId = ?
                    GROUP BY P.id;
                ";
                $statement = $connection->prepare($sql);
                $statement->bind_param('i', $customerId);
                $statement->execute();
                $result = $statement->get_result();
                $statement->close();
                while($row = $result->fetch_assoc())
                    $purchases[] = self::fromRow($row);
                return $purchases ?? array();
            } catch(mysqli_sql_exception $_) {
                throw new InternalServerErrorResponse();
            }
        }

        static function selectCustomerId(mysqli $connection, int $id): int {
            try {
                $sql = "
                    SELECT customerId
                    FROM Purchases
                    WHERE id = ?;
                ";
                $statement = $connection->prepare($sql);
                $statement->bind_param('i', $id);
                $statement->execute();
                $result = $statement->get_result();
                $statement->close();
                $row = $result->fetch_assoc();
                if($row == null) throw new NotFoundResponse();
                return intval($row['customerId']);
            } catch(mysqli_sql_exception $_) {
                throw new InternalServerErrorResponse();
            }
        }

        function toTableRow(): string {
            $row = getFileContent(Settings::LIB_ABSOLUTE_PATH . '/tables/purchase-row.html');
            $productsTotal = $this->productsTotalInCents / 100;
            $row = str_replace('{$productsTotal}', number_format($productsTotal, 2), $row);
            $total = $productsTotal + $this->commission;
            $row = str_replace('{$total}', number_format($total, 2), $row);
            foreach($this as $property => $value) {
                switch($property) {
                    case 'createdAt': $row = str_replace('{$' . $property . '}', $value->format('Y/m/d H:i:s'), $row); break;
                    case 'paymentType': $row = str_replace('{$' . $property . '}', $value->toUiString(), $row); break;
                    default: $row = str_replace('{$' . $property . '}', $value, $row); break;
                }
            }
            $row = str_replace('{$details}', '<a href="./details.php?id=' . $this->id . '">Details</a>', $row);
            return $row;
        }

        static function selectTotalMonthlyRevenue(mysqli $connection): float {
            try {
                $sql = "
                    SELECT SUM(POC.piecePrice * POC.quantity) AS totalMonthlyRevenue
                    FROM ProductsOnPurchases AS POC
                    INNER JOIN Purchases AS P
                    ON P.id = POC.purchaseId
                    WHERE P.createdAt > CURRENT_DATE - INTERVAL 1 MONTH AND P.createdAt <= CURRENT_TIMESTAMP;
                ";
                $statement = $connection->prepare($sql);
                $statement->execute();
                $result = $statement->get_result();
                $row = $result->fetch_assoc();
                if($row == null) throw new InternalServerErrorResponse();
                return floatval($row['totalMonthlyRevenue']);
            } catch(mysqli_sql_exception $_) {
                throw new InternalServerErrorResponse();
            }
        }
    }

    class ProductOnPurchase {
        public ?int $id;
        public int $productId;
        public int $purchaseId;
        public int $piecePrice;
        public int $quantity;

        function __construct(?int $id, int $productId, int $purchaseId, int $piecePrice, int $quantity) {
            $this->id = $id;
            $this->productId = $productId;
            $this->purchaseId = $purchaseId;
            $this->piecePrice = $piecePrice;
            $this->quantity = $quantity;
        }

        function insert(mysqli $connection): void {
            $sql = "
                INSERT INTO ProductsOnPurchases
                (productId, purchaseId, piecePrice, quantity)
                VALUES (?, ?, ? / 100, ?);
            ";
            $statement = $connection->prepare($sql);
            $statement->bind_param('iiii', $this->productId, $this->purchaseId, $this->piecePrice, $this->quantity);
            $statement->execute();
            $statement->close();
        }
    }

    class PurchaseProduct {
        public int $productId;
        public int $code;
        public ProductType $productType;
        public int $piecePriceInCents;
        public int $quantity;
        public string $nameOrTitle;

        function __construct(int $productId, int $code, ProductType $productType, int $piecePriceInCents, int $quantity, string $nameOrTitle) {
            $this->productId = $productId;
            $this->code = $code;
            $this->productType = $productType;
            $this->piecePriceInCents = $piecePriceInCents;
            $this->quantity = $quantity;
            $this->nameOrTitle = $nameOrTitle;
        }

        static function tableGroups(): string {
            return getFileContent(Settings::LIB_ABSOLUTE_PATH . '/tables/purchase-product-groups.html');
        }

        static function tableHeaders(): string {
            return getFileContent(Settings::LIB_ABSOLUTE_PATH . '/tables/purchase-product-headers.html');
        }

        static function fromRow(array $row): PurchaseProduct {
            return new PurchaseProduct(intval($row['productId']),
                                       intval($row['code']),
                                       ProductType::fromMysqlString($row['productType']),
                                       intval($row['piecePriceInCents']),
                                       intval($row['quantity']),
                                       $row['nameOrTitle']);
        }

        static function selectAll(mysqli $connection, int $purchaseId): array {
            try {
                $sql = "
                    SELECT R.productId, R.code, R.productType, R.piecePriceInCents, R.quantity, S.nameOrTitle
                    FROM (
                        SELECT P.id, POP.productId, P.code, P.productType, ROUND(POP.piecePrice * 100) AS piecePriceInCents, POP.quantity
                        FROM ProductsOnPurchases AS POP
                        INNER JOIN Products AS P
                        ON POP.productId = P.id
                        WHERE purchaseId = ?
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
                $statement->bind_param('i', $purchaseId);
                $statement->execute();
                $result = $statement->get_result();
                $statement->close();
                while($row = $result->fetch_assoc())
                    $cartProducts[] = self::fromRow($row);
                return $cartProducts ?? array();
            } catch(mysqli_sql_exception $_) {
                throw new InternalServerErrorResponse();
            }
        }

        function toTableRow(): string {
            $row = getFileContent(Settings::LIB_ABSOLUTE_PATH . '/tables/purchase-product-row.html');
            $piecePrice = $this->piecePriceInCents / 100;
            $row = str_replace('{$piecePrice}', number_format($piecePrice, 2), $row);
            $total = $piecePrice * $this->quantity;
            $row = str_replace('{$total}', number_format($total, 2), $row);
            foreach($this as $property => $value) {
                switch($property) {
                    case 'productType': $row = str_replace('{$' . $property . '}', $value->toUiString(), $row); break;
                    default: $row = str_replace('{$' . $property . '}', $value, $row); break;
                }
            }
            $row = str_replace('{$total}', $total, $row);
            $row .= '<td><a href="../products/details.php?id=' . $this->productId . '">Details</a></td>';
            return $row;
        }
    }

    enum PaymentType: string {
        case CHEQUE = 'cheque';
        case CREDIT_CARD = 'credit-card';
        case CREDIT_TRANSFER = 'credit-transfer';

        static function formSelect(): string {
            return getFileContent(Settings::LIB_ABSOLUTE_PATH . '/forms/payment-type.html');
        }

        function toMysqlString(): string {
            return $this->name;
        }

        function toUiString(): string {
            switch($this) {
                case self::CHEQUE: return 'Cheque';
                case self::CREDIT_CARD: return 'Credit Card';
                case self::CREDIT_TRANSFER: return 'Credit Transfer';
            }
        }

        static function fromString(string $s): PaymentType {
            foreach(self::cases() as $paymentType) {
                if($s == $paymentType->value)
                    return $paymentType;
            }
            throw new BadRequestResponse();
        }

        static function fromMysqlString(string $s): PaymentType {
            foreach(self::cases() as $paymentType) {
                if($s == $paymentType->name)
                    return $paymentType;
            }
            throw new Error('Unknown ProductType: ' . $s);
        }
    }
?>