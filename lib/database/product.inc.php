<?php
    class Product {
        public ?int $id;
        public int $code;
        public ProductType $productType;
        public int $priceInCents;
        public int $discount;
        public int $availableQuantity;

        function __construct(?int $id, int $code, ProductType $productType, int $priceInCents, int $discount, int $availableQuantity) {
            $this->id = $id;
            $this->code = $code;
            $this->productType = $productType;
            $this->priceInCents = $priceInCents;
            $this->discount = $discount;
            $this->availableQuantity = $availableQuantity;
        }

        static function tableGroups(): string {
            return getFileContent(Settings::LIB_ABSOLUTE_PATH . '/tables/product-groups.html');
        }

        static function tableHeaders(): string {
            return getFileContent(Settings::LIB_ABSOLUTE_PATH . '/tables/product-headers.html');
        }

        static function formNew(): string {
            return getFileContent(Settings::LIB_ABSOLUTE_PATH . '/forms/product.html');
        }

        static function productFromForm(Validator $validator): Product {
            return new Product(null,
                               $validator->getPositiveInt('code'),
                               $validator->getProductType('product-type'),
                               $validator->getPriceInCents('price-in-cents'),
                               $validator->getDiscount('discount'),
                               $validator->getPositiveInt('available-quantity'));
        }

        function insert(mysqli $connection): void {
            $sql = "
                INSERT INTO Products
                (code, productType, price, discount, availableQuantity)
                VALUES (?, ?, ? / 100, ?, ?);
            ";
            $statement = $connection->prepare($sql);
            $formattedProductType = $this->productType->toMysqlString();
            $statement->bind_param("isiii", $this->code, $formattedProductType, $this->priceInCents, $this->discount, $this->availableQuantity);
            $statement->execute();
            $this->id = $connection->insert_id;
            $statement->close();
        }

        static function productSelectNumberOfPages(mysqli $connection, string $table): int {
            try {
                $sql = "
                    SELECT COUNT(*) AS records
                    FROM " . $table . ";
                ";
                $statement = $connection->prepare($sql);
                $statement->execute();
                $result = $statement->get_result();
                $row = $result->fetch_assoc();
                $pages = intdiv($row['records'], Settings::RECORDS_PER_PAGE);
                $statement->close();
                return $pages;
            } catch(mysqli_sql_exception $_) {
                throw new InternalServerErrorResponse();
            }
        }

        static function fromRow(array $row): Product {
            return new Product($row['id'],
                               $row['code'],
                               ProductType::fromMysqlString($row['productType']),
                               $row['priceInCents'],
                               $row['discount'],
                               $row['availableQuantity']);
        }
    }

    class Console extends Product {
        public string $name;
        public GameTypes $gameTypes;

        function __construct(Product $product, string $name, GameTypes $gameTypes) {
            foreach($product as $property => $value)
                $this->{$property} = $value;
            $this->name = $name;
            $this->gameTypes = $gameTypes;
        }

        static function tableGroups(): string {
            return parent::tableGroups() . getFileContent(Settings::LIB_ABSOLUTE_PATH . '/tables/console-groups.html');
        }

        static function tableHeaders(): string {
            return parent::tableHeaders() . getFileContent(Settings::LIB_ABSOLUTE_PATH . '/tables/console-headers.html');
        }

        static function formNew(): string {
            $form = parent::formNew() . getFileContent(Settings::LIB_ABSOLUTE_PATH. '/forms/console.html');
            $form = str_replace('{$basePath}', URL_ROOT_PATH, $form);
            foreach(get_class_vars('Console') as $property => $value)
                $form = str_replace('{$' . $property . '}', '', $form);
            foreach(get_class_vars('GameTypes') as $property => $value)
                $form = str_replace('{$gameTypes->' . $property . '}', '', $form);
            return $form;
        }

        static function fromForm(Validator $validator): Console {
            $product = parent::productFromForm($validator);
            return new Console($product, $validator->getNonEmptyString('name'), GameTypes::fromForm($validator));
        }

        function insert(mysqli $connection): void {
            try {
                $connection->begin_transaction();
                parent::insert($connection);
                $sql = "
                    INSERT INTO Consoles
                    (id, name, gameTypes)
                    VALUES (?, ?, ?);
                ";
                $statement = $connection->prepare($sql);
                $formattedGameTypes = $this->gameTypes->toMysqlString();
                $statement->bind_param("iss", $this->id, $this->name, $formattedGameTypes);
                $statement->execute();
                $statement->close();
                $connection->commit();
            } catch(mysqli_sql_exception $_) {
                $connection->rollback();
                throw new UnprocessableContentResponse();
            }
        }

        static function selectNumberOfPages(mysqli $connection): int {
            return parent::productSelectNumberOfPages($connection, 'Consoles');
        }

        static function fromRow(array $row): Console {
            return new Console(parent::fromRow($row), $row['name'], GameTypes::fromMysqlString($row['gameTypes']));
        }

        static function selectPage(mysqli $connection, int $page): array {
            try {
                $sql = "
                    SELECT P.id AS id, code, productType, price * 100 AS priceInCents, discount, availableQuantity,
                        name, gameTypes
                    FROM Products as P
                    INNER JOIN Consoles as C
                    ON C.id = P.id
                    LIMIT ?, ?;
                ";
                $statement = $connection->prepare($sql);
                $offset = $page * Settings::RECORDS_PER_PAGE;
                $statement->bind_param("ii", $offset, Settings::RECORDS_PER_PAGE);
                $statement->execute();
                $result = $statement->get_result();
                while($row = $result->fetch_assoc())
                    $consoles[] = Console::fromRow($row);
                $statement->close();
                return $consoles;
            } catch(mysqli_sql_exception $_) {
                echo $_->getMessage();
                throw new UnprocessableContentResponse();
            }
        }
    }

    class Videogame extends Product {
        public string $title;
        public string $plot;
        public int $releaseYear;

        function __construct(Product $product, string $title, string $plot, int $releaseYear) {
            foreach($product as $property => $value)
                $this->{$property} = $value;
            $this->title = $title;
            $this->plot = $plot;
            $this->releaseYear = $releaseYear;
        }

        static function tableGroups(): string {
            return parent::tableGroups() . getFileContent(Settings::LIB_ABSOLUTE_PATH . '/tables/videogame-groups.html');
        }

        static function tableHeaders(): string {
            return parent::tableHeaders() . getFileContent(Settings::LIB_ABSOLUTE_PATH . '/tables/videogame-headers.html');
        }

        static function formNew(): string {
            $form = parent::formNew(Settings::LIB_ABSOLUTE_PATH) . getFileContent(Settings::LIB_ABSOLUTE_PATH . '/forms/videogame.html');
            $form = str_replace('{$basePath}', URL_ROOT_PATH, $form);
            foreach(get_class_vars('Videogame') as $property => $value)
                $form = str_replace('{$' . $property . '}', '', $form);
            return $form;
        }

        static function fromForm(Validator $validator): Videogame {
            $product = parent::productFromForm($validator);
            return new Videogame($product,
                                 $validator->getNonEmptyString('title'),
                                 $validator->getNonEmptyString('plot'),
                                 $validator->getPositiveInt('release-year'));
        }

        function insert(mysqli $connection): void {
            try {
                $connection->begin_transaction();
                parent::insert($connection);
                $sql = "
                    INSERT INTO Videogames
                    (id, title, plot, releaseYear)
                    VALUES (?, ?, ?, ?);
                ";
                $statement = $connection->prepare($sql);
                $statement->bind_param("issi", $this->id, $this->title, $this->plot, $this->releaseYear);
                $statement->execute();
                $statement->close();
                $connection->commit();
            } catch(mysqli_sql_exception $_) {
                $connection->rollback();
                throw new UnprocessableContentResponse();
            }
        }

        static function selectNumberOfPages(mysqli $connection): int {
            return parent::productSelectNumberOfPages($connection, 'Videogames');
        }
    }

    class Accessory extends Product {
        public string $name;
        public AccessoryType $type;

        function __construct(Product $product, string $name, AccessoryType $type) {
            foreach($product as $property => $value)
                $this->{$property} = $value;
            $this->name = $name;
            $this->type = $type;
        }

        static function tableGroups(): string {
            return parent::tableGroups() . getFileContent(Settings::LIB_ABSOLUTE_PATH . '/tables/accessory-groups.html');
        }

        static function tableHeaders(): string {
            return parent::tableHeaders() . getFileContent(Settings::LIB_ABSOLUTE_PATH . '/tables/accessory-headers.html');
        }

        static function formNew(): string {
            $form = parent::formNew() . getFileContent(Settings::LIB_ABSOLUTE_PATH . '/forms/accessory.html');
            $form = str_replace('{$basePath}', URL_ROOT_PATH, $form);
            foreach(get_class_vars('Accessory') as $property => $value)
                $form = str_replace('{$' . $property . '}', '', $form);
            foreach(AccessoryType::cases() as $accessoryType)
                $form = str_replace('{$type->' . $accessoryType->value . '}', '', $form);
            return $form;
        }

        static function fromForm(Validator $validator): Accessory {
            $product = parent::productFromForm($validator);
            return new Accessory($product,
                                 $validator->getNonEmptyString('name'),
                                 $validator->getAccessoryType('type'));
        }

        function insert(mysqli $connection): void {
            try {
                $connection->begin_transaction();
                parent::insert($connection);
                $sql = "
                    INSERT INTO Accessories
                    (id, name, type)
                    VALUES (?, ?, ?);
                ";
                $statement = $connection->prepare($sql);
                $formattedType = $this->type->toMysqlString();
                $statement->bind_param("iss", $this->id, $this->name, $formattedType);
                $statement->execute();
                $statement->close();
                $connection->commit();
            } catch(mysqli_sql_exception $_) {
                $connection->rollback();
                throw new UnprocessableContentResponse();
            }
        }

        static function selectNumberOfPages(mysqli $connection): int {
            return parent::productSelectNumberOfPages($connection, 'Accessories');
        }
    }

    class Guide extends Product {
        public string $title;

        function __construct(Product $product, string $title) {
            foreach($product as $property => $value)
                $this->{$property} = $value;
            $this->title = $title;
        }

        static function tableGroups(): string {
            return parent::tableGroups() . getFileContent(Settings::LIB_ABSOLUTE_PATH . '/tables/guide-groups.html');
        }

        static function tableHeaders(): string {
            return parent::tableHeaders() . getFileContent(Settings::LIB_ABSOLUTE_PATH . '/tables/guide-headers.html');
        }

        static function formNew(): string {
            $form = parent::formNew() . getFileContent(Settings::LIB_ABSOLUTE_PATH . '/forms/guide.html');
            $form = str_replace('{$basePath}', URL_ROOT_PATH, $form);
            foreach(get_class_vars('Guide') as $property => $value)
                $form = str_replace('{$' . $property . '}', '', $form);
            return $form;
        }

        static function fromForm(Validator $validator): Guide {
            $product = parent::productFromForm($validator);
            return new Guide($product, $validator->getNonEmptyString('title'));
        }

        function insert(mysqli $connection): void {
            try {
                $connection->begin_transaction();
                parent::insert($connection);
                $sql = "
                    INSERT INTO Guides
                    (id, title)
                    VALUES (?, ?);
                ";
                $statement = $connection->prepare($sql);
                $statement->bind_param("is", $this->id, $this->title);
                $statement->execute();
                $statement->close();
                $connection->commit();
            } catch(mysqli_sql_exception $_) {
                $connection->rollback();
                throw new UnprocessableContentResponse();
            }
        }

        static function selectNumberOfPages(mysqli $connection): int {
            return parent::productSelectNumberOfPages($connection, 'Guides');
        }
    }

    enum ProductType: string {
        case CONSOLE = 'console';
        case VIDEOGAME = 'videogame';
        case ACCESSORY = 'accessory';
        case GUIDE = 'guide';

        static function formSelect(): string {
            return getFileContent(Settings::LIB_ABSOLUTE_PATH . '/forms/product-type.html');
        }

        function toMysqlString(): string {
            return $this->name;
        }

        static function fromString(string $s): ProductType {
            foreach(self::cases() as $productType) {
                if($s == $productType->value)
                    return $productType;
            }
            throw new BadRequestResponse();
        }

        static function fromMysqlString(string $s): ProductType {
            foreach(self::cases() as $productType) {
                if($s == $productType->name)
                    return $productType;
            }
            throw new Error('Unknown ProductType: ' . $s);
        }
    }

    class GameTypes {
        public bool $legacy;
        public bool $cd;
        public bool $dvd;
        public bool $digital;

        function __construct(bool $legacy, bool $cd, bool $dvd, bool $digital) {
            $this->legacy = $legacy;
            $this->cd = $cd;
            $this->dvd = $dvd;
            $this->digital = $digital;
        }

        static function fromForm(Validator $validator): GameTypes {
            return new GameTypes($validator->getBool('game-types-legacy'),
                                 $validator->getBool('game-types-cd'),
                                 $validator->getBool('game-types-dvd'),
                                 $validator->getBool('game-types-digital'));
        }

        static function fromMysqlString(string $s): GameTypes {
            return new GameTypes(str_contains($s, 'LEGACY'), str_contains($s, 'CD'), str_contains($s, 'DVD'), str_contains($s, 'DIGITAL'));
        }

        function toMysqlString(): string {
            $s = '';
            if($this->legacy) $s .= 'LEGACY,';
            if($this->cd) $s .= 'CD,';
            if($this->dvd) $s .= 'DVD,';
            if($this->digital) $s .= 'DIGITAL,';
            if(str_ends_with($s, ',')) $s = substr($s, 0, strlen($s) - 1);
            return $s;
        }
    }

    enum AccessoryType: string {
        case AUDIO = 'audio';
        case VIDEO = 'video';
        case INPUT = 'input';

        function toMysqlString(): string {
            return $this->name;
        }

        static function fromString(string $s): AccessoryType {
            foreach(self::cases() as $accessoryType) {
                if($s == $accessoryType->value)
                    return $accessoryType;
            }
            throw new BadRequestResponse();
        }

        static function fromMysqlString(string $s): AccessoryType {
            foreach(self::cases() as $accessoryType) {
                if($s == $accessoryType->name)
                    return $accessoryType;
            }
            throw new Error('Unknown AccessoryType: ' . $s);
        }
    }
?>