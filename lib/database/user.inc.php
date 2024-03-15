<?php
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

        static function formNew(): string {
            return getFileContent(Settings::LIB_ABSOLUTE_PATH . '/forms/user.html');
        }

        static function userFromForm(Validator $validator, UserType $userType): User {
            $password = $validator->getNonEmptyString('password');
            $confirmPassword = $validator->getNonEmptyString('confirm-password');
            if($password != $confirmPassword) throw new UnprocessableContentResponse('confirm-password');
            return new User(null,
                            $userType,
                            $validator->getNonEmptyString('name'),
                            $validator->getNonEmptyString('surname'),
                            $validator->getGender('gender'), $validator->getDateTime('date-of-birth'),
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
                (name, surname, username, passwordHash, dateOfBirth, documentState, documentType, documentNumber)
                VALUES (?, ?, ?, SHA2(?, 256), ?, ?, ?, ?);
            ";
            $statement = $connection->prepare($sql);
            $documentType = $this->documentType->name;
            $formattedDateOfBirth = $this->dateOfBirth->format('Y-m-d');
            $statement->bind_param("ssssssss", $this->name, $this->surname, $this->username, $this->password, $formattedDateOfBirth,
                $this->documentState, $documentType, $this->documentNumber);
            $statement->execute();
            $this->id = $connection->insert_id;
            $statement->close();
        }

        static function login(mysqli $connection, string $username, string $password): void {
            try {
                $sql = "
                    SELECT id, userType, passwordHash
                    FROM Users
                    WHERE username=?;
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
                Auth::login($row['id'], UserType::fromMysqlString($row['userType']));
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
        public ?int $loyaltyCardId;

        function __construct(User &$user, string $addressStreetType, string $addressStreetName, string $addressHouseNumber,
                             int $phoneNumberPrefix, int $phoneNumber, string $emailAddress, ?int $loyaltyCardId) {
            foreach($user as $property => $value)
                $this->{$property} = $value;
            $this->addressStreetType = $addressStreetType;
            $this->addressStreetName = $addressStreetName;
            $this->addressHouseNumber = $addressHouseNumber;
            $this->phoneNumberPrefix = $phoneNumberPrefix;
            $this->phoneNumber = $phoneNumber;
            $this->emailAddress = $emailAddress;
            $this->loyaltyCardId = $loyaltyCardId;
        }

        static function formNew(): string {
            $form = parent::formNew() . getFileContent(Settings::LIB_ABSOLUTE_PATH. '/forms/customer.html');
            $form = str_replace('{$basePath}', URL_ROOT_PATH, $form);
            foreach(get_class_vars('Customer') as $property => $value) {
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

        static function fromForm(Validator &$validator): Customer {
            $user = self::userFromForm($validator, UserType::CUSTOMER);
            return new Customer($user,
                                $validator->getNonEmptyString('street-type'),
                                $validator->getNonEmptyString('street-name'),
                                $validator->getPositiveInt('house-number'),
                                $validator->getPhoneNumberPrefix('phone-number-prefix'),
                                $validator->getPhoneNumber('phone-number'),
                                $validator->getEmail('email-address'),
                                null);
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
    }

    class Seller extends User {

    }

    class Admin extends User {

    }

    enum UserType: string {
        case CUSTOMER = 'customer';
        case SELLER = 'seller';
        case ADMIN = 'admin';

        function toMysqlString(): string {
            return $this->name;
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
        case DRIVING_LICENSE = 'diving-license';

        function toMysqlString(): string {
            return $this->name;
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
?>