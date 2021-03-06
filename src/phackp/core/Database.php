<?php
namespace yuxblank\phackp\core;
use PDO;

/**
 * Database connection class.
 * Use \PDO to create a connection to datasource.
 * Provides useful query methods for interacting with databases.
 * Also provides Serialization/Deserialization of PDO instance with ability to reconnect un-serialized objects
 * @author yuri.blanc
 * @version 0.3
 */

class Database{
    /**
     * @var array Database configuration
     */
    private $conf;
    /**
     * @var \PDO
     */
    private $pdo;
    /**
     * @var \PDOStatement
     */
    private $stm;


    /**
     * Database constructor.
     */
    public function __construct() {
        $this->conf = Application::getDatabase();
        $this->connect();

    }

    /**
     * Save Database instance state for serialization
     * @return array
     */
    public function __sleep()
    {
        return array('conf');

    }

    /**
     * Deserialize Database class and reconnect to datasource.
     */
    public function __wakeup()
    {
        $this->connect();
    }


    /**
     * Connect to database and create pdo instance
     */
    protected function connect(){

        $dsn = $this->conf['DRIVER'] . ':host=' . $this->conf['HOST'] . ";dbname=" . $this->conf['NAME'];
        try {
            $this->pdo = new PDO($dsn, $this->conf['USER'], $this->conf['PSW'], $this->conf['OPTIONS']);
        } catch (\PDOException $ex) {
            $ex->getMessage();

        }

    }

    /**
     * This method will return the current instance of the PDO object, already connected.
     * If you need to directly access pdo, you can do it.
     * @return PDO
     */

    public function getPDO() {
        return $this->pdo;
    }

    /**
     * Bind an array of properties to a table name: table.property
     * @param array $properties
     * @param string $table
     * @return array
     */
    public function setTableToProperties(array $properties, $table) {
        $result = array();
        foreach ($properties as $property) {
            $result[] = $table.'.'.$property;
        }
        return $result;
    }

    /**
     * Bind an array of parameters to a given \PDOStatement
     * @param array $params
     * @return \PDOStatement
     */
    public function paramsBinder(array $params) {
        foreach ($params as $key => $value) {
            $key++; // + 1 for bindParams
            $this->bindValue($key, $value);
        }
        return $this->stm;
    }

    /**
     * Create a prepared statement
     * @param $statement
     * @return \PDOStatement
     */
    public function query($statement) {
        $this->stm = $this->pdo->prepare($statement);
        return $this->stm;
    }
    /**
     * bind a a value
     * @param mixed $param
     * @param mixed $value
     * @return \PDOStatement
     */
    public function bindValue ($param, $value) {
        $this->stm->bindParam($param,$value);
        return $this->stm;
    }

    /**
     * Exclude 'id' or 'ID' index for update operations
     * @param array $properties
     * @return array
     */
    public function excludeId(array $properties) {
        $result = array();
        foreach ($properties as $key => $property) {
            if ($key ==='id'|| $key==='ID'){
                continue;
            }
            $result[$key] = $property;
        }
        return $result;
    }


    /**
     * Wraps \PDOStatement
     * @see \PDOStatement->execute();
     * @return bool
     */
    public function execute() {
        return $this->stm->execute();
    }

    /**
     * Count rows
     * @return string
     */
    public function rowCount() {
        $this->execute();
        return $this->stm->fetchColumn();
    }

    /**
     * Return an array of associative arrays of fetched rows
     * @return array
     */
    public function resultList() {
        $this->execute();
        return $this->stm->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Return a single associative array a row
     * @return mixed
     */
    public function singleResult() {
        $this->execute();
        return $this->stm->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Return the fetched row as an object
     * @return \stdClass
     */
    public function fetchObj() {
        $this->execute();
        return $this->stm->fetch(PDO::FETCH_OBJ);
    }

    /**
     * Returns fetched rows as an object array
     * @return array
     */
    public function fetchObjectSet() {
        $this->execute();
        return $this->stm->fetchAll(PDO::FETCH_OBJ);
    }

    /**
     * Fetch a single row as a given class instance
     * @param $object
     * @return mixed
     */
    public function fetchSingleClass($object) {
        $this->stm->setFetchMode(PDO::FETCH_INTO, new $object());
        $this->execute();
        return $this->stm->fetch();
    }

    /**
     * Return an array of result as given class instances
     * @param $object
     * @return array
     */
    public function fetchClassSet($object) {
        $this->execute();
        return $this->stm->fetchAll(PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE, $object);
    }


}


