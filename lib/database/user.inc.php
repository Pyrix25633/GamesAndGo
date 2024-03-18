<?php
    /*
        Classi per la gestione degli utenti,
        ci sono metodi per la selezione e l'aggiornamento
        e per la creazione di form vuoti o precompilati
        e di tabelle con una struttura modulare grazie alla
        programmazione ad oggetti.

        Tutti gli altri file nella cartella 'database' seguono la stessa logica
    */

    class User {
        public ?int $id;
        public UserType $userType;
        public string $name;
        public string $surname;
        public Gender $gender;
        public DateTime $dateOfBirth;
        public string $documentState;
        public DocumentType $documentType;
        public string $documentNumber;
        public string $username;
        public ?string $password;
        public ?string $passwordHash;

        function __construct(?int $id, UserType $userType, string $name, string $surname, Gender $gender, DateTime $dateOfBirth, $documentState,
                             DocumentType $documentType, string $documentNumber, string $username, ?string $password, ?string $passwordHash) {
            $this->id = $id;
            $this->userType = $userType;
            $this->name = $name;
            $this->surname = $surname;
            $this->gender = $gender;
            $this->dateOfBirth = $dateOfBirth;
            $this->documentState = $documentState;
            $this->documentType = $documentType;
            $this->documentNumber = $documentNumber;
            $this->username = $username;
            $this->password = $password;
            $this->passwordHash = $passwordHash;
        }

        static function tableGroups(): string {
            return getFileContent(Settings::LIB_ABSOLUTE_PATH . '/tables/user-groups.html');
        }

        static function tableHeaders(): string {
            return getFileContent(Settings::LIB_ABSOLUTE_PATH . '/tables/user-headers.html');
        }

        static function formNew(): string {
            return getFileContent(Settings::LIB_ABSOLUTE_PATH . '/forms/user.html');
        }

        function formUpdate(): string {
            return self::formNew();
        }

        static function userFromForm(Validator $validator, UserType $userType): User {
            try {
                $id = $validator->getPositiveInt('id');
            } catch(Response $_) {
                $id = null;
            }
            $password = $validator->getNonEmptyString('password');
            $confirmPassword = $validator->getNonEmptyString('confirm-password');
            if($password != $confirmPassword) throw new UnprocessableContentResponse('confirm-password');
            return new User($id,
                            $userType,
                            $validator->getNonEmptyString('name'),
                            $validator->getNonEmptyString('surname'),
                            $validator->getGender('gender'),
                            $validator->getDateTime('date-of-birth'),
                            $validator->getState('document-state'),
                            $validator->getDocumentType('document-type'),
                            $validator->getNonEmptyString('document-number'),
                            $validator->getNonEmptyString('username'),
                            $password,
                            null);
        }

        function insert(mysqli $connection): void {
            $sql = "
                INSERT INTO Users
                (userType, name, surname, gender, dateOfBirth, documentState, documentType, documentNumber, username, passwordHash)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, SHA2(?, 256));
            ";
            $statement = $connection->prepare($sql);
            $formattedUserType = $this->userType->toMysqlString();
            $formattedDocumentType = $this->documentType->toMysqlString();
            $formattedGender = $this->gender->toMysqlString();
            $formattedDateOfBirth = $this->dateOfBirth->format('Y-m-d');
            $statement->bind_param("ssssssssss", $formattedUserType, $this->name, $this->surname, $formattedGender, $formattedDateOfBirth,
                $this->documentState, $formattedDocumentType, $this->documentNumber, $this->username, $this->password);
            $statement->execute();
            $this->id = $connection->insert_id;
            $statement->close();
        }

        function update(mysqli $connection): void {
            $sql = "
                UPDATE Users
                SET name = ?, surname = ?, gender = ?, dateOfBirth = ?, documentState = ?,
                    documentType = ?, documentNumber = ?, username = ?, passwordHash = SHA2(?, 256)
                WHERE id = ?;
            ";
            $statement = $connection->prepare($sql);
            $formattedGender = $this->gender->toMysqlString();
            $formattedDocumentType = $this->documentType->toMysqlString();
            $formattedDateOfBirth = $this->dateOfBirth->format('Y-m-d');
            $statement->bind_param('ssssssissi', $this->name, $this->surname, $formattedGender, $formattedDateOfBirth, $this->documentState,
                $formattedDocumentType, $this->documentNumber, $this->username, $this->password, $this->id);
            $statement->execute();
            $statement->close();
        }

        static function login(mysqli $connection, string $username, string $password): void {
            try {
                $sql = "
                    SELECT id, userType, passwordHash
                    FROM Users
                    WHERE username = ?;
                ";
                $statement = $connection->prepare($sql);
                $statement->bind_param('s', $username);
                $statement->execute();
                $result = $statement->get_result();
                $row = $result->fetch_assoc();
                $statement->close();
                if($row == null) throw new NotFoundResponse('username');
                $passwordHash = $row['passwordHash'];
                if($passwordHash != hash('sha256', $password)) throw new UnauthorizedResponse('password');
                Auth::login($row['id'], UserType::fromMysqlString($row['userType']), $row['passwordHash']);
            } catch(mysqli_sql_exception $_) {
                throw new InternalServerErrorResponse();
            }
        }

        static function selectNumberOfPages(mysqli $connection): int {
            try {
                $sql = "
                    SELECT COUNT(*) AS records
                    FROM Users;
                ";
                $statement = $connection->prepare($sql);
                $statement->execute();
                $result = $statement->get_result();
                $statement->close();
                $row = $result->fetch_assoc();
                $pages = intdiv($row['records'], Settings::RECORDS_PER_PAGE);
                return $pages;
            } catch(mysqli_sql_exception $_) {
                throw new InternalServerErrorResponse();
            }
        }

        static function selectPage(mysqli $connection, int $page): array {
            try {
                $sql = "
                    SELECT *
                    FROM Users
                    LIMIT ?, ?;
                ";
                $statement = $connection->prepare($sql);
                $recordsPerPage = Settings::RECORDS_PER_PAGE;
                $offset = $page * $recordsPerPage;
                $statement->bind_param("ii", $offset, $recordsPerPage);
                $statement->execute();
                $result = $statement->get_result();
                $statement->close();
                while($row = $result->fetch_array())
                    $users[] = self::fromRow($row);
                return $users ?? array();
            } catch(mysqli_sql_exception $_) {
                throw new UnprocessableContentResponse();
            }
        }

        static function fromRow(array $row): User {
            return new User(intval($row['id']),
                            UserType::fromMysqlString($row['userType']),
                            $row['name'],
                            $row['surname'],
                            Gender::fromMysqlString($row['gender']),
                            new DateTime($row['dateOfBirth']),
                            $row['documentState'],
                            DocumentType::fromMysqlString($row['documentType']),
                            $row['documentNumber'],
                            $row['username'],
                            null,
                            $row['passwordHash']);
        }

        function toAdminTableRow(): string {
            $row = getFileContent(Settings::LIB_ABSOLUTE_PATH. '/tables/user-row.html');
            foreach($this as $property => $value) {
                switch($property) {
                    case 'password': break;
                    case 'passwordHash': break;
                    case 'userType':
                    case 'gender':
                    case 'documentType':
                        $row = str_replace('{$' . $property . '}', $value->toUiString(), $row);
                        break;
                    case 'dateOfBirth': $row = str_replace('{$' . $property . '}', $value->format('Y/m/d'), $row); break;
                    default: $row = str_replace('{$' . $property . '}', $value, $row); break;
                }
            }
            $row .= '<td><a href="./update/index.php?id=' . $this->id . '">Update</a></td>';
            return $row;
        }

        static function select(mysqli $connection, int $id): User {
            try {
                $sql = "
                    SELECT *
                    FROM Users
                    WHERE id = ?;
                ";
                $statement = $connection->prepare($sql);
                $statement->bind_param('i', $id);
                $statement->execute();
                $result = $statement->get_result();
                $row = $result->fetch_assoc();
                $statement->close();
                if($row == null) throw new NotFoundResponse('id');
                return self::fromRow($row);
            } catch(mysqli_sql_exception $_) {
                throw new InternalServerErrorResponse();
            }
        }

        static function selectRowResult(mysqli $connection, int $id, string $fieldsAndTable): array {
            try {
                $sql = "
                    " . $fieldsAndTable . "
                    WHERE id = ?;
                ";
                $statement = $connection->prepare($sql);
                $statement->bind_param('i', $id);
                $statement->execute();
                $result = $statement->get_result();
                $statement->close();
                $row = $result->fetch_assoc();
                if($row == null) throw new NotFoundResponse('id');
                return $row;
            } catch(mysqli_sql_exception $_) {
                throw new InternalServerErrorResponse();
            }
        }

        static function selectUser(mysqli $connection, int $id): User {
            try {
                $sql = "
                    SELECT *
                    FROM Users
                    WHERE id = ?;
                ";
                $statement = $connection->prepare($sql);
                $statement->bind_param('i', $id);
                $statement->execute();
                $result = $statement->get_result();
                $row = $result->fetch_assoc();
                $statement->close();
                if($row == null) throw new NotFoundResponse('id');
                switch(UserType::fromMysqlString($row['userType'])) {
                    case UserType::CUSTOMER: return Customer::fromRow(array_merge($row, Customer::selectRow($connection, $id)));
                    case UserType::SELLER: return Seller::fromRow(array_merge($row, Seller::selectRow($connection, $id)));
                    case UserType::ADMIN: return Admin::fromRow(array_merge($row, Admin::selectRow($connection, $id)));
                }
            } catch(mysqli_sql_exception $_) {
                throw new InternalServerErrorResponse();
            }
        }


        function purchased(mysqli $connection, int $productId): bool {
            try {
                $sql = "
                    SELECT COUNT(*) AS records
                    FROM ProductsOnPurchases
                    WHERE purchaseId IN (
                        SELECT id
                        FROM Purchases
                        WHERE customerId = ?
                    ) AND productId = ?;
                ";
                $statement = $connection->prepare($sql);
                $statement->bind_param('ii', $this->id, $productId);
                $statement->execute();
                $result = $statement->get_result();
                $statement->close();
                $row = $result->fetch_assoc();
                if($row == null) return false;
                return intval($row['records']) > 0;
            } catch(mysqli_sql_exception $_) {
                throw new InternalServerErrorResponse();
            }
        }
    }

    class Customer extends User {
        public string $addressStreetType;
        public string $addressStreetName;
        public int $addressHouseNumber;
        public int $phoneNumberPrefix;
        public int $phoneNumber;
        public string $emailAddress;

        function __construct(User $user, string $addressStreetType, string $addressStreetName, int $addressHouseNumber,
                             int $phoneNumberPrefix, int $phoneNumber, string $emailAddress) {
            foreach($user as $property => $value)
                $this->{$property} = $value;
            $this->addressStreetType = $addressStreetType;
            $this->addressStreetName = $addressStreetName;
            $this->addressHouseNumber = $addressHouseNumber;
            $this->phoneNumberPrefix = $phoneNumberPrefix;
            $this->phoneNumber = $phoneNumber;
            $this->emailAddress = $emailAddress;
        }

        static function formNew(): string {
            $form = parent::formNew() . getFileContent(Settings::LIB_ABSOLUTE_PATH. '/forms/customer.html');
            $form = str_replace('{$basePath}', URL_ROOT_PATH, $form);
            foreach(get_class_vars('Customer') as $property => $value) {
                switch($property) {
                    case 'gender':
                        foreach(Gender::cases() as $gender)
                            $form = str_replace('{$' . $property . '::' . $gender->name . '}', '', $form);
                        break;
                    case 'documentType':
                        foreach(DocumentType::cases() as $documentType)
                            $form = str_replace('{$' . $property . '::' . $documentType->name . '}', '', $form);
                        break;
                    default: $form = str_replace('{$' . $property . '}', '', $form); break;
                }
            }
            return $form;
        }

        function formUpdate(): string {
            $form = parent::formUpdate() . getFileContent(Settings::LIB_ABSOLUTE_PATH . '/forms/customer.html');
            $form = str_replace('{$basePath}', URL_ROOT_PATH, $form);
            foreach($this as $property => $value) {
                switch($property) {
                    case 'userType': break;
                    case 'gender':
                        foreach(Gender::cases() as $gender)
                            $form = str_replace('{$' . $property . '::' . $gender->name . '}', $gender == $value ? 'selected' : '', $form);
                        break;
                    case 'dateOfBirth': $form = str_replace('{$' . $property . '}', $value->format('Y-m-d'), $form); break;
                    case 'documentType':
                        foreach(DocumentType::cases() as $documentType)
                            $form = str_replace('{$' . $property . '::' . $documentType->name . '}', $documentType == $value ? 'selected' : '', $form);
                        break;
                    case 'password': break;
                    case 'passwordHash': break;
                    default: $form = str_replace('{$' . $property . '}', $value, $form); break;
                }
            }
            return $form;
        }

        static function fromForm(Validator &$validator): Customer {
            $user = parent::userFromForm($validator, UserType::CUSTOMER);
            return new Customer($user,
                                $validator->getNonEmptyString('street-type'),
                                $validator->getNonEmptyString('street-name'),
                                $validator->getPositiveInt('house-number'),
                                $validator->getPhoneNumberPrefix('phone-number-prefix'),
                                $validator->getPhoneNumber('phone-number'),
                                $validator->getEmail('email-address'));
        }

        function insert(mysqli $connection): void {
            try {
                $connection->begin_transaction();
                parent::insert($connection);
                $sql = "
                    INSERT INTO Customers
                    (id, addressStreetType, addressStreetName, addressHouseNumber, phoneNumberPrefix, phoneNumber, emailAddress)
                    VALUES (?, ?, ?, ?, ?, ?, ?);
                ";
                $statement = $connection->prepare($sql);
                $statement->bind_param("issiiis", $this->id, $this->addressStreetType, $this->addressStreetName, $this->addressHouseNumber,
                    $this->phoneNumberPrefix, $this->phoneNumber, $this->emailAddress);
                $statement->execute();
                $statement->close();
                $connection->commit();
            } catch(mysqli_sql_exception $_) {
                $connection->rollback();
                throw new UnprocessableContentResponse();
            }
        }

        function update(mysqli $connection): void {
            try {
                $connection->begin_transaction();
                parent::update($connection);
                $sql = "
                    UPDATE Customers
                    SET addressStreetType = ?, addressStreetName = ?, addressHouseNumber = ?, phoneNumberPrefix = ?, phoneNumber = ?, emailAddress = ?
                    WHERE id = ?;
                ";
                $statement = $connection->prepare($sql);
                $statement->bind_param("ssiiisi", $this->addressStreetType, $this->addressStreetName, $this->addressHouseNumber,
                    $this->phoneNumberPrefix, $this->phoneNumber, $this->emailAddress, $this->id);
                $statement->execute();
                $statement->close();
                $connection->commit();
            } catch(mysqli_sql_exception $_) {
                $connection->rollback();
                throw new UnprocessableContentResponse();
            }
        }

        static function selectRow(mysqli $connection, int $id): array {
            return parent::selectRowResult($connection, $id, '
                SELECT id, addressStreetType, addressStreetName, addressHouseNumber, phoneNumberPrefix, phoneNumber, emailAddress
                FROM Customers');
        }

        static function fromRow(array $row): Customer {
            return new Customer(parent::fromRow($row),
                                $row['addressStreetType'],
                                $row['addressStreetName'],
                                intval($row['addressHouseNumber']),
                                intval($row['phoneNumberPrefix']),
                                intval($row['phoneNumber']),
                                $row['emailAddress']);
        }
    }

    class Seller extends User {
        public string $addressStreetType;
        public string $addressStreetName;
        public int $addressHouseNumber;
        public int $phoneNumberPrefix;
        public int $phoneNumber;
        public string $emailAddress;
        public int $code;
        public SellerRole $role;

        function __construct(User $user, string $addressStreetType, string $addressStreetName, string $addressHouseNumber,
                             int $phoneNumberPrefix, int $phoneNumber, string $emailAddress, int $code, SellerRole $role) {
            foreach($user as $property => $value)
                $this->{$property} = $value;
            $this->addressStreetType = $addressStreetType;
            $this->addressStreetName = $addressStreetName;
            $this->addressHouseNumber = $addressHouseNumber;
            $this->phoneNumberPrefix = $phoneNumberPrefix;
            $this->phoneNumber = $phoneNumber;
            $this->emailAddress = $emailAddress;
            $this->code = $code;
            $this->role = $role;
        }

        static function formNew(): string {
            $form = parent::formNew() . getFileContent(Settings::LIB_ABSOLUTE_PATH. '/forms/seller.html');
            $form = str_replace('{$basePath}', URL_ROOT_PATH, $form);
            foreach(get_class_vars('Seller') as $property => $value) {
                switch($property) {
                    case 'gender':
                        foreach(Gender::cases() as $gender)
                            $form = str_replace('{$gender::' . $gender->name . '}', '', $form);
                        break;
                    case 'documentType':
                        foreach(DocumentType::cases() as $documentType)
                            $form = str_replace('{$documentType::' . $documentType->name . '}', '', $form);
                        break;
                    case 'role':
                        foreach(SellerRole::cases() as $role)
                            $form = str_replace('{$documentType::' . $role->name . '}', '', $form);
                        break;
                    default: $form = str_replace('{$' . $property . '}', '', $form); break;
                }
            }
            return $form;
        }

        function formUpdate(): string {
            $form = parent::formUpdate() . getFileContent(Settings::LIB_ABSOLUTE_PATH . '/forms/seller.html');
            $form = str_replace('{$basePath}', URL_ROOT_PATH, $form);
            foreach($this as $property => $value) {
                switch($property) {
                    case 'userType': break;
                    case 'gender':
                        foreach(Gender::cases() as $gender)
                            $form = str_replace('{$' . $property . '::' . $gender->name . '}', $gender == $value ? 'selected' : '', $form);
                        break;
                    case 'dateOfBirth': $form = str_replace('{$' . $property . '}', $value->format('Y-m-d'), $form); break;
                    case 'documentType':
                        foreach(DocumentType::cases() as $documentType)
                            $form = str_replace('{$' . $property . '::' . $documentType->name . '}', $documentType == $value ? 'selected' : '', $form);
                        break;
                    case 'role':
                        foreach(SellerRole::cases() as $sellerRole)
                            $form = str_replace('{$' . $property . '::' . $sellerRole->name . '}', $sellerRole == $value ? 'selected' : '', $form);
                        break;
                    case 'password': break;
                    case 'passwordHash': break;
                    default: $form = str_replace('{$' . $property . '}', $value, $form); break;
                }
            }
            return $form;
        }

        static function fromForm(Validator &$validator): Seller {
            $user = parent::userFromForm($validator, UserType::SELLER);
            return new Seller($user,
                              $validator->getNonEmptyString('street-type'),
                              $validator->getNonEmptyString('street-name'),
                              $validator->getPositiveInt('house-number'),
                              $validator->getPhoneNumberPrefix('phone-number-prefix'),
                              $validator->getPhoneNumber('phone-number'),
                              $validator->getEmail('email-address'),
                              $validator->getPositiveInt('code'),
                              $validator->getSellerRole('role'));
        }

        function insert(mysqli $connection): void {
            try {
                $connection->begin_transaction();
                parent::insert($connection);
                $sql = "
                    INSERT INTO Sellers
                    (id, addressStreetType, addressStreetName, addressHouseNumber, phoneNumberPrefix, phoneNumber, emailAddress, code, role)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?);
                ";
                $statement = $connection->prepare($sql);
                $formattedRole = $this->role->toMysqlString();
                $statement->bind_param("issiiisis", $this->id, $this->addressStreetType, $this->addressStreetName, $this->addressHouseNumber,
                    $this->phoneNumberPrefix, $this->phoneNumber, $this->emailAddress, $this->code, $formattedRole);
                $statement->execute();
                $statement->close();
                $connection->commit();
            } catch(mysqli_sql_exception $_) {
                $connection->rollback();
                throw new UnprocessableContentResponse();
            }
        }

        function update(mysqli $connection): void {
            try {
                $connection->begin_transaction();
                parent::update($connection);
                $sql = "
                    UPDATE Sellers
                    SET addressStreetType = ?, addressStreetName = ?, addressHouseNumber = ?, phoneNumberPrefix = ?,
                        phoneNumber = ?, emailAddress = ?, code = ?, role = ?
                    WHERE id = ?;
                ";
                $statement = $connection->prepare($sql);
                $formattedRole = $this->role->toMysqlString();
                $statement->bind_param("ssiiisisi", $this->addressStreetType, $this->addressStreetName, $this->addressHouseNumber,
                    $this->phoneNumberPrefix, $this->phoneNumber, $this->emailAddress, $this->code, $formattedRole, $this->id);
                $statement->execute();
                $statement->close();
                $connection->commit();
            } catch(mysqli_sql_exception $_) {
                $connection->rollback();
                throw new UnprocessableContentResponse();
            }
        }

        static function selectRow(mysqli $connection, int $id): array {
            return parent::selectRowResult($connection, $id, '
                SELECT id, addressStreetType, addressStreetName, addressHouseNumber, phoneNumberPrefix, phoneNumber, emailAddress, code, role
                FROM Sellers');
        }

        static function fromRow(array $row): Seller {
            return new Seller(parent::fromRow($row),
                                $row['addressStreetType'],
                                $row['addressStreetName'],
                                intval($row['addressHouseNumber']),
                                intval($row['phoneNumberPrefix']),
                                intval($row['phoneNumber']),
                                $row['emailAddress'],
                                intval($row['code']),
                                SellerRole::fromMysqlString($row['role']));
        }
    }

    class Admin extends User {
        function __construct(User $user) {
            foreach($user as $property => $value)
                $this->{$property} = $value;
        }

        static function formNew(): string {
            $form = parent::formNew();
            $form = str_replace('{$basePath}', URL_ROOT_PATH, $form);
            foreach(get_class_vars('Seller') as $property => $value) {
                switch($property) {
                    case 'gender':
                        foreach(Gender::cases() as $gender)
                            $form = str_replace('{$gender::' . $gender->name . '}', '', $form);
                        break;
                    case 'documentType':
                        foreach(DocumentType::cases() as $documentType)
                            $form = str_replace('{$documentType::' . $documentType->name . '}', '', $form);
                        break;
                    default: $form = str_replace('{$' . $property . '}', '', $form); break;
                }
            }
            return $form;
        }

        function formUpdate(): string {
            $form = parent::formUpdate();
            $form = str_replace('{$basePath}', URL_ROOT_PATH, $form);
            foreach($this as $property => $value) {
                switch($property) {
                    case 'userType': break;
                    case 'gender':
                        foreach(Gender::cases() as $gender)
                            $form = str_replace('{$' . $property . '::' . $gender->name . '}', $gender == $value ? 'selected' : '', $form);
                        break;
                    case 'dateOfBirth': $form = str_replace('{$' . $property . '}', $value->format('Y-m-d'), $form); break;
                    case 'documentType':
                        foreach(DocumentType::cases() as $documentType)
                            $form = str_replace('{$' . $property . '::' . $documentType->name . '}', $documentType == $value ? 'selected' : '', $form);
                        break;
                    case 'password': break;
                    case 'passwordHash': break;
                    default: $form = str_replace('{$' . $property . '}', $value, $form); break;
                }
            }
            return $form;
        }

        static function fromForm(Validator &$validator): Admin {
            $user = parent::userFromForm($validator, UserType::ADMIN);
            return new Admin($user);
        }

        function insert(mysqli $connection): void {
            try {
                $connection->begin_transaction();
                parent::insert($connection);
                $sql = "
                    INSERT INTO Admins
                    (id)
                    VALUES (?);
                ";
                $statement = $connection->prepare($sql);
                $statement->bind_param('i', $this->id);
                $statement->execute();
                $statement->close();
                $connection->commit();
            } catch(mysqli_sql_exception $_) {
                echo $_->getMessage();
                exit;
                $connection->rollback();
                throw new UnprocessableContentResponse();
            }
        }

        function update(mysqli $connection): void {
            try {
                $connection->begin_transaction();
                parent::update($connection);
                $connection->commit();
            } catch(mysqli_sql_exception $_) {
                $connection->rollback();
                throw new UnprocessableContentResponse();
            }
        }

        static function selectRow(mysqli $connection, int $id): array {
            return parent::selectRowResult($connection, $id, '
                SELECT id
                FROM Admins');
        }

        static function fromRow(array $row): Admin {
            return new Admin(parent::fromRow($row));
        }
    }

    enum UserType: string {
        case CUSTOMER = 'customer';
        case SELLER = 'seller';
        case ADMIN = 'admin';

        static function formSelect(): string {
            return getFileContent(Settings::LIB_ABSOLUTE_PATH . '/forms/user-type.html');
        }

        function toMysqlString(): string {
            return $this->name;
        }

        function toUiString(): string {
            switch($this) {
                case self::CUSTOMER: return 'Customer';
                case self::SELLER: return 'Seller';
                case self::ADMIN: return 'Admin';
            }
        }

        static function fromString(string $s): UserType {
            foreach(self::cases() as $userType) {
                if($s == $userType->value)
                    return $userType;
            }
            throw new BadRequestResponse();
        }

        static function fromMysqlString(string $s): UserType {
            foreach(self::cases() as $userType) {
                if($s == $userType->name)
                    return $userType;
            }
            throw new Error('Unknown UserType: ' . $s);
        }
    }

    enum Gender: string {
        case MALE = 'male';
        case FEMALE = 'female';
        case OTHER = 'other';

        function toMysqlString(): string {
            return $this->name;
        }

        function toUiString(): string {
            switch($this) {
                case self::MALE: return 'Male';
                case self::FEMALE: return 'Female';
                case self::OTHER: return 'Other';
            }
        }

        static function fromString(string $s): Gender {
            foreach(self::cases() as $gender) {
                if($s == $gender->value)
                    return $gender;
            }
            throw new BadRequestResponse();
        }

        static function fromMysqlString(string $s): Gender {
            foreach(self::cases() as $gender) {
                if($s == $gender->name)
                    return $gender;
            }
            throw new Error('Unknown Gender: ' . $s);
        }
    }

    enum DocumentType: string {
        case ID = 'id';
        case PASSPORT = 'passport';
        case DRIVING_LICENSE = 'driving-license';

        function toMysqlString(): string {
            return $this->name;
        }

        function toUiString(): string {
            switch($this) {
                case self::ID: return 'ID';
                case self::PASSPORT: return 'Passport';
                case self::DRIVING_LICENSE: return 'Driving License';
            }
        }

        static function fromString(string $s): DocumentType {
            foreach(self::cases() as $documentType) {
                if($s == $documentType->value)
                    return $documentType;
            }
            throw new BadRequestResponse();
        }

        static function fromMysqlString(string $s): DocumentType {
            foreach(self::cases() as $documentType) {
                if($s == $documentType->name)
                    return $documentType;
            }
            throw new Error('Unknown DocumentType: ' . $s);
        }
    }

    enum SellerRole: string {
        case SHOP_ASSISTANT = 'shop-assistant';
        case WAREHOUSE_MAN = 'warehouse-man';

        function toMysqlString(): string {
            return $this->name;
        }

        static function fromString(string $s): SellerRole {
            foreach(self::cases() as $sellerRole) {
                if($s == $sellerRole->value)
                    return $sellerRole;
            }
            throw new BadRequestResponse();
        }

        static function fromMysqlString(string $s): SellerRole {
            foreach(self::cases() as $sellerRole) {
                if($s == $sellerRole->name)
                    return $sellerRole;
            }
            throw new Error('Unknown SellerRole: ' . $s);
        }
    }
?>