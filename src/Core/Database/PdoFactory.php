<?php declare(strict_types = 1);

namespace TheSaiged\Core\Database;

use PDO;
use TheSaiged\Core\Env;

final class PdoFactory {

    static function create (): PDO {
        $host = Env::required('DB_HOST');
        $port = Env::required('DB_PORT');
        $name = Env::required('DB_NAME');
        $user = Env::required('DB_USER');
        $pass = Env::required('DB_PASS');
        $dsn  = "mysql:host=$host;port=$port;dbname=$name;charset=utf8mb4";

        return new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    }

}
