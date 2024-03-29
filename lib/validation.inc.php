<?php
    /*
        Classe per la validazione dei dati inviati tramite GET e POST,
        se i dati ricevuti non rispettano le condizioni richieste viene generato
        un errore che redireziona l'utente su una pagina di errore,
        nell'applicazione pronta per la pubblicazione questa parte andrebbe migliorata
        effettuando controlli più stringenti sui dati ed una gestione degli errori
        più user friendly
    */
    class Validator {
        private array $data;

        function __construct(?array $data, bool $canBeEmpty = false) {
            if(!$canBeEmpty && $data == null) throw new MethodNotAllowedResponse();
            $this->data = $data;
        }

        function isSet(string $key): bool {
            return isset($this->data[$key]);
        }

        function getInt(string $key): int {
            if(!$this->isSet($key)) throw new BadRequestResponse($key);
            $int = $this->data[$key];
            if(intval($int) != $int) throw new BadRequestResponse($key);
            return (int)$int;
        }

        function getPositiveInt(string $key): int {
            $parsed = $this->getInt($key);
            if($parsed < 0) throw new BadRequestResponse($key);
            return $parsed;
        }

        function getBool(string $key): bool {
            return $this->isSet($key) && $this->data[$key] == 'on';
        }

        function getString(string $key): string {
            if(!$this->isSet($key)) throw new BadRequestResponse($key);
            return $this->data[$key];
        }

        function getNonEmptyString(string $key): string {
            $parsed = $this->getString($key);
            if(strlen($parsed) == 0) throw new BadRequestResponse($key);
            return $parsed;
        }

        function getGender(string $key): Gender {
            $parsed = $this->getNonEmptyString($key);
            return Gender::fromString($parsed);
        }

        function getDateTime(string $key): DateTime {
            $parsed = $this->getNonEmptyString($key);
            try {
                return new DateTime($parsed);
            } catch(DateMalformedStringException $_) {
                throw new BadRequestResponse($key);
            }
        }

        function getState(string $key): string {
            $parsed = $this->getNonEmptyString($key);
            if(strlen($parsed) != 2) throw new BadRequestResponse($key);
            return $parsed;
        }

        function getDocumentType(string $key): DocumentType {
            $parsed = $this->getNonEmptyString($key);
            return DocumentType::fromString($parsed);
        }

        function getPhoneNumberPrefix(string $key): int {
            $parsed = $this->getPositiveInt($key);
            if($parsed > 999 || $parsed == 0) throw new BadRequestResponse($key);
            return $parsed;
        }

        function getPhoneNumber(string $key): int {
            $parsed = $this->getPositiveInt($key);
            if($parsed > 9999999999 || $parsed < 100000000) throw new BadRequestResponse($key);
            return $parsed;
        }

        function getEmail(string $key): string {
            $parsed = $this->getNonEmptyString($key);
            $email = filter_var($parsed, FILTER_VALIDATE_EMAIL);
            if(!$email) throw new BadRequestResponse($key);
            return $email;
        }

        function getProductType(string $key): ProductType {
            $parsed = $this->getNonEmptyString($key);
            return ProductType::fromString($parsed);
        }

        function getUserType(string $key): UserType {
            $parsed = $this->getNonEmptyString($key);
            return UserType::fromString($parsed);
        }

        function getSellerRole(string $key): SellerRole {
            $parsed = $this->getNonEmptyString($key);
            return SellerRole::fromString($parsed);
        }

        function getPriceInCents(string $key): int {
            $parsed = $this->getPositiveInt($key);
            if($parsed > 9999999) throw new BadRequestResponse($key);
            return $parsed;
        }

        function getDiscount(string $key): int {
            $parsed = $this->getPositiveInt($key);
            if($parsed > 100) throw new BadRequestResponse($key);
            return $parsed;
        }

        function getAccessoryType(string $key): AccessoryType {
            $parsed = $this->getNonEmptyString($key);
            return AccessoryType::fromString($parsed);
        }

        function getPaymentType(string $key): PaymentType {
            $parsed = $this->getNonEmptyString($key);
            return PaymentType::fromString($parsed);
        }

        function getVote(string $key): int {
            $parsed = $this->getPositiveInt($key);
            if($parsed > 10 || $parsed == 0) throw new BadRequestResponse($key);
            return $parsed;
        }

        function getPurchaseStatus(string $key): PurchaseStatus {
            $parsed = $this->getNonEmptyString($key);
            return PurchaseStatus::fromString($parsed);
        }
    }
?>