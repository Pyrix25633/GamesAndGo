<?php

    /*
        Insieme di classi per la gestione degli errori.
        Se si verificano errori che non si possono risolvere,
        una delle classi che estendono Response viene instanziata
        e lanciata come eccezione, in ogni file php c'è un try-catch,
        nel catch viene chiamato il metodo send, che per BadRequestResponse
        genererà una risposta HTTP simile a:
        HTTP/<versione> 400
        Location: <percorso>/error.php?code=400&message=Bad%20Request&field=username
    */

    class Response extends Exception {
        private int $httpCode;
        private string $httpMessage;
        private ?string $field;

        function __construct(int $httpCode, string $httpMessage, ?string $field = null) {
            $this->httpCode = $httpCode;
            $this->httpMessage = $httpMessage;
            $this->field = $field;
        }

        function send(): void {
            http_response_code($this->httpCode);
            $url = URL_ROOT_PATH . '/error.php?code=' . $this->httpCode . '&message=' . $this->httpMessage;
            $url = str_replace(' ', '%20', $url);
            if($this->field != null)
                $url .= '&field=' . $this->field;
            header('Location: ' . $url);
            exit;
        }
    }

    class BadRequestResponse extends Response {
        function __construct(?string $field = null) {
            parent::__construct(400, 'Bad Request', $field);
        }
    }

    class UnauthorizedResponse extends Response {
        function __construct(?string $field = null) {
            parent::__construct(401, 'Unauthorized', $field);
        }
    }

    class ForbiddenResponse extends Response {
        function __construct() {
            parent::__construct(403, 'Forbidden');
        }
    }

    class NotFoundResponse extends Response {
        function __construct(?string $field = null) {
            parent::__construct(404, 'Not Found', $field);
        }
    }

    class MethodNotAllowedResponse extends Response {
        function __construct() {
            parent::__construct(405, 'Method Not Allowed');
        }
    }

    class UnprocessableContentResponse extends Response {
        function __construct(?string $field = null) {
            parent::__construct(422, 'Unprocessable Content', $field);
        }
    }

    class InternalServerErrorResponse extends Response {
        function __construct() {
            parent::__construct(500, 'Internal Server Error');
        }
    }
?>