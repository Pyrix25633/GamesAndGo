CREATE DATABASE IF NOT EXISTS GamesAndGo;

USE DATABASE GamesAndGo;

-- ////////
-- Prodotti
-- ////////

CREATE TABLE IF NOT EXISTS Products (
    id INT AUTO_INCREMENT,
    -- Codice, ipotizzo sia il codice a barre, quindi un numero
    code BIGINT NOT NULL,
    -- Ho introdotto un tipo, anche se è un dato ridondante,
    -- perchè semplifica molto l'interazione dell'applicazione con il database
    productType ENUM('CONSOLE', 'VIDEOGAME', 'ACCESSORY', 'GUIDE') NOT NULL,
    price DECIMAL(7, 2) NOT NULL, -- Prezzo, es 12.99
    discount TINYINT NOT NULL, -- Sconto, es: 10
    availableQuantity SMALLINT NOT NULL,
    PRIMARY KEY (id),
    UNIQUE (code)
);

CREATE TABLE IF NOT EXISTS Consoles (
    id INT NOT NULL,
    name VARCHAR(32) NOT NULL,
    gameTypes SET('LEGACY', 'CD', 'DVD', 'DIGITAL'), -- Comparto giochi, tipi di giochi
    PRIMARY KEY (id),
    UNIQUE (name),
    FOREIGN KEY (id) REFERENCES Products(id)
);

CREATE TABLE IF NOT EXISTS Videogames (
    id INT NOT NULL,
    title VARCHAR(56) NOT NULL,
    plot TEXT NOT NULL, -- Trama
    releaseYear SMALLINT NOT NULL, -- Anno di rilascio, es: 2013
    PRIMARY KEY (id),
    UNIQUE (title),
    FOREIGN KEY (id) REFERENCES Products(id)
);

CREATE TABLE IF NOT EXISTS Accessories (
    id INT NOT NULL,
    name VARCHAR(32) NOT NULL,
    type ENUM('AUDIO', 'VIDEO', 'INPUT'), -- Tipo accessorio
    PRIMARY KEY (id),
    UNIQUE (name),
    FOREIGN KEY (id) REFERENCES Products(id)
);

-- Dato che la guida è di un videogioco, se la piattaforma vendesse solo guide
-- di videogiochi presenti nel database si potrebbe aggiungere un attributo e
-- una chiave esterna, non l'ho fatto per semplicità
CREATE TABLE IF NOT EXISTS Guides (
    id INT NOT NULL,
    title VARCHAR(56) NOT NULL,
    PRIMARY KEY (id),
    UNIQUE (title),
    FOREIGN KEY (id) REFERENCES Products(id)
);

-- //////
-- Utenti
-- //////

CREATE TABLE IF NOT EXISTS Users (
    id INT AUTO_INCREMENT,
    -- Ho introdotto un tipo, anche se è un dato ridondante,
    -- perchè semplifica molto l'interazione dell'applicazione con il database
    userType ENUM('CUSTOMER', 'SELLER', 'ADMIN') NOT NULL,
    -- Generalità
    name VARCHAR(32) NOT NULL,
    surname VARCHAR(32) NOT NULL,
    gender ENUM('MALE', 'FEMALE', 'OTHER') NOT NULL,
    dateOfBirth DATE NOT NULL,
    documentState CHAR(2) NOT NULL,
    documentType ENUM('ID', 'PASSPORT', 'DRIVING_LICENSE') NOT NULL,
    documentNumber VARCHAR(32) NOT NULL,
    -- Credenziali
    username VARCHAR(32) NOT NULL,
    -- Ho utilizzato l'hashing della password, sarebbe opportuno approfondire meglio
    -- e utilizzare anche il salting
    passwordHash CHAR(64) NOT NULL,
    PRIMARY KEY (id),
    UNIQUE (username)
);

CREATE TABLE IF NOT EXISTS Customers (
    id INT NOT NULL,
    -- Indirizzo
    addressStreetType VARCHAR(8) NOT NULL,
    addressStreetName VARCHAR(32) NOT NULL,
    addressHouseNumber SMALLINT NOT NULL,
    -- Numero di telefono
    phoneNumberPrefix TINYINT NOT NULL,
    phoneNumber BIGINT NOT NULL,
    -- Email
    emailAddress VARCHAR(64) NOT NULL,
    PRIMARY KEY (id),
    FOREIGN KEY (id) REFERENCES Users(id)
);

CREATE TABLE IF NOT EXISTS Sellers (
    id INT NOT NULL,
    -- Indirizzo
    addressStreetType VARCHAR(8) NOT NULL,
    addressStreetName VARCHAR(32) NOT NULL,
    addressHouseNumber SMALLINT NOT NULL,
    -- Numero di telefono
    phoneNumberPrefix TINYINT NOT NULL,
    phoneNumber BIGINT NOT NULL,
    -- Email
    emailAddress VARCHAR(64) NOT NULL,
    -- Codice cartellino, quindi un numero
    code INT NOT NULL,
    -- Ruolo, sarebbe opportuno analizzare insieme all'azienda i possibili
    -- ruoli dipendente e le relative operazioni che può svolgere
    role ENUM('SHOP_ASSISTANT', 'WAREHOUSE_MAN') NOT NULL,
    PRIMARY KEY (id),
    FOREIGN KEY (id) REFERENCES Users(id)
);

CREATE TABLE IF NOT EXISTS Admins (
    id INT NOT NULL,
    PRIMARY KEY (id),
    FOREIGN KEY (id) REFERENCES Users(id)
);

-- /////////////
-- Carte fedeltà
-- /////////////

CREATE TABLE IF NOT EXISTS LoyaltyCards (
    id INT AUTO_INCREMENT,
    rfid VARCHAR(16) NOT NULL,
    points INT NOT NULL DEFAULT 0,
    customerId INT NOT NULL,
    PRIMARY KEY (id),
    UNIQUE (customerId),
    FOREIGN KEY (customerId) REFERENCES Customers(id)
);

-- //////////
-- Recensioni
-- //////////

CREATE TABLE IF NOT EXISTS Feedbacks (
    id INT AUTO_INCREMENT,
    customerId INT NOT NULL,
    vote TINYINT NOT NULL,
    comment TEXT NOT NULL,
    productId INT NOT NULL,
    PRIMARY KEY (id),
    -- Un cliente può scrivere solo una recensione per prodotto
    UNIQUE(customerId, productId),
    FOREIGN KEY (customerId) REFERENCES Customers(id),
    FOREIGN KEY (productId) REFERENCES Products(id)
);

-- /////////
-- Fornitori
-- /////////
-- Dato che non hanno collegamenti con le altre entità
-- non li ho implementati nell'applicazione, sarebbe opportuno
-- contattare l'azienda e approfondire

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

-- ////////
-- Acquisti
-- ////////

CREATE TABLE IF NOT EXISTS Carts (
    id INT AUTO_INCREMENT,
    customerId INT NOT NULL,
    PRIMARY KEY (id),
    UNIQUE (customerId),
    FOREIGN KEY (customerId) REFERENCES Customers(id)
);

-- Possono esserci più ProductsOnCarts con lo stesso id prodotto e carrello, non è un problema,
-- nell'applicazione pronta per essere pubblicata si potrebbe impostare un vincolo
-- UNIQUE(productId, cartId) e modificare l'interazione utente sull'applicazione
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
   status ENUM('PREPARING', 'DELIVERING', 'DELIVERED') NOT NULL DEFAULT 'PREPARING',
   createdAt DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
   paymentType ENUM('CHEQUE', 'CREDIT_CARD', 'CREDIT_TRANSFER') NOT NULL,
   paymentCode VARCHAR(24) NOT NULL,
   commission TINYINT NOT NULL, -- Commissione, memorizzata per motivi fiscali, in quanto potrebbe cambiare nel tempo
   PRIMARY KEY (id),
   UNIQUE (paymentCode),
   FOREIGN KEY (customerId) REFERENCES Customers(id)
);

-- Possono esserci più ProductsOnPurchases con lo stesso id prodotto e acquisto, non è un problema,
-- nell'applicazione pronta per essere pubblicata si potrebbe impostare un vincolo
-- UNIQUE(productId, purchaseId) e modificare l'interazione utente sull'applicazione
-- Viene memorizzato anche il prezzo finale (con lo sconto applicato) per pezzo per motivi fiscali,
-- sarebbe opportuno analizzare il problema con un commercialista e regolarizzare il database
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