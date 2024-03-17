-- A
-- Tutti gli acquisti effettuati in un dato intervallo di tempo che mostrino
-- nome e cognome e codice del cliente in ordine crescente per data,
-- riportando il costo dell'acquisto fatto

-- Esempio con inizio intervallo '2024-03-01 00:00:00' e fine intervallo '2024-03-16 19:00:00'
SELECT U.name, U.surname, U.id, R.*, R.productsTotal + R.commission AS total
FROM (
    SELECT P.customerId, P.createdAt, P.paymentType, P.paymentCode,
        SUM(POC.piecePrice * POC.quantity) AS productsTotal, P.commission
    FROM ProductsOnPurchases AS POC
    INNER JOIN Purchases AS P
    ON P.id = POC.purchaseId
    WHERE P.createdAt > '2024-03-01 00:00:00' AND P.createdAt < '2024-03-16 19:00:00'
    GROUP BY P.id
) AS R
INNER JOIN Users AS U
ON U.id = R.customerId
ORDER BY createdAt ASC;

-- B
-- Eliminazione di un dipendente dal database, con indicazione del codice del dipendente

-- Esempio con codice dipendente 1891
SET @SellerId = (
    SELECT id
    FROM Sellers
    WHERE code = 1891
);
DELETE FROM Sellers
WHERE id = @SellerId;
DELETE FROM Users
WHERE id = @SellerId;

-- C
-- Dato un certo prodotto, consentire al dipendente di visualizzare l’elenco delle recensioni
-- con il voto medio dei feedback ricevuti da tutti i clienti

-- Esempi con id prodotto 3

-- Elenco delle rencesioni
SELECT R.vote, R.comment, U.username AS customerUsername FROM (
    SELECT * FROM Feedbacks
    WHERE productId = 3
) AS R
INNER JOIN Users AS U
ON R.customerId = U.id;

-- Media dei voti
SELECT AVG(vote)
FROM Feedbacks
WHERE productId = 3;