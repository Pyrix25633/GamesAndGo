<?php
    class Feedback {
        public ?int $customerId;
        public ?string $customerUsername;
        public int $vote;
        public string $comment;
        public ?int $productId;

        function __construct(?int $customerId, ?string $customerUsername, int $vote, string $comment, ?int $productId) {
            $this->customerId = $customerId;
            $this->customerUsername = $customerUsername;
            $this->vote = $vote;
            $this->comment = $comment;
            $this->productId = $productId;
        }

        static function tableGroups(): string {
            return getFileContent(Settings::LIB_ABSOLUTE_PATH . '/tables/feedback-groups.html');
        }

        static function tableHeaders(): string {
            return getFileContent(Settings::LIB_ABSOLUTE_PATH . '/tables/feedback-headers.html');
        }

        static function formNew(): string {
            $form = getFileContent(Settings::LIB_ABSOLUTE_PATH . '/forms/feedback-new.html');
            return str_replace('{$basePath}', URL_ROOT_PATH, $form);
        }

        static function fromForm(Validator &$validator, int $customerId, int $productId): Feedback {
            return new Feedback($customerId,
                                null,
                                $validator->getVote('vote'),
                                $validator->getNonEmptyString('comment'),
                                $productId);
        }

        function insert(mysqli $connection): void {
            try {
                $sql = "
                    INSERT INTO Feedbacks
                    (customerId, vote, comment, productId)
                    VALUES (?, ?, ?, ?);
                ";
                $statement = $connection->prepare($sql);
                $statement->bind_param('iisi', $this->customerId, $this->vote, $this->comment, $this->productId);
                $statement->execute();
                $statement->close();
            } catch(mysqli_sql_exception $_) {
                throw new UnprocessableContentResponse();
            }
        }

        static function fromRow(array $row): Feedback {
            return new Feedback(null, $row['customerUsername'], intval($row['vote']), $row['comment'], null);
        }

        static function selectAll(mysqli $connection, int $productId): array {
            try {
                $sql = "
                    SELECT R.vote, R.comment, U.username AS customerUsername FROM (
                        SELECT * FROM Feedbacks
                        WHERE productId = ?
                    ) AS R
                    INNER JOIN Users AS U
                    ON R.customerId = U.id;
                ";
                $statement = $connection->prepare($sql);
                $statement->bind_param('i', $productId);
                $statement->execute();
                $result = $statement->get_result();
                $statement->close();
                while($row = $result->fetch_assoc())
                    $feedbacks[] = self::fromRow($row);
                return $feedbacks ?? array();
            } catch(mysqli_sql_exception $_) {
                throw new InternalServerErrorResponse();
            }
        }

        function toTableRow(): string {
            $row = getFileContent(Settings::LIB_ABSOLUTE_PATH. '/tables/feedback-row.html');
            foreach($this as $property => $value) {
                if($value != null) $row = str_replace('{$' . $property .'}', $value, $row);
            }
            return $row;
        }
    }
?>