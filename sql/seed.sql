-- ////////
-- Prodotti
-- ////////

-- Console

REPLACE INTO Products
(code, productType, price, discount, availableQuantity)
VALUES (1094623479, 'CONSOLE', 65.59, 15, 17);
REPLACE INTO Consoles
(id, name, gameTypes)
VALUES (@@IDENTITY, 'Nintendo 3DS', 'LEGACY');

REPLACE INTO Products
(code, productType, price, discount, availableQuantity)
VALUES (4793541268, 'CONSOLE', 599.90, 0, 34);
REPLACE INTO Consoles
(id, name, gameTypes)
VALUES (@@IDENTITY, 'PlayStation 4', 'CD,DVD,DIGITAL');

-- Videogiochi

REPLACE INTO Products
(code, productType, price, discount, availableQuantity)
VALUES (6589041257, 'VIDEOGAME', 69.90, 8, 29);
REPLACE INTO Videogames
(id, title, plot, releaseYear)
VALUES (@@IDENTITY, 'LEGO Star Wars: The Skywalker Saga', 'Play with your character like in the movies', 2022);

REPLACE INTO Products
(code, productType, price, discount, availableQuantity)
VALUES (5987231468, 'VIDEOGAME', 49.90, 0, 35);
REPLACE INTO Videogames
(id, title, plot, releaseYear)
VALUES (@@IDENTITY, 'GTA V', 'Play as a gangster in Los Santos City', 2013);

-- Accessori

REPLACE INTO Products
(code, productType, price, discount, availableQuantity)
VALUES (498653179, 'ACCESSORY', 32.49, 10, 9);
REPLACE INTO Accessories
(id, name, type)
VALUES (@@IDENTITY, 'Sony Headphones', 'AUDIO');

REPLACE INTO Products
(code, productType, price, discount, availableQuantity)
VALUES (7935480217, 'ACCESSORY', 45.99, 0, 26);
REPLACE INTO Accessories
(id, name, type)
VALUES (@@IDENTITY, 'Playstation 5 Controller', 'INPUT');

-- Guide

REPLACE INTO Products
(code, productType, price, discount, availableQuantity)
VALUES (7549830180, 'GUIDE', 8.90, 5, 11);
REPLACE INTO Guides
(id, title, videogameId)
VALUES(@@IDENTITY, 'LSWTSS: how to complete all levels', 3);

REPLACE INTO Products
(code, productType, price, discount, availableQuantity)
VALUES (9803170391, 'GUIDE', 9.49, 0, 7);
REPLACE INTO Guides
(id, title, videogameId)
VALUES(@@IDENTITY, 'GTAV: how to complete all missions', 4);

-- //////
-- Utenti
-- //////

-- Dipendente

REPLACE INTO Users
(userType, name, surname, gender, dateOfBirth, documentState, documentType, documentNumber, username, passwordHash)
VALUES ('SELLER', 'Daniele', 'Anselmi', 'MALE', '1990-04-02', 'IT', 'ID', 'CA07650CB', 'd.anselmi', SHA2('pass123', 256));
REPLACE INTO Admins
(id)
VALUES(@@IDENTITY);

-- Amministratore

REPLACE INTO Users
(userType, name, surname, gender, dateOfBirth, documentState, documentType, documentNumber, username, passwordHash)
VALUES ('ADMIN', 'Toni', 'Fassina', 'MALE', '1975-02-14', 'IT', 'ID', 'CA10354CB', 't.fassina', SHA2('admin', 256));
REPLACE INTO Admins
(id)
VALUES(@@IDENTITY);