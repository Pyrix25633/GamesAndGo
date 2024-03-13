<?php
    declare(strict_types = 1);
    const SETTINGS_FILE = __DIR__ . '/settings.json';

    class Settings {
        public string $server;
        public string $username;
        public string $password;
        public string $database;
        public int $recordsPerPage;

        function __construct() {
            $file = fopen(SETTINGS_FILE, 'r');
            $settings = json_decode(fread($file, filesize(SETTINGS_FILE)));
            $this->server = $settings->server;
            $this->username = $settings->username;
            $this->password = $settings->password;
            $this->database = $settings->database;
            $this->recordsPerPage = $settings->recordsPerPage;
        }
    }

    function connect(Settings $settings): mysqli {
        $connection = new mysqli($settings->server, $settings->username, $settings->password);
        $connection->select_db($settings->database);
        if($connection->connect_error != null) throw new InternalServerErrorResponse();
        return $connection;
    }
?>