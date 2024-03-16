<?php
    class Purchase {
        public ?int $id;
        public int $customerId;
        public ?DateTime $createdAt;
        public PaymentType $paymentType;
        public string $paymentCode;
        public int $commission;

        function __construct(?int $id, int $customerId, ?DateTime $createdAt, PaymentType $paymentType, string $paymentCode, int $commission) {
            $this->id = $id;
            $this->customerId = $customerId;
            $this->createdAt = $createdAt;
            $this->paymentType = $paymentType;
            $this->paymentCode = $paymentCode;
            $this->commission = $commission;
        }

        static function fromForm(Validator $validator, int $customerId): Purchase {
            $paymentType = $validator->getPaymentType('payment-type');
            return new Purchase(null,
                                $customerId,
                                null,
                                $paymentType,
                                $validator->getNonEmptyString('payment-code'), $paymentType == PaymentType::CHEQUE ? 10 : 0);
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
                VALUES (?, ?, ?, ?);
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
                if($s == $paymentType->value)
                    return $paymentType;
            }
            throw new Error('Unknown ProductType: ' . $s);
        }
    }
?>