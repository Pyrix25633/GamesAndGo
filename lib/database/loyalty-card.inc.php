<?php
    class LoyaltyCard {
        public string $rfid;
        public string $customerUsername;

        function __construct(string $rfid, string $customerUsername) {
            $this->rfid = $rfid;
            $this->customerUsername = $customerUsername;
        }

        static function formNew(): string {
            $form = getFileContent(Settings::LIB_ABSOLUTE_PATH . '/forms/loyalty-card-new.html');
            return str_replace('{$basePath}', URL_ROOT_PATH, $form);
        }

        static function fromForm(Validator &$validator): LoyaltyCard {
            return new LoyaltyCard($validator->getNonEmptyString('rfid'), $validator->getNonEmptyString('customer-username'));
        }

        function insert(mysqli $connection): void {
            try {
                $sql = "
                    INSERT INTO LoyaltyCards
                    (rfid, customerId)
                    VALUES (?, (
                        SELECT id
                        FROM Users
                        WHERE userType = 'CUSTOMER' AND username = ?
                    ));
                ";
                $statement = $connection->prepare($sql);
                $statement->bind_param('ss', $this->rfid, $this->customerUsername);
                $statement->execute();
                $statement->close();
            } catch(mysqli_sql_exception $_) {
                throw new UnprocessableContentResponse();
            }
        }

        static function selectPoints(mysqli $connection, int $customerId): ?int {
            try {
                $sql = "
                    SELECT points
                    FROM LoyaltyCards
                    WHERE customerId = ?;
                ";
                $statement = $connection->prepare($sql);
                $statement->bind_param('i', $customerId);
                $statement->execute();
                $result = $statement->get_result();
                $statement->close();
                $row = $result->fetch_assoc();
                if($row == null) return null;
                return intval($row['points']);
            } catch(mysqli_sql_exception $_) {
                throw new InternalServerErrorResponse();
            }
        }
    }
?>