<?php
    const URL_ROOT_PATH = '/GamesAndGo/';

    class Response extends Exception {
        private int $httpCode;
        private ?string $field;

        function __construct(int $httpCode, ?string $field = null) {
            $this->httpCode = $httpCode;
            $this->field = $field;
        }

        function send(): void {
            http_response_code($this->httpCode);
            if($this->field == null)
                header('Location: ' . URL_ROOT_PATH . 'error/' . $this->httpCode . '.php');
            else
                header('Location: ' . URL_ROOT_PATH . 'error/' . $this->httpCode . '.php?field=' . $this->field);
            die();
        }
    }

    class BadRequestResponse extends Response {
        function __construct(?string $field = null) {
            parent::__construct(400, $field);
        }
    }

    class UnauthorizedResponse extends Response {
        function __construct(?string $field = null) {
            parent::__construct(401, $field);
        }
    }

    class ForbiddenResponse extends Response {
        function __construct() {
            parent::__construct(403);
        }
    }

    class NotFoundResponse extends Response {
        function __construct(?string $field = null) {
            parent::__construct(404, $field);
        }
    }

    class MethodNotAllowedResponse extends Response {
        function __construct() {
            parent::__construct(405);
        }
    }

    class UnprocessableContentResponse extends Response {
        function __construct(?string $field = null) {
            parent::__construct(422, $field);
        }
    }

    class InternalServerErrorResponse extends Response {
        function __construct() {
            parent::__construct(500);
        }
    }
?>