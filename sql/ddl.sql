CREATE DATABASE IF NOT EXISTS GamesAndGo;

USE DATABASE GamesAndGo;

-- Products

CREATE TABLE IF NOT EXISTS Products (
    id INT AUTO_INCREMENT,
    code BIGINT NOT NULL,
    productType ENUM('CONSOLE', 'VIDEOGAME', 'ACCESSORY', 'GUIDE') NOT NULL,
    price DECIMAL(7, 2) NOT NULL,
    discount TINYINT NOT NULL,
    availableQuantity SMALLINT NOT NULL,
    PRIMARY KEY (id),
    UNIQUE (code)
);

CREATE TABLE IF NOT EXISTS Consoles (
    id INT NOT NULL,
    name VARCHAR(32) NOT NULL,
    gameTypes SET('LEGACY', 'CD', 'DVD', 'DIGITAL'),
    PRIMARY KEY (id),
    UNIQUE (name),
    FOREIGN KEY (id) REFERENCES Products(id)
);

CREATE TABLE IF NOT EXISTS Videogames (
    id INT NOT NULL,
    title VARCHAR(56) NOT NULL,
    plot TEXT NOT NULL,
    releaseYear SMALLINT NOT NULL,
    PRIMARY KEY (id),
    UNIQUE (title),
    FOREIGN KEY (id) REFERENCES Products(id)
);

CREATE TABLE IF NOT EXISTS Accessories (
    id INT NOT NULL,
    name VARCHAR(32) NOT NULL,
    type ENUM('AUDIO', 'VIDEO', 'INPUT'),
    PRIMARY KEY (id),
    UNIQUE (name),
    FOREIGN KEY (id) REFERENCES Products(id)
);

CREATE TABLE IF NOT EXISTS Guides (
    id INT NOT NULL,
    title VARCHAR(56) NOT NULL,
    videogameId INT NOT NULL,
    PRIMARY KEY (id),
    UNIQUE (title),
    FOREIGN KEY (videogameId) REFERENCES Videogames(id),
    FOREIGN KEY (id) REFERENCES Products(id)
);

-- Users

CREATE TABLE IF NOT EXISTS Users (
    id INT AUTO_INCREMENT,
    userType ENUM('CUSTOMER', 'SELLER', 'ADMIN') NOT NULL,
    name VARCHAR(32) NOT NULL,
    surname VARCHAR(32) NOT NULL,
    gender ENUM('MALE', 'FEMALE', 'OTHER') NOT NULL,
    dateOfBirth DATE NOT NULL,
    documentState CHAR(2) NOT NULL,
    documentType ENUM('ID', 'PASSPORT', 'DRIVING_LICENSE') NOT NULL,
    documentNumber VARCHAR(32) NOT NULL,
    username VARCHAR(32) NOT NULL,
    passwordHash CHAR(64) NOT NULL,
    PRIMARY KEY (id),
    UNIQUE (username)
);

CREATE TABLE IF NOT EXISTS Customers (
    id INT NOT NULL,
    addressStreetType VARCHAR(8) NOT NULL,
    addressStreetName VARCHAR(32) NOT NULL,
    addressHouseNumber SMALLINT NOT NULL,
    phoneNumberPrefix TINYINT NOT NULL,
    phoneNumber BIGINT NOT NULL,
    emailAddress VARCHAR(64) NOT NULL,
    PRIMARY KEY (id),
    FOREIGN KEY (id) REFERENCES Users(id),
    FOREIGN KEY (loyaltyCardId) REFERENCES LoyaltyCards(id)
);

CREATE TABLE IF NOT EXISTS Sellers (
    id INT NOT NULL,
    addressStreetType VARCHAR(8) NOT NULL,
    addressStreetName VARCHAR(32) NOT NULL,
    addressHouseNumber SMALLINT NOT NULL,
    phoneNumberPrefix TINYINT NOT NULL,
    phoneNumber BIGINT NOT NULL,
    emailAddress VARCHAR(64) NOT NULL,
    code INT NOT NULL,
    role ENUM('SHOP_ASSISTANT', 'WAREHOUSE_MAN'),
    PRIMARY KEY (id),
    FOREIGN KEY (id) REFERENCES Users(id)
);

CREATE TABLE IF NOT EXISTS Admins (
    id INT NOT NULL,
    PRIMARY KEY (id),
    FOREIGN KEY (id) REFERENCES Users(id)
);

-- Loyalty Card

CREATE TABLE IF NOT EXISTS LoyaltyCards (
    id INT AUTO_INCREMENT,
    rfid VARCHAR(16) NOT NULL,
    points INT NOT NULL DEFAULT 0,
    customerId INT NOT NULL,
    PRIMARY KEY (id),
    UNIQUE (customerId),
    FOREIGN KEY (customerId) REFERENCES Customers(id)
);

-- Feedbacks

CREATE TABLE IF NOT EXISTS Feedbacks (
    id INT AUTO_INCREMENT,
    customerId INT NOT NULL,
    vote TINYINT NOT NULL,
    comment TEXT NOT NULL,
    productId INT NOT NULL,
    PRIMARY KEY (id),
    FOREIGN KEY (customerId) REFERENCES Customers(id),
    FOREIGN KEY (productId) REFERENCES Products(id)
);

-- Suppliers

CREATE TABLE IF NOT EXISTS Suppliers (
    id INT AUTO_INCREMENT,
    name VARCHAR(56),
    vatIdentificationNumber VARCHAR(15),
    phoneNumberPrefix TINYINT NOT NULL,
    phoneNumber BIGINT NOT NULL,
    emailAddress VARCHAR(64) NOT NULL,
    PRIMARY KEY (id),
    UNIQUE (name),
    UNIQUE (vatIdentificationNumber),
    UNIQUE (emailAddress),
    UNIQUE (phoneNumberPrefix, phoneNumber)
);

-- Purchases

CREATE TABLE IF NOT EXISTS Carts (
    id INT AUTO_INCREMENT,
    customerId INT NOT NULL,
    PRIMARY KEY (id),
    UNIQUE (customerId),
    FOREIGN KEY (customerId) REFERENCES Customers(id)
);

CREATE TABLE IF NOT EXISTS ProductsOnCarts (
    id INT AUTO_INCREMENT,
    productId INT NOT NULL,
    cartId INT NOT NULL,
    quantity TINYINT NOT NULL,
    PRIMARY KEY (id),
    FOREIGN KEY (productId) REFERENCES Products(id),
    FOREIGN KEY (cartId) REFERENCES Carts(id)
);

CREATE TABLE IF NOT EXISTS Purchases (
   id INT AUTO_INCREMENT,
   customerId INT NOT NULL,
   createdAt DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
   PRIMARY KEY (id),
   FOREIGN KEY (customerId) REFERENCES Customers(id)
);

CREATE TABLE IF NOT EXISTS ProductsOnPurchases (
   id INT AUTO_INCREMENT,
   productId INT NOT NULL,
   purchaseId INT NOT NULL,
   piecePrice DECIMAL(7, 2) NOT NULL,
   quantity TINYINT NOT NULL,
   PRIMARY KEY (id),
   FOREIGN KEY (productId) REFERENCES Products(id),
   FOREIGN KEY (purchaseId) REFERENCES Purchases(id)
);