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

        static function formNew(string $imgPath): string {
            return '
                <div class="container section">
                    <h2>Product Details</h2>
                    <img class="icon" src="' . $imgPath . 'img/product-details.svg" alt="Personal Details Icon">
                </div>
                <div class="container space-between">
                    <label for="code">Code:</label>
                    <input type="number" name="code" id="code">
                </div>
                <div class="container space-between">
                    <label for="price-in-cents">Price (cents):</label>
                    <input class="medium" type="number" name="price-in-cents" id="price-in-cents">
                </div>
                <div class="container space-between">
                    <label for="discount">Discount (%):</label>
                    <input class="small" type="number" name="discount" id="discount">
                </div>
                <div class="container space-between">
                    <label for="available-quantity">Available Quantity:</label>
                    <input class="small" type="number" name="available-quantity" id="available-quantity">
                </div>
            ';
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
            if(!$statement->execute()) throw new UnprocessableContentResponse();
            $this->id = $connection->insert_id;
            $statement->close();
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

        static function formNew(string $imgPath): string {
            return parent::formNew($imgPath) . '
                <div class="container section">
                    <h2>Console Details</h2>
                    <img class="icon" src="' . $imgPath . 'img/more.svg" alt="Product Details Icon">
                </div>
                <div class="container space-between">
                    <label for="name">Name:</label>
                    <input type="text" name="name" id="name">
                </div>
                <div class="container">
                    <fieldset>
                        <legend>Game Types:</legend>
                        <div class="container no-margin">
                            <label for="game-types-legacy">Legacy</label>
                            <input type="checkbox" id="game-types-legacy" name="game-types-legacy">
                        </div>
                        <div class="container no-margin">
                            <label for="game-types-cd">CD</label>
                            <input type="checkbox" id="game-types-cd" name="game-types-cd">
                        </div>
                        <div class="container no-margin">
                            <label for="game-types-dvd">DVD</label>
                            <input type="checkbox" id="game-types-dvd" name="game-types-dvd">
                        </div>
                        <div class="container no-margin">
                            <label for="game-types-digital">DIGITAL</label>
                            <input type="checkbox" id="game-types-digital" name="game-types-digital">
                        </div>
                    </fieldset>
                </div>
            ';
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
                if(!$statement->execute()) throw new UnprocessableContentResponse();
                $statement->close();
                $connection->commit();
            } catch(mysqli_sql_exception $_) {
                $connection->rollback();
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

        static function formNew(string $imgPath): string {
            return parent::formNew($imgPath) . '
                <div class="container section">
                    <h2>Videogame Details</h2>
                    <img class="icon" src="' . $imgPath . 'img/more.svg" alt="Videogame Details Icon">
                </div>
                <div class="container space-between">
                    <label for="title">Title:</label>
                    <input type="text" name="title" id="title">
                </div>
                <div class="container space-between">
                    <label for="plot">Plot:</label>
                    <textarea name="plot" id="plot"></textarea>
                </div>
                <div class="container space-between">
                    <label for="release-year">Release Year:</label>
                    <input class="medium" type="number" name="release-year" id="release-year">
                </div>
            ';
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
                if(!$statement->execute()) throw new UnprocessableContentResponse();
                $statement->close();
                $connection->commit();
            } catch(mysqli_sql_exception $_) {
                $connection->rollback();
                throw new UnprocessableContentResponse();
            }
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

        static function formNew(string $imgPath): string {
            return parent::formNew($imgPath) . '
                <div class="container section">
                    <h2>Accessory Details</h2>
                    <img class="icon" src="' . $imgPath . 'img/more.svg" alt="Accessory Details Icon">
                </div>
                <div class="container space-between">
                    <label for="name">Name:</label>
                    <input type="text" name="name" id="name">
                </div>
                <div class="container space-between">
                    <label for="type">Type:</label>
                    <select name="type" id="type">
                        <option value="audio">Audio</option>
                        <option value="video">Video</option>
                        <option value="input">Input</option>
                    </select>
                </div>
            ';
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
                if(!$statement->execute()) throw new UnprocessableContentResponse();
                $statement->close();
                $connection->commit();
            } catch(mysqli_sql_exception $_) {
                $connection->rollback();
                throw new UnprocessableContentResponse();
            }
        }
    }

    class Guide extends Product {
        public string $title;
        public int $videogameId;

        function __construct(Product $product, string $title, int $videogameId) {
            foreach($product as $property => $value)
                $this->{$property} = $value;
            $this->title = $title;
            $this->videogameId = $videogameId;
        }
    }

    enum ProductType: string {
        case CONSOLE = 'console';
        case VIDEOGAME = 'videogame';
        case ACCESSORY = 'accessory';
        case GUIDE = 'guide';

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