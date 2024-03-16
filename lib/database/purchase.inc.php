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
        }

        static function selectAll(mysqli $connection, int $customerId): array {
            try {
                $sql = "
                    SELECT P.id, P.customerId, P.createdAt, P.paymentType, P.paymentCode, 
                        ROUND(SUM(POC.piecePrice * POC.quantity) * 100) AS productsTotalInCents, P.commission
                    FROM ProductsOnPurchases AS POC
                    INNER JOIN Purchases AS P
                    ON P.id = POC.purchaseId
                    WHERE customerId = ?;
                ";
                $statement = $connection->prepare($sql);
                $statement->bind_param('i', $customerId);
                $statement->execute();
                $result = $statement->get_result();
                while($row = $result->fetch_assoc())
                    $purchases[] = self::fromRow($row);
                return $purchases ?? array();
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
            $row = str_replace('{$details}', '<a href="./details?id=' . $this->id . '">Details</a>', $row);
            return $row;
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
                case self::CHEQUE: return 'Cheque (+10)';
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