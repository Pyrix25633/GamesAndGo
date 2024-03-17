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

        function formUpdate(): string {
            return self::formNew();
        }

        static function productFromForm(Validator $validator): Product {
            try {
                $id = $validator->getPositiveInt('id');
            } catch(Response $_) {
                $id = null;
            }
            return new Product($id,
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

        function update(mysqli $connection): void {
            $sql = "
                UPDATE Products
                SET code = ?, productType = ?, price = ? / 100, discount = ?, availableQuantity = ?
                WHERE id = ?;
            ";
            $statement = $connection->prepare($sql);
            $formattedProductType = $this->productType->toMysqlString();
            $statement->bind_param("isiiii", $this->code, $formattedProductType, $this->priceInCents, $this->discount, $this->availableQuantity, $this->id);
            $statement->execute();
            $statement->close();
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

        static function select(mysqli $connection, int $id): Product {
            try {
                $sql = "
                    SELECT id, code, productType, price * 100 AS priceInCents, discount, availableQuantity
                    FROM Products
                    WHERE id = ?;
                ";
                $statement = $connection->prepare($sql);
                $statement->bind_param('i', $id);
                $statement->execute();
                $result = $statement->get_result();
                $statement->close();
                $row = $result->fetch_assoc();
                if($row == null) throw new NotFoundResponse('id');
                switch(ProductType::fromMysqlString($row['productType'])) {
                    case ProductType::CONSOLE: return Console::fromRow(array_merge($row, Console::selectRow($connection, $id)));
                    case ProductType::VIDEOGAME: return Videogame::fromRow(array_merge($row, Videogame::selectRow($connection, $id)));
                    case ProductType::ACCESSORY: return Accessory::fromRow(array_merge($row, Accessory::selectRow($connection, $id)));
                    case ProductType::GUIDE: return Guide::fromRow(array_merge($row, Guide::selectRow($connection, $id)));
                }
            } catch(mysqli_sql_exception $_) {
                throw new InternalServerErrorResponse();
            }
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
                $statement->close();
                $row = $result->fetch_assoc();
                $pages = intdiv($row['records'], Settings::RECORDS_PER_PAGE);
                return $pages;
            } catch(mysqli_sql_exception $_) {
                throw new InternalServerErrorResponse();
            }
        }

        static function selectPageResult(mysqli $connection, string $parameters, string $join, int $page): object {
            try {
                $sql = "
                    SELECT P.id AS id, code, productType, price * 100 AS priceInCents, discount, availableQuantity,
                        " . $parameters . "
                    FROM Products as P
                    " . $join . "
                    LIMIT ?, ?;
                ";
                $statement = $connection->prepare($sql);
                $recordsPerPage = Settings::RECORDS_PER_PAGE;
                $offset = $page * $recordsPerPage;
                $statement->bind_param("ii", $offset, $recordsPerPage);
                $statement->execute();
                $result = $statement->get_result();
                $statement->close();
                return $result;
            } catch(mysqli_sql_exception $_) {
                throw new UnprocessableContentResponse();
            }
        }

        static function purchase(mysqli $connection, int $id, int $quantity): void {
            try {
                $sql = "
                    SELECT availableQuantity
                    FROM Products
                    WHERE id = ?;
                ";
                $statement = $connection->prepare($sql);
                $statement->bind_param('i', $id);
                $statement->execute();
                $result = $statement->get_result();
                $row = $result->fetch_assoc();
                $statement->close();
                if($row == null) throw new NotFoundResponse();
                if(intval($row['availableQuantity']) < $quantity) throw new UnprocessableContentResponse();
                $sql = "
                    UPDATE Products
                    SET availableQuantity = availableQuantity - ?
                    WHERE id = ?;
                ";
                $statement = $connection->prepare($sql);
                $statement->bind_param('ii', $quantity, $id);
                $statement->execute();
                $statement->close();
            } catch(mysqli_sql_exception $_) {
                throw new InternalServerErrorResponse();
            }
        }

        static function fromRow(array $row): Product {
            return new Product(intval($row['id']),
                               intval($row['code']),
                               ProductType::fromMysqlString($row['productType']),
                               intval($row['priceInCents']),
                               intval($row['discount']),
                               intval($row['availableQuantity']));
        }

        function toTableRow(): string {
            $row = getFileContent(Settings::LIB_ABSOLUTE_PATH. '/tables/product-row.html');
            $price = $this->priceInCents / 100;
            $row = str_replace('{$price}', number_format($price, 2), $row);
            $finalPrice = $price * (100 - $this->discount) / 100;
            $row = str_replace('{$finalPrice}', number_format($finalPrice, 2), $row);
            return $row;
        }

        function toSellerTableRow(): string {
            $row = $this->toTableRow();
            return $row . '<td><a href="' . URL_ROOT_PATH . '/seller/products/update/index.php?id=' . $this->id . '">Update</a></td>';
        }

        function toCustomerTableRow(): string {
            $row = $this->toTableRow();
            return $row . '<td><a href="' . URL_ROOT_PATH . '/customer/products/details.php?id=' . $this->id . '">Details</a></td>';
        }

        function toDetails(): string {
            $row = getFileContent(Settings::LIB_ABSOLUTE_PATH. '/details/product.html');
            $price = $this->priceInCents / 100;
            $row = str_replace('{$price}', number_format($price, 2), $row);
            $finalPrice = $price * (100 - $this->discount) / 100;
            $row = str_replace('{$finalPrice}', number_format($finalPrice, 2), $row);
            return $row;
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
            $form = parent::formNew() . getFileContent(Settings::LIB_ABSOLUTE_PATH . '/forms/console.html');
            $form = str_replace('{$basePath}', URL_ROOT_PATH, $form);
            foreach(get_class_vars('Console') as $property => $value)
                $form = str_replace('{$' . $property . '}', '', $form);
            foreach(get_class_vars('GameTypes') as $property => $value)
                $form = str_replace('{$gameTypes->' . $property . '}', '', $form);
            return $form;
        }

        function formUpdate(): string {
            $form = parent::formUpdate() . getFileContent(Settings::LIB_ABSOLUTE_PATH . '/forms/console.html');
            $form = str_replace('{$basePath}', URL_ROOT_PATH, $form);
            foreach($this as $property => $value) {
                switch($property) {
                    case 'productType': break;
                    case 'gameTypes':
                        foreach($value as $gameType => $bool)
                            $form = str_replace('{$gameTypes->' . $gameType . '}', $bool ? 'checked' : '', $form);
                        break;
                    default: $form = str_replace('{$' . $property . '}', $value, $form); break;
                }
            }
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

        function update(mysqli $connection): void {
            try {
                $connection->begin_transaction();
                parent::update($connection);
                $sql = "
                    UPDATE Consoles
                    SET name = ?, gameTypes = ?
                    WHERE id = ?;
                ";
                $statement = $connection->prepare($sql);
                $formattedGameTypes = $this->gameTypes->toMysqlString();
                $statement->bind_param("ssi", $this->name, $formattedGameTypes, $this->id);
                $statement->execute();
                $statement->close();
                $connection->commit();
            } catch(mysqli_sql_exception $_) {
                $connection->rollback();
                throw new UnprocessableContentResponse();
            }
        }

        static function selectRow(mysqli $connection, int $id): array {
            return parent::selectRowResult($connection, $id, 'SELECT name, gameTypes FROM Consoles');
        }

        static function selectNumberOfPages(mysqli $connection): int {
            return parent::productSelectNumberOfPages($connection, 'Consoles');
        }

        static function fromRow(array $row): Console {
            return new Console(parent::fromRow($row), $row['name'], GameTypes::fromMysqlString($row['gameTypes']));
        }

        static function selectPage(mysqli $connection, int $page): array {
            try {
                $result = parent::selectPageResult($connection, 'name, gameTypes', 'INNER JOIN Consoles AS C ON C.id = P.id', $page);
                while($row = $result->fetch_assoc())
                    $consoles[] = self::fromRow($row);
                return $consoles ?? array();
            } catch(mysqli_sql_exception $_) {
                throw new UnprocessableContentResponse();
            }
        }

        function toTableRow(): string {
            $row = parent::toTableRow() . getFileContent(Settings::LIB_ABSOLUTE_PATH . '/tables/console-row.html');
            foreach($this as $property => $value) {
                switch($property) {
                    case 'productType': break;
                    case 'gameTypes':
                        foreach($value as $gameType => $gameTypeValue)
                            $row = str_replace('{$gameTypes->' . $gameType . '}', $gameTypeValue ? 'Yes' : 'No', $row);
                        break;
                    default: $row = str_replace('{$' . $property . '}', $value, $row); break;
                }
            }
            return $row;
        }

        function toDetails(): string {
            $row = parent::toDetails() . getFileContent(Settings::LIB_ABSOLUTE_PATH . '/details/console.html');
            foreach($this as $property => $value) {
                switch($property) {
                    case 'productType': break;
                    case 'gameTypes':
                        foreach($value as $gameType => $gameTypeValue)
                            $row = str_replace('{$gameTypes->' . $gameType . '}', $gameTypeValue ? 'Yes' : 'No', $row);
                        break;
                    default: $row = str_replace('{$' . $property . '}', $value, $row); break;
                }
            }
            return $row;
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

        function formUpdate(): string {
            $form = parent::formUpdate() . getFileContent(Settings::LIB_ABSOLUTE_PATH . '/forms/videogame.html');
            $form = str_replace('{$basePath}', URL_ROOT_PATH, $form);
            foreach($this as $property => $value) {
                switch($property) {
                    case 'productType': break;
                    default: $form = str_replace('{$' . $property . '}', $value, $form); break;
                }
            }
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

        function update(mysqli $connection): void {
            try {
                $connection->begin_transaction();
                parent::update($connection);
                $sql = "
                    UPDATE Videogames
                    SET title = ?, plot = ?, releaseYear = ?
                    WHERE id = ?;
                ";
                $statement = $connection->prepare($sql);
                $statement->bind_param("ssii", $this->title, $this->plot, $this->releaseYear, $this->id);
                $statement->execute();
                $statement->close();
                $connection->commit();
            } catch(mysqli_sql_exception $_) {
                $connection->rollback();
                throw new UnprocessableContentResponse();
            }
        }

        static function selectRow(mysqli $connection, int $id): array {
            return parent::selectRowResult($connection, $id, 'SELECT title, plot, releaseYear FROM Videogames');
        }

        static function selectNumberOfPages(mysqli $connection): int {
            return parent::productSelectNumberOfPages($connection, 'Videogames');
        }

        static function fromRow(array $row): Videogame {
            return new Videogame(parent::fromRow($row), $row['title'], $row['plot'], intval($row['releaseYear']));
        }

        static function selectPage(mysqli $connection, int $page): array {
            try {
                $result = parent::selectPageResult($connection, 'title, plot, releaseYear', 'INNER JOIN Videogames AS V ON V.id = P.id', $page);
                while($row = $result->fetch_assoc())
                    $videogames[] = self::fromRow($row);
                return $videogames ?? array();
            } catch(mysqli_sql_exception $_) {
                throw new UnprocessableContentResponse();
            }
        }

        function toTableRow(): string {
            $row = parent::toTableRow() . getFileContent(Settings::LIB_ABSOLUTE_PATH . '/tables/videogame-row.html');
            foreach($this as $property => $value) {
                switch($property) {
                    case 'productType': break;
                    default: $row = str_replace('{$' . $property . '}', $value, $row); break;
                }
            }
            return $row;
        }

        function toDetails(): string {
            $row = parent::toDetails() . getFileContent(Settings::LIB_ABSOLUTE_PATH . '/details/videogame.html');
            foreach($this as $property => $value) {
                switch($property) {
                    case 'productType': break;
                    default: $row = str_replace('{$' . $property . '}', $value, $row); break;
                }
            }
            return $row;
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
                $form = str_replace('{$type::' . $accessoryType->name . '}', '', $form);
            return $form;
        }

        function formUpdate(): string {
            $form = parent::formUpdate() . getFileContent(Settings::LIB_ABSOLUTE_PATH . '/forms/accessory.html');
            $form = str_replace('{$basePath}', URL_ROOT_PATH, $form);
            foreach($this as $property => $value) {
                switch($property) {
                    case 'productType': break;
                    case 'type':
                        foreach(AccessoryType::cases() as $accessoryType)
                            $form = str_replace('{$type::' . $accessoryType->name . '}', $value == $accessoryType ? 'selected' : '', $form);
                        break;
                    default: $form = str_replace('{$' . $property . '}', $value, $form); break;
                }
            }
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

        static function selectRow(mysqli $connection, int $id): array {
            return parent::selectRowResult($connection, $id, 'SELECT name, type FROM Accessories');
        }

        static function selectNumberOfPages(mysqli $connection): int {
            return parent::productSelectNumberOfPages($connection, 'Accessories');
        }

        static function fromRow(array $row): Accessory {
            return new Accessory(parent::fromRow($row), $row['name'], AccessoryType::fromMysqlString($row['type']));
        }

        static function selectPage(mysqli $connection, int $page): array {
            try {
                $result = parent::selectPageResult($connection, 'name, type', 'INNER JOIN Accessories AS A ON A.id = P.id', $page);
                while($row = $result->fetch_assoc())
                    $accessories[] = self::fromRow($row);
                return $accessories ?? array();
            } catch(mysqli_sql_exception $_) {
                throw new UnprocessableContentResponse();
            }
        }

        function toTableRow(): string {
            $row = parent::toTableRow() . getFileContent(Settings::LIB_ABSOLUTE_PATH . '/tables/accessory-row.html');
            foreach($this as $property => $value) {
                switch($property) {
                    case 'productType': break;
                    case 'type': $row = str_replace('{$' . $property . '}', $value->name, $row); break;
                    default: $row = str_replace('{$' . $property . '}', $value, $row); break;
                }
            }
            return $row;
        }

        function toDetails(): string {
            $row = parent::toDetails() . getFileContent(Settings::LIB_ABSOLUTE_PATH . '/details/accessory.html');
            foreach($this as $property => $value) {
                switch($property) {
                    case 'productType': break;
                    case 'type': 
                        switch($value) {
                            case AccessoryType::AUDIO: $type = 'Audio'; break;
                            case AccessoryType::VIDEO: $type = 'Video'; break;
                            case AccessoryType::INPUT: $type = 'Input'; break;
                        }
                        $row = str_replace('{$' . $property . '}', $type, $row); break;
                    default: $row = str_replace('{$' . $property . '}', $value, $row); break;
                }
            }
            return $row;
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

        function formUpdate(): string {
            $form = parent::formUpdate() . getFileContent(Settings::LIB_ABSOLUTE_PATH . '/forms/guide.html');
            $form = str_replace('{$basePath}', URL_ROOT_PATH, $form);
            foreach($this as $property => $value) {
                switch($property) {
                    case 'productType': break;
                    default: $form = str_replace('{$' . $property . '}', $value, $form); break;
                }
            }
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

        static function selectRow(mysqli $connection, int $id): array {
            return parent::selectRowResult($connection, $id, 'SELECT title FROM Guides');
        }

        static function selectNumberOfPages(mysqli $connection): int {
            return parent::productSelectNumberOfPages($connection, 'Guides');
        }

        static function fromRow(array $row): Guide {
            return new Guide(parent::fromRow($row), $row['title']);
        }

        static function selectPage(mysqli $connection, int $page): array {
            try {
                $result = parent::selectPageResult($connection, 'title', 'INNER JOIN Guides AS G ON G.id = P.id', $page);
                while($row = $result->fetch_assoc())
                    $guides[] = self::fromRow($row);
                return $guides ?? array();
            } catch(mysqli_sql_exception $_) {
                throw new UnprocessableContentResponse();
            }
        }

        function toTableRow(): string {
            $row = parent::toTableRow() . getFileContent(Settings::LIB_ABSOLUTE_PATH . '/tables/guide-row.html');
            foreach($this as $property => $value) {
                switch($property) {
                    case 'productType': break;
                    default: $row = str_replace('{$' . $property . '}', $value, $row); break;
                }
            }
            return $row;
        }

        function toDetails(): string {
            $row = parent::toDetails() . getFileContent(Settings::LIB_ABSOLUTE_PATH . '/details/guide.html');
            foreach($this as $property => $value) {
                switch($property) {
                    case 'productType': break;
                    default: $row = str_replace('{$' . $property . '}', $value, $row); break;
                }
            }
            return $row;
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

        function toUiString(): string {
            switch($this) {
                case self::CONSOLE: return 'Console';
                case self::VIDEOGAME: return 'Videogame';
                case self::ACCESSORY: return 'Accessory';
                case self::GUIDE: return 'Guide';
            }
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