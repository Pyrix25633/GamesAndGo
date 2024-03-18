<?php
    // Insieme di costanti utilizzate in varie parti dell'applicazione
    define('URL_ROOT_PATH', getUrlBasePath());
    class Settings {
        public const DB_SERVER = '127.0.0.1';
        public const DB_USERNAME = 'root';
        public const DB_PASSWORD = '';
        public const DB_DATABASE = 'GamesAndGo';
        public const AUTH_COOKIE_NAME = 'auth-token';
        public const AUTH_COOKIE_DURATION = '14D';
        public const AUTH_COOKIE_KEY = 'a28cvhiierbh1a4';
        public const AUTH_COOKIE_METHOD = 'aes-256-ctr';
        public const RECORDS_PER_PAGE = 15;
        public const LIB_ABSOLUTE_PATH = __DIR__;
    }

    function getUrlBasePath(): string {
        $path = dirname(__DIR__) . '\n';
        while(!str_ends_with($path, 'htdocs'))
            $path = dirname($path);
        return str_replace($path, '', dirname(__DIR__));
    }

    function getFileContent(string $path): string {
        $file = fopen($path, 'r');
        $content = fread($file, filesize($path));
        fclose($file);
        return $content;
    }

    function connect(): mysqli {
        $connection = new mysqli(Settings::DB_SERVER, Settings::DB_USERNAME, Settings::DB_PASSWORD);
        $connection->select_db(Settings::DB_DATABASE);
        if($connection->connect_error != null) throw new InternalServerErrorResponse();
        return $connection;
    }

    class PageHelper {
        public int $previousPage;
        public int $nextPage;
        public int $lastPage;

        function __construct(int $page, int $pages) {
            $this->previousPage = $page - 1;
            if($this->previousPage < 0) $this->previousPage = 0;
            $this->lastPage = $pages - 1;
            if($this->lastPage < 0) $this->lastPage  = 0;
            $this->nextPage = $page + 1;
            if($this->nextPage > $this->lastPage) $this->nextPage = $this->lastPage;
        }
    }
?>