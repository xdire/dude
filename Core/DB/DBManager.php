<?php namespace Xdire\Dude\Core\DB;
/**
 * Static class DBManager
 *
 * Manager holding PDO instances of already instatiated DB connections
 *
 * Class DBManager
 * @package Xdire\Dude\Core\DB
 */
final class DBManager
{

    /**
     * @var \PDO[]
     */
    private static $instances = [];

    /**
     * @param string $address
     * @param int $port
     * @param string $dbname
     * @return \PDO | null
     */
    public static final function &getDbInstance($address,$port,$dbname) {
        $key = crc32($address.$port.$dbname)."1";
        $ret = null;
        if(isset(self::$instances[$key])) {
            return self::$instances[$key];
        }
        return $ret;
    }

    /**
     * @param string $address
     * @param int $port
     * @param string $dbname
     * @param \PDO $connection
     */
    public static final function addInstance($address,$port,$dbname,\PDO &$connection) {
        $key = crc32($address.$port.$dbname)."1";
        self::$instances[$key] = $connection;
    }

}