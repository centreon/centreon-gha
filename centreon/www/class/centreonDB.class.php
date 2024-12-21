<?php

/*
 * Copyright 2005 - 2024 Centreon (https://www.centreon.com/)
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * For more information : contact@centreon.com
 *
 */

// file centreon.config.php may not exist in test environment
$configFile = realpath(__DIR__ . "/../../config/centreon.config.php");
if ($configFile !== false) {
    require_once $configFile;
}

require_once __DIR__ . '/centreonDBStatement.class.php';
require_once __DIR__ . '/centreonLog.class.php';

/**
 * Class
 *
 * @class       CentreonDB
 * @description used to manage DB connection
 */
class CentreonDB extends PDO
{
    public const DRIVER_PDO_MYSQL = "mysql";
    public const LABEL_DB_CONFIGURATION = 'centreon';
    public const LABEL_DB_REALTIME = 'centstorage';

    /** @var int */
    private const RETRY = 3;

    /** @var array<string,CentreonDB> */
    private static $instance = [];

    /** @var int */
    protected $retry;

    /** @var array<int, array<int, mixed>|int|bool|string> */
    protected $options;

    /** @var string */
    protected $centreon_path;

    /** @var CentreonLog */
    protected $logger;

    /*
     * Statistics
     */

    /** @var int */
    protected $requestExecuted;

    /** @var int */
    protected $requestSuccessful;

    /** @var int */
    protected $lineRead;

    /** @var int */
    private $queryNumber;

    /** @var int */
    private $successQueryNumber;

    /** @var CentreonDbConfig */
    private CentreonDbConfig $dbConfig;

    /**
     * By default, the queries are buffered.
     *
     * @var bool
     */
    private bool $isBufferedQueryActive = true;

    /**
     * Constructor
     *
     * @param string                $dbLabel LABEL_DB_* constants
     * @param int                   $retry
     * @param CentreonDbConfig|null $dbConfig
     *
     * @throws Exception
     */
    public function __construct(
        $dbLabel = self::LABEL_DB_CONFIGURATION,
        $retry = self::RETRY,
        ?CentreonDbConfig $dbConfig = null
    ) {
        try {
            if (is_null($dbConfig)) {
                $this->dbConfig = new CentreonDbConfig(
                    $dbLabel === self::LABEL_DB_CONFIGURATION ? hostCentreon : hostCentstorage,
                    user,
                    password,
                    $dbLabel === self::LABEL_DB_CONFIGURATION ? db : dbcstg,
                    port ?? 3306
                );
            } else {
                $this->dbConfig = $dbConfig;
            }

            $this->logger = CentreonLog::create();

            $this->centreon_path = _CENTREON_PATH_;
            $this->retry = $retry;

            $this->options = [
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_STATEMENT_CLASS => [
                    CentreonDBStatement::class,
                    [$this->logger],
                ],
                PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8',
                PDO::MYSQL_ATTR_LOCAL_INFILE => true,
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ];

            /*
             * Init request statistics
             */
            $this->requestExecuted = 0;
            $this->requestSuccessful = 0;
            $this->lineRead = 0;

            parent::__construct(
                $this->dbConfig->getMysqlDsn(),
                $this->dbConfig->dbUser,
                $this->dbConfig->dbPassword,
                $this->options
            );
        } catch (Exception $e) {
            $this->writeDbLog(
                "Unable to connect to database : {$e->getMessage()}",
                ['dsn_mysql' => $this->dbConfig->getMysqlDsn()],
                exception: $e,
                level: CentreonLog::LEVEL_CRITICAL
            );
            if (PHP_SAPI !== "cli") {
                $this->displayConnectionErrorPage(
                    $e->getCode() === 2002 ? "Unable to connect to database" : $e->getMessage()
                );
            } else {
                throw new Exception($e->getMessage());
            }
        }
    }

    /**
     * Factory
     *
     * @param CentreonDbConfig $dbConfig
     *
     * @return CentreonDB
     * @throws Exception
     */
    public static function connectToCentreonDb(CentreonDbConfig $dbConfig): CentreonDB
    {
        return new self(dbLabel: self::LABEL_DB_CONFIGURATION, dbConfig: $dbConfig);
    }

    /**
     * Factory
     *
     * @param CentreonDbConfig $dbConfig
     *
     * @return CentreonDB
     * @throws Exception
     */
    public static function connectToCentreonStorageDb(CentreonDbConfig $dbConfig): CentreonDB
    {
        return new self(dbLabel: self::LABEL_DB_REALTIME, dbConfig: $dbConfig);
    }

    /**
     * @return string
     */
    public function getCurrentDatabaseName(): string
    {
        return $this->dbConfig->dbName;
    }

    /**
     * To know if the connection is established
     *
     * @return bool
     */
    public function isConnected(): bool
    {
        try {
            $this->executeQuery('SELECT 1');

            return true;
        } catch (CentreonDbException $e) {
            return false;
        }
    }

    /***
     * Returns the ID of the last inserted row.
     *
     * @return string
     *
     * @throws CentreonDbException
     */
    public function getLastInsertId(): string
    {
        try {
            return (string) $this->lastInsertId();
        } catch (Throwable $e) {
            $this->logAndCreateException(
                "Error while getting last insert ID : {$e->getMessage()}",
                [],
                '',
                $e
            );
        }
    }

    // --------------------------------------- CUD METHODS -----------------------------------------

    /**
     * Executes an SQL statement with the given parameters and returns the number of affected rows.
     *
     * Could be only used for DELETE.
     *
     * This method supports PDO binding types.
     *
     * @param string $query
     * @param array  $bindParams
     * @param bool   $withParamType
     *
     * @return int
     * @throws CentreonDbException
     */
    public function delete(string $query, array $bindParams, bool $withParamType = false): int
    {
        try {
            $this->validateQueryString($query, 'DELETE', true);
            $stmt = $this->prepareQuery($query);
            $this->executePreparedQuery($stmt, $bindParams, $withParamType);

            return $stmt->rowCount();
        } catch (CentreonDbException $e) {
            $this->logAndCreateException(
                "Error while deleting data : {$e->getMessage()}",
                [
                    'bind_params' => $bindParams,
                    'with_param_type' => $withParamType,
                ],
                $query,
                $e
            );
        }
    }

    /**
     * Executes an SQL statement with the given parameters and returns the number of affected rows.
     *
     * Could be only used for INSERT.
     *
     * This method supports PDO binding types.
     *
     * @param string $query
     * @param array  $bindParams
     * @param bool   $withParamType
     *
     * @return int
     * @throws CentreonDbException
     */
    public function insert(string $query, array $bindParams, bool $withParamType = false): int
    {
        try {
            $this->validateQueryString($query, 'INSERT INTO', true);
            $stmt = $this->prepareQuery($query);
            $this->executePreparedQuery($stmt, $bindParams, $withParamType);

            return $stmt->rowCount();
        } catch (CentreonDbException $e) {
            $this->logAndCreateException(
                "Error while inserting data : {$e->getMessage()}",
                [
                    'bind_params' => $bindParams,
                    'with_param_type' => $withParamType,
                ],
                $query,
                $e
            );
        }
    }

    /**
     * Executes an SQL statement with the given parameters and returns the number of affected rows.
     *
     * Could be only used for several INSERT.
     *
     * This method supports PDO binding types.
     *
     * @param string $tableName
     * @param array  $columns
     * @param array  $bindParams    An array of arrays of bind parameters wth the same length as $columns. The keys must
     *                              be the same as the columns.
     * @param bool   $withParamType If true, $bindParams must have an array of arrays like
     *                              ['column => ['value', PDO::PARAM_*]]
     *
     * @return int
     * @throws CentreonDbException
     */
    public function iterateInsert(
        string $tableName,
        array $columns,
        array $bindParams,
        bool $withParamType = false
    ): int {
        try {
            if (empty($tableName)) {
                throw new CentreonDbException(
                    'Table name must not be empty',
                    ['table_name' => $tableName]
                );
            }
            if (empty($columns)) {
                throw new CentreonDbException(
                    'Columns must not be empty',
                    ['columns' => $columns]
                );
            }
            if (empty($bindParams)) {
                throw new CentreonDbException(
                    'Bind parameters must not be empty',
                    ['bind_params' => $bindParams]
                );
            }
            $bindParamsToExecute = [];
            $query = "INSERT INTO $tableName (" . implode(', ', $columns) . ") VALUES";
            for ($i = 0, $iMax = count($bindParams); $i < $iMax; $i++) {
                if (! is_array($bindParams[$i])) {
                    throw new CentreonDbException(
                        '$bindParams must be an array of arrays',
                        ['bin_params_in_error' => $bindParams[$i], 'bind_params' => $bindParams]
                    );
                }
                if (count($columns) !== count($bindParams[$i])) {
                    throw new CentreonDbException(
                        'Columns and bind parameters must have the same length',
                        ['columns' => $columns, 'bin_params_in_error' => $bindParams[$i], 'bind_params' => $bindParams]
                    );
                }
                if ($i > 0) {
                    $query .= ',';
                }
                $query .= '(:' . implode('_' . $i . ', :', $columns) . '_' . $i . ')';
                foreach ($columns as $column) {
                    if (! isset($bindParams[$i][$column])) {
                        throw new CentreonDbException(
                            "Column $column is not set in bindParams",
                            ['column' => $column, 'bind_params_in_error' => $bindParams[$i]]
                        );
                    }
                    if (! $withParamType) {
                        $bindParamsToExecute[$column . '_' . $i] = $bindParams[$i][$column];
                    } else {
                        if (! is_array($bindParams[$i][$column]) || count($bindParams[$i][$column]) !== 2) {
                            throw new CentreonDbException(
                                "Column $column is not set correctly in bindParams, it must be an array with value and type",
                                ['column' => $column, 'bind_params_in_error' => $bindParams[$i]]
                            );
                        }
                        $bindParamsToExecute[$column . '_' . $i] = [
                            $bindParams[$i][$column][0],
                            $bindParams[$i][$column][1]
                        ];
                    }
                }
            }
            $stmt = $this->prepareQuery($query);
            $this->executePreparedQuery($stmt, $bindParamsToExecute, $withParamType);

            return $stmt->rowCount();
        } catch (Throwable $e) {
            $this->logAndCreateException(
                "Error while iterating insert datas : {$e->getMessage()}",
                [
                    'bind_params' => $bindParams,
                    'with_param_type' => $withParamType,
                ],
                $query ?? '',
                $e
            );
        }
    }

    /**
     * Executes an SQL statement with the given parameters and returns the number of affected rows.
     *
     * Could be only used for UPDATE.
     *
     * This method supports PDO binding types.
     *
     * @param string $query
     * @param array  $bindParams
     * @param bool   $withParamType
     *
     * @return int
     * @throws CentreonDbException
     */
    public function update(string $query, array $bindParams, bool $withParamType = false): int
    {
        try {
            $this->validateQueryString($query, 'UPDATE', true);
            $stmt = $this->prepareQuery($query);
            $this->executePreparedQuery($stmt, $bindParams, $withParamType);

            return $stmt->rowCount();
        } catch (Throwable $e) {
            $this->logAndCreateException(
                "Error while updating data : {$e->getMessage()}",
                [
                    'bind_params' => $bindParams,
                    'with_param_type' => $withParamType,
                ],
                $query,
                $e
            );
        }
    }

    // --------------------------------------- FETCH METHODS -----------------------------------------

    /**
     * Prepares and executes an SQL query and returns the first row of the result as an associative array.
     *
     * Could be only used with SELECT.
     *
     * This method supports PDO binding types.
     *
     * @param string $query
     * @param array  $bindParams
     * @param bool   $withParamType
     *
     * @return array<string, mixed>|false False is returned if no rows are found.
     *
     * @throws CentreonDbException
     */
    public function fetchAssociative(string $query, array $bindParams = [], bool $withParamType = false): false | array
    {
        try {
            $this->validateQueryString($query, 'SELECT', true);
            $pdoStatement = $this->prepareQuery($query);
            $pdoStatement = $this->executePreparedQuery($pdoStatement, $bindParams, $withParamType, PDO::FETCH_ASSOC);

            return $this->fetch($pdoStatement);
        } catch (Throwable $e) {
            $this->logAndCreateException(
                "Error while fetching data with fetchAssociative() : {$e->getMessage()}",
                [
                    'bind_params' => $bindParams,
                    'with_param_type' => $withParamType,
                ],
                $query,
                $e
            );
        }
    }

    /**
     * Prepares and executes an SQL query and returns the first row of the result
     * as a numerically indexed array.
     *
     * Could be only used with SELECT.
     *
     * This method supports PDO binding types.
     *
     * @param string $query
     * @param array  $bindParams
     * @param bool   $withParamType
     *
     * @return array<string, mixed>|false False is returned if no rows are found.
     *
     * @throws CentreonDbException
     */
    public function fetchNumeric(string $query, array $bindParams = [], bool $withParamType = false): false | array
    {
        try {
            $this->validateQueryString($query, 'SELECT', true);
            $pdoStatement = $this->prepareQuery($query);
            $pdoStatement = $this->executePreparedQuery($pdoStatement, $bindParams, $withParamType, PDO::FETCH_NUM);

            return $this->fetch($pdoStatement);
        } catch (Throwable $e) {
            $this->logAndCreateException(
                "Error while fetching data with fetchNumeric() : {$e->getMessage()}",
                [
                    'bind_params' => $bindParams,
                    'with_param_type' => $withParamType,
                ],
                $query,
                $e
            );
        }
    }

    /**
     * Prepares and executes an SQL query and returns the first row of the result as a value of a single column.
     *
     * Could be only used with SELECT.
     *
     * This method supports PDO binding types.
     *
     * @param string $query
     * @param array  $bindParams
     * @param bool   $withParamType
     * @param int    $column
     *
     * @return array|bool
     * @throws CentreonDbException
     */
    public function fetchByColumn(
        string $query,
        array $bindParams = [],
        int $column = 0,
        bool $withParamType = false
    ): mixed {
        try {
            $this->validateQueryString($query, 'SELECT', true);
            $pdoStatement = $this->prepareQuery($query);
            $pdoStatement = $this->executePreparedQuery(
                $pdoStatement,
                $bindParams,
                $withParamType,
                PDO::FETCH_COLUMN,
                [$column]
            );

            return $this->fetch($pdoStatement);
        } catch (Throwable $e) {
            $this->logAndCreateException(
                "Error while fetching data with fetchByColumn() : {$e->getMessage()}",
                [
                    'bind_params' => $bindParams,
                    'with_param_type' => $withParamType,
                    'column' => $column,
                ],
                $query,
                $e
            );
        }
    }

    /**
     * Prepares and executes an SQL query and returns the result as an array of numeric arrays.
     *
     * Could be only used with SELECT.
     *
     * This method supports PDO binding types.
     *
     * @param string $query
     * @param array  $bindParams
     * @param bool   $withParamType
     *
     * @return array<array<int,mixed>>
     *
     * @throws CentreonDbException
     */
    public function fetchAllNumeric(string $query, array $bindParams = [], bool $withParamType = false): array
    {
        try {
            $this->validateQueryString($query, 'SELECT', true);
            $pdoStatement = $this->prepareQuery($query);
            $pdoStatement = $this->executePreparedQuery($pdoStatement, $bindParams, $withParamType, PDO::FETCH_NUM);

            return $this->fetchAll($pdoStatement);
        } catch (Throwable $e) {
            $this->logAndCreateException(
                "Error while fetching data with fetchAllNumeric() : {$e->getMessage()}",
                [
                    'bind_params' => $bindParams,
                    'with_param_type' => $withParamType,
                ],
                $query,
                $e
            );
        }
    }

    /**
     * Prepares and executes an SQL query and returns the result as an array of associative arrays.
     *
     * Could be only used with SELECT.
     *
     * This method supports PDO binding types.
     *
     * @param string $query
     * @param array  $bindParams
     * @param bool   $withParamType
     *
     * @return array<array<string,mixed>>
     *
     * @throws CentreonDbException
     */
    public function fetchAllAssociative(string $query, array $bindParams = [], bool $withParamType = false): array
    {
        try {
            $this->validateQueryString($query, 'SELECT', true);
            $pdoStatement = $this->prepareQuery($query);
            $pdoStatement = $this->executePreparedQuery($pdoStatement, $bindParams, $withParamType, PDO::FETCH_ASSOC);

            return $this->fetchAll($pdoStatement);
        } catch (Throwable $e) {
            $this->logAndCreateException(
                "Error while fetching data with fetchAllAssociative() : {$e->getMessage()}",
                [
                    'bind_params' => $bindParams,
                    'with_param_type' => $withParamType,
                ],
                $query,
                $e
            );
        }
    }

    /**
     * Prepares and executes an SQL query and returns the result as an array of associative arrays with the name of the
     * column as key.
     *
     * Could be only used with SELECT.
     *
     * This method supports PDO binding types.
     *
     * @param string $query
     * @param array  $bindParams
     * @param bool   $withParamType
     * @param int    $column
     *
     * @return array<array<string,mixed>>
     *
     * @throws CentreonDbException
     */
    public function fetchAllByColumn(
        string $query,
        array $bindParams = [],
        int $column = 0,
        bool $withParamType = false
    ): array {
        try {
            $this->validateQueryString($query, 'SELECT', true);
            $pdoStatement = $this->prepareQuery($query);
            $pdoStatement = $this->executePreparedQuery(
                $pdoStatement,
                $bindParams,
                $withParamType,
                PDO::FETCH_COLUMN,
                [$column]
            );

            return $this->fetchAll($pdoStatement);
        } catch (Throwable $e) {
            $this->logAndCreateException(
                "Error while fetching data with fetchAllByColumn() : {$e->getMessage()}",
                [
                    'bind_params' => $bindParams,
                    'with_param_type' => $withParamType,
                    'column' => $column,
                ],
                $query,
                $e
            );
        }
    }

    /**
     * Prepares and executes an SQL query and returns the result as an associative array with the keys
     * mapped to the first column and the values mapped to the second column.
     *
     * Could be only used with SELECT.
     *
     * This method supports PDO binding types.
     *
     * @param string $query
     * @param array  $bindParams
     * @param bool   $withParamType
     *
     * @return array<int|string,mixed>
     *
     * @throws CentreonDbException
     */
    public function fetchAllKeyValue(string $query, array $bindParams = [], bool $withParamType = false): array
    {
        try {
            $this->validateQueryString($query, 'SELECT', true);
            $pdoStatement = $this->prepareQuery($query);
            $pdoStatement = $this->executePreparedQuery(
                $pdoStatement,
                $bindParams,
                $withParamType,
                PDO::FETCH_KEY_PAIR
            );

            return $this->fetchAll($pdoStatement);
        } catch (Throwable $e) {
            $this->logAndCreateException(
                "Error while fetching data with fetchAllKeyValue() : {$e->getMessage()}",
                [
                    'bind_params' => $bindParams,
                    'with_param_type' => $withParamType,
                ],
                $query,
                $e
            );
        }
    }

    /**
     * Prepares and executes an SQL query and returns the result as an associative array with the keys mapped
     * to the first column and the values being an associative array representing the rest of the columns
     * and their values.
     *
     * Could be only used with SELECT.
     *
     * This method supports PDO binding types.
     *
     * @param string $query
     * @param array  $bindParams
     * @param bool   $withParamType
     *
     * @return array<mixed,array<string,mixed>>
     *
     * @throws CentreonDbException
     */
    public function fetchAllAssociativeIndexed(
        string $query,
        array $bindParams = [],
        bool $withParamType = false
    ): array {
        try {
            $data = [];
            foreach ($this->fetchAllAssociative($query, $bindParams, $withParamType) as $row) {
                $data[array_shift($row)] = $row;
            }

            return $data;
        } catch (Throwable $e) {
            $this->logAndCreateException(
                "Error while fetching data with fetchAllAssociativeIndexed() : {$e->getMessage()}",
                [
                    'bind_params' => $bindParams,
                    'with_param_type' => $withParamType,
                ],
                $query,
                $e
            );
        }
    }

    /**
     * Prefer to use fetchNumeric() or fetchAssociative() instead of this method.
     *
     * @param PDOStatement $pdoStatement
     *
     * @return mixed
     *
     * @throws CentreonDbException
     *
     * @see fetchNumeric(), fetchAssociative()
     */
    public function fetch(PDOStatement $pdoStatement): mixed
    {
        try {
            return $pdoStatement->fetch();
        } catch (Throwable $e) {
            $this->closeQuery($pdoStatement);
            $this->logAndCreateException(
                "Error while fetching the row : {$e->getMessage()}",
                [],
                $pdoStatement->queryString,
                $e
            );
        }
    }

    /**
     * Prefer to use fetchAllNumeric() or fetchAllAssociative() instead of this method.
     *
     * @param PDOStatement $pdoStatement
     *
     * @return array
     *
     * @throws CentreonDbException
     *
     * @see fetchAllNumeric(), fetchAllAssociative()
     */
    public function fetchAll(PDOStatement $pdoStatement): array
    {
        try {
            return $pdoStatement->fetchAll();
        } catch (Throwable $e) {
            $this->logAndCreateException(
                "Error while fetching all the rows : {$e->getMessage()}",
                [],
                $pdoStatement->queryString,
                $e
            );
        } finally {
            $this->closeQuery($pdoStatement);
        }
    }

    // --------------------------------------- ITERATE METHODS -----------------------------------------

    /**
     * Prepares and executes an SQL query and returns the result as an iterator over rows represented
     * as associative arrays.
     *
     * Could be only used with SELECT.
     *
     * This method supports PDO binding types.
     *
     * @param string $query
     * @param array  $bindParams
     * @param bool   $withParamType
     *
     * @return Traversable<int,array<string,mixed>>
     *
     * @throws CentreonDbException
     */
    public function iterateAssociative(string $query, array $bindParams = [], bool $withParamType = false): Traversable
    {
        try {
            $this->validateQueryString($query, 'SELECT', true);
            $pdoStatement = $this->prepareQuery($query);
            $pdoStatement = $this->executePreparedQuery($pdoStatement, $bindParams, $withParamType, PDO::FETCH_ASSOC);
            while (($row = $this->fetch($pdoStatement)) !== false) {
                yield $row;
            }
        } catch (Throwable $e) {
            $this->logAndCreateException(
                "Error while fetching data with iterateAssociative() : {$e->getMessage()}",
                [
                    'bind_params' => $bindParams,
                    'with_param_type' => $withParamType,
                ],
                $query,
                $e
            );
        }
    }

    /**
     * Prepares and executes an SQL query and returns the result as an iterator over rows represented as numeric arrays.
     *
     * Could be only used with SELECT.
     *
     * This method supports PDO binding types.
     *
     * @param string $query
     * @param array  $bindParams
     * @param bool   $withParamType
     *
     * @return Traversable<int,list<mixed>>
     *
     * @throws CentreonDbException
     */
    public function iterateNumeric(string $query, array $bindParams = [], bool $withParamType = false): Traversable
    {
        try {
            $this->validateQueryString($query, 'SELECT', true);
            $pdoStatement = $this->prepareQuery($query);
            $pdoStatement = $this->executePreparedQuery($pdoStatement, $bindParams, $withParamType, PDO::FETCH_NUM);
            while (($row = $this->fetch($pdoStatement)) !== false) {
                yield $row;
            }
        } catch (Throwable $e) {
            $this->logAndCreateException(
                "Error while fetching data with iterateNumeric() : {$e->getMessage()}",
                [
                    'bind_params' => $bindParams,
                    'with_param_type' => $withParamType,
                ],
                $query,
                $e
            );
        }
    }

    /**
     * Prepares and executes an SQL query and returns the result as an iterator over the column values.
     *
     * Could be only used with SELECT.
     *
     * This method supports PDO binding types.
     *
     * @param string $query
     * @param array  $bindParams
     * @param int    $column
     * @param bool   $withParamType
     *
     * @return Traversable<int,list<mixed>>
     *
     * @throws CentreonDbException
     */
    public function iterateByColumn(
        string $query,
        array $bindParams = [],
        int $column = 0,
        bool $withParamType = false
    ): Traversable {
        try {
            $this->validateQueryString($query, 'SELECT', true);
            $pdoStatement = $this->prepareQuery($query);
            $pdoStatement = $this->executePreparedQuery(
                $pdoStatement,
                $bindParams,
                $withParamType,
                PDO::FETCH_COLUMN,
                [$column]
            );
            while (($row = $this->fetch($pdoStatement)) !== false) {
                yield $row;
            }
        } catch (Throwable $e) {
            $this->logAndCreateException(
                "Error while fetching data with iterateByColumn() : {$e->getMessage()}",
                [
                    'bind_params' => $bindParams,
                    'with_param_type' => $withParamType,
                    'column' => $column,
                ],
                $query,
                $e
            );
        }
    }

    /**
     * Prepares and executes an SQL query and returns the result as an iterator with the keys
     * mapped to the first column and the values mapped to the second column.
     *
     * Could be only used with SELECT.
     *
     * This method supports PDO binding types.
     *
     * @param string $query
     * @param array  $bindParams
     * @param bool   $withParamType
     *
     * @return Traversable<mixed,mixed>
     *
     * @throws CentreonDbException
     */
    public function iterateKeyValue(string $query, array $bindParams = [], bool $withParamType = false): Traversable
    {
        try {
            $this->validateQueryString($query, 'SELECT', true);
            $pdoStatement = $this->prepareQuery($query);
            $pdoStatement = $this->executePreparedQuery(
                $pdoStatement,
                $bindParams,
                $withParamType,
                PDO::FETCH_KEY_PAIR
            );
            while (($row = $this->fetch($pdoStatement)) !== false) {
                yield $row;
            }
        } catch (Throwable $e) {
            $this->logAndCreateException(
                "Error while fetching data with iterateByColumn() : {$e->getMessage()}",
                [
                    'bind_params' => $bindParams,
                    'with_param_type' => $withParamType
                ],
                $query,
                $e
            );
        }
    }

    /**
     * Prepares and executes an SQL query and returns the result as an iterator with the keys mapped
     * to the first column and the values being an associative array representing the rest of the columns
     * and their values.
     *
     * Could be only used with SELECT.
     *
     * This method supports PDO binding types.
     *
     * @param string $query
     * @param array  $bindParams
     * @param bool   $withParamType
     *
     * @return Traversable<mixed,array<string,mixed>>
     *
     * @throws CentreonDbException
     */
    public function iterateAssociativeIndexed(
        string $query,
        array $bindParams = [],
        bool $withParamType = false
    ): Traversable {
        try {
            foreach ($this->iterateAssociative($query, $bindParams, $withParamType) as $row) {
                yield [array_shift($row) => $row];
            }
        } catch (Throwable $e) {
            $this->logAndCreateException(
                "Error while fetching data with iterateAssociativeIndexed() : {$e->getMessage()}",
                [
                    'bind_params' => $bindParams,
                    'with_param_type' => $withParamType
                ],
                $query,
                $e
            );
        }
    }

    // --------------------------------------- DDL METHODS -----------------------------------------

    /**
     * Only for DDL queries (ALTER TABLE, CREATE TABLE, DROP TABLE, CREATE DATABASE, and TRUNCATE TABLE...)
     *
     * @param string $query
     *
     * @return bool
     * @throws CentreonDbException
     */
    public function updateDatabase(string $query): bool
    {
        try {
            if (empty($query)) {
                throw new CentreonDbException(
                    'Query must not be empty',
                    ['query' => $query]
                );
            }
            $standardQueryStarts = ['SELECT ', 'UPDATE ', 'DELETE ', 'INSERT INTO '];
            foreach ($standardQueryStarts as $standardQueryStart) {
                if (
                    str_starts_with($query, strtolower($standardQueryStart))
                    || str_starts_with($query, strtoupper($standardQueryStart))
                ) {
                    throw new CentreonDbException(
                        'Query must not to start by SELECT, UPDATE, DELETE or INSERT INTO, this method is only for DDL queries',
                        ['query' => $query]
                    );
                }
            }

            return $this->exec($query) !== false;
        } catch (Throwable $e) {
            $this->logAndCreateException(
                "Error while updating the database: {$e->getMessage()}",
                [],
                $query,
                $e
            );
        }
    }

    // --------------------------------------- BASE METHODS -----------------------------------------

    /**
     * @param string $query
     * @param array  $options
     *
     * @return PDOStatement|bool
     * @throws CentreonDbException
     */
    public function prepareQuery(string $query, array $options = []): PDOStatement | bool
    {
        try {
            if (empty($query)) {
                throw new CentreonDbException(
                    'Error while preparing query, query must not be empty',
                    [
                        'query' => $query,
                    ]
                );
            }

            // here we don't want to use CentreonDbStatement, instead used PDOStatement
            $this->setAttribute(PDO::ATTR_STATEMENT_CLASS, [PDOStatement::class]);

            return parent::prepare($query, $options);
        } catch (Throwable $e) {
            $this->logAndCreateException(
                "Error while preparing the query: {$e->getMessage()}",
                ['options' => $options],
                $query,
                $e
            );
        } finally {
            // here we restart CentreonDbStatement for the other requests
            $this->setAttribute(PDO::ATTR_STATEMENT_CLASS, [
                CentreonDBStatement::class,
                [$this->logger],
            ]);
        }
    }

    /**
     * Prepared query.
     *
     * Not for DDL queries
     *
     * @param PDOStatement $pdoStatement
     * @param array        $bindParams    It's optional only for SELECT queries
     * @param bool         $withParamType When $withParamType is true, $bindParams must have an array as value like
     *                                    ['value', PDO::PARAM_*]
     *                                    Allowed types : PDO::PARAM_STR, PDO::PARAM_BOOL, PDO::PARAM_INT, PDO::PARAM_NULL
     * @param int          $fetchMode     Only for the SELECT queries
     * @param array        $fetchModeArgs
     *
     * @return bool|PDOStatement If the query is a CUD query, it returns a boolean, if it's a SELECT query, it returns
     *                           a PDOStatement
     *
     * @throws CentreonDbException
     */
    public function executePreparedQuery(
        PDOStatement $pdoStatement,
        array $bindParams = [],
        bool $withParamType = false,
        int $fetchMode = PDO::FETCH_ASSOC,
        array $fetchModeArgs = []
    ): bool | PDOStatement {
        try {
            // here we don't want to use CentreonDbStatement, instead used PDOStatement
            $this->setAttribute(PDO::ATTR_STATEMENT_CLASS, [PDOStatement::class]);

            $isCUD = (
                str_starts_with($pdoStatement->queryString, 'INSERT INTO ')
                || str_starts_with($pdoStatement->queryString, 'insert into ')
                || str_starts_with($pdoStatement->queryString, 'UPDATE ')
                || str_starts_with($pdoStatement->queryString, 'update ')
                || str_starts_with($pdoStatement->queryString, 'DELETE ')
                || str_starts_with($pdoStatement->queryString, 'delete ')
            );

            if (! $isCUD) {
                $this->validateQueryString($pdoStatement->queryString, 'SELECT', true);
            }

            if (($withParamType && $bindParams === []) || ($isCUD && $bindParams === [])) {
                throw new CentreonDbException(
                    "Binding parameters are empty",
                    ['bind_params' => $bindParams]
                );
            }

            if ($withParamType) {
                foreach ($bindParams as $paramName => $bindParam) {
                    if (is_array($bindParam) && $bindParam !== [] && count($bindParam) === 2) {
                        $paramValue = $bindParam[0];
                        $paramType = $bindParam[1];
                        if (
                            ! in_array(
                                $paramType,
                                [PDO::PARAM_STR, PDO::PARAM_BOOL, PDO::PARAM_INT, PDO::PARAM_NULL],
                                true
                            )
                        ) {
                            throw new CentreonDbException(
                                "Error for the param type, it's not an integer or a value of PDO::PARAM_*",
                                ['bind_param' => $bindParam]
                            );
                        }
                        $this->makeBindValue($pdoStatement, $paramName, $paramValue, $paramType);
                    } else {
                        throw new CentreonDbException(
                            "Incorrect format for bindParam values, it must to be an array like ['value', PDO::PARAM_*]",
                            ['bind_params' => $bindParams]
                        );
                    }
                }
            }

            if ($isCUD) {
                return ($withParamType) ? $pdoStatement->execute() : $pdoStatement->execute($bindParams);
            } else {
                ($withParamType) ? $pdoStatement->execute() : $pdoStatement->execute($bindParams);
                $pdoStatement->setFetchMode($fetchMode, ...$fetchModeArgs);

                return $pdoStatement;
            }
        } catch (Throwable $e) {
            $this->logAndCreateException(
                "Error while executing the prepared query: {$e->getMessage()}",
                ['bind_params' => $bindParams],
                $pdoStatement->queryString,
                $e
            );
        } finally {
            // here we restart CentreonDbStatement for the other requests
            $this->setAttribute(PDO::ATTR_STATEMENT_CLASS, [
                CentreonDBStatement::class,
                [$this->logger],
            ]);
        }
    }

    /**
     * @param PDOStatement $pdoStatement
     * @param array|null   $bindParams
     *
     * @return bool (no signature for this method because of a bug with tests with \Centreon\Test\Mock\CentreonDb::execute())
     * @throws CentreonDbException
     */
    public function execute(PDOStatement $pdoStatement, ?array $bindParams = null)
    {
        try {
            if ($bindParams === []) {
                throw new CentreonDbException(
                    "To execute the query, bindParams must to be an array filled or null, empty array given",
                    ['bind_params' => $bindParams]
                );
            }

            return $pdoStatement->execute($bindParams);
        } catch (Throwable $e) {
            $this->logAndCreateException(
                "Error while executing the query: {$e->getMessage()}",
                ['bind_params' => $bindParams],
                $pdoStatement->queryString,
                $e
            );
        }
    }

    /**
     * Without prepared query, only for SELECT queries.
     *
     * Not used for DDL queries.
     *
     * This method does not support PDO binding types.
     *
     * @param       $query
     * @param int   $fetchMode
     * @param array $fetchModeArgs
     *
     * @return PDOStatement|bool
     *
     * @throws CentreonDbException
     */
    public function executeQuery(
        $query,
        int $fetchMode = PDO::FETCH_ASSOC,
        array $fetchModeArgs = []
    ): PDOStatement | false {
        try {
            if (empty($query)) {
                throw new CentreonDbException(
                    'Error while executing query, query must not be empty',
                    [
                        'query' => $query,
                    ]
                );
            }

            // here we don't want to use CentreonDbStatement, instead used PDOStatement
            $this->setAttribute(PDO::ATTR_STATEMENT_CLASS, [PDOStatement::class]);
            $stmt = $this->prepare($query);
            $stmt->execute();
            $stmt->setFetchMode($fetchMode, ...$fetchModeArgs);

            return $stmt;
        } catch (Throwable $e) {
            $this->logAndCreateException(
                "Error while executing the query: {$e->getMessage()}",
                [
                    'fetch_mode' => $fetchMode,
                    'fetch_mode_args' => $fetchModeArgs,
                ],
                $query,
                $e
            );
        } finally {
            // here we restart CentreonDbStatement for the other requests
            $this->setAttribute(PDO::ATTR_STATEMENT_CLASS, [
                CentreonDBStatement::class,
                [$this->logger],
            ]);
        }
    }

    /**
     *  Allowed types : PDO::PARAM_STR, PDO::PARAM_BOOL, PDO::PARAM_INT, PDO::PARAM_NULL
     *
     * @param PDOStatement $pdoStatement
     * @param int|string   $paramName
     * @param mixed        $value
     * @param int          $type
     *
     * @return bool
     * @throws CentreonDbException
     */
    public function makeBindValue(
        PDOStatement $pdoStatement,
        int | string $paramName,
        mixed $value,
        int $type = PDO::PARAM_STR
    ): bool {
        try {
            if (empty($paramName)) {
                throw new CentreonDbException(
                    "paramName must to be filled, empty given",
                    ['param_name' => $paramName]
                );
            }
            if (
                ! in_array(
                    $type,
                    [PDO::PARAM_STR, PDO::PARAM_BOOL, PDO::PARAM_INT, PDO::PARAM_NULL],
                    true
                )
            ) {
                throw new CentreonDbException(
                    "Error for the param type, it's not an integer or a value of PDO::PARAM_*",
                    ['param_name' => $paramName]
                );
            }

            return $pdoStatement->bindValue($paramName, $value, $type);
        } catch (Throwable $e) {
            $this->logAndCreateException(
                "Error while binding value for param {$paramName} : {$e->getMessage()}",
                [
                    'param_name' => $paramName,
                    'param_value' => $value,
                    'param_type' => $type
                ],
                $pdoStatement->queryString,
                $e
            );
        }
    }

    /**
     * @param PDOStatement $pdoStatement
     * @param int|string   $paramName
     * @param mixed        $var
     * @param int          $type
     * @param int          $maxLength
     *
     * @return bool
     * @throws CentreonDbException
     */
    public function makeBindParam(
        PDOStatement $pdoStatement,
        int | string $paramName,
        mixed &$var,
        int $type = PDO::PARAM_STR,
        int $maxLength = 0
    ): bool {
        try {
            if (empty($paramName)) {
                throw new CentreonDbException(
                    "paramName must to be filled, empty given",
                    ['param_name' => $paramName]
                );
            }
            if (
                ! in_array(
                    $type,
                    [PDO::PARAM_STR, PDO::PARAM_BOOL, PDO::PARAM_INT, PDO::PARAM_NULL],
                    true
                )
            ) {
                throw new CentreonDbException(
                    "Error for the param type, it's not an integer or a value of PDO::PARAM_*",
                    ['param_name' => $paramName]
                );
            }

            return $pdoStatement->bindParam($paramName, $var, $type, $maxLength);
        } catch (Throwable $e) {
            $this->logAndCreateException(
                "Error while binding param {$paramName} : {$e->getMessage()}",
                [
                    'param_name' => $paramName,
                    'param_var' => $var,
                    'param_type' => $type,
                    'param_max_length' => $maxLength
                ],
                $pdoStatement->queryString,
                $e
            );
        }
    }

    /**
     * @param PDOStatement $pdoStatement
     *
     * @return bool
     * @throws CentreonDbException
     */
    public function closeQuery(PDOStatement $pdoStatement): bool
    {
        try {
            return $pdoStatement->closeCursor();
        } catch (Throwable $e) {
            $this->logAndCreateException(
                "Error while closing the PDOStatement cursor: {$e->getMessage()}",
                [],
                $pdoStatement->queryString,
                $e
            );
        }
    }

    /**
     * @param string $string
     * @param int    $type
     *
     * @return string
     * @throws CentreonDbException
     */
    public function escapeString(string $string, int $type = PDO::PARAM_STR): string
    {
        $quotedString = parent::quote($string, $type);
        if ($quotedString === false) {
            throw new CentreonDbException("Error while quoting the string: {$string}");
        }

        return $quotedString;
    }

    // ----------------------------------------- TRANSACTIONS -----------------------------------------

    /**
     * Check if a transaction is active or not.
     *
     * @return bool
     */
    public function isTransactionActive(): bool
    {
        return parent::inTransaction();
    }

    /**
     * Opens a new transaction. This must be closed by calling one of the following methods:
     * {@see commit} or {@see rollBack}
     *
     * @return void
     *
     * @throws CentreonDbException
     */
    public function startTransaction(): void
    {
        try {
            $this->beginTransaction();
        } catch (Throwable $e) {
            $this->logAndCreateException("Error while starting a transaction: {$e->getMessage()}", [], '', $e);
        }
    }

    /**
     * To validate a transaction.
     *
     * @return bool
     *
     * @throws CentreonDbException
     */
    public function commit(): bool
    {
        try {
            if (! parent::commit()) {
                throw new CentreonDbException("Error while committing a transaction");
            }

            return true;
        } catch (Throwable $e) {
            $this->logAndCreateException("Error while committing a transaction", [], '', $e);
        }
    }

    /**
     * To cancel a transaction.
     *
     * @return bool
     *
     * @throws CentreonDbException
     */
    public function rollBack(): bool
    {
        try {
            if (! parent::rollBack()) {
                throw new CentreonDbException("Error while rolling back a transaction");
            }

            return true;
        } catch (Throwable $e) {
            $this->logAndCreateException("Error while rolling back a transaction", [], '', $e);
        }
    }

    // ------------------------------------- UNBUFFERED QUERIES -----------------------------------------

    /**
     * Prepares a statement to execute a query without buffering. Only works for SELECT queries.
     *
     * @return void
     *
     * @throws CentreonDbException
     */
    public function startUnbufferedQuery(): void
    {
        try {
            if (! $this->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false)) {
                throw new CentreonDbException("Error while starting an unbuffered query");
            }
            $this->isBufferedQueryActive = false;
        } catch (Throwable $e) {
            $this->logAndCreateException("Error while starting an unbuffered query", [], '', $e);
        }
    }

    /**
     * Checks whether an unbuffered query is currently active.
     *
     * @return bool
     */
    public function isUnbufferedQueryActive(): bool
    {
        return $this->isBufferedQueryActive === false;
    }

    /**
     * To close an unbuffered query.
     *
     * @return void
     *
     * @throws CentreonDbException
     */
    public function stopUnbufferedQuery(): void
    {
        try {
            if (! $this->isUnbufferedQueryActive()) {
                throw new CentreonDbException(
                    "Error while stopping an unbuffered query, no unbuffered query is currently active"
                );
            }
            if (! $this->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true)) {
                throw new CentreonDbException("Error while stopping an unbuffered query");
            }
            $this->isBufferedQueryActive = true;
        } catch (Throwable $e) {
            $this->logAndCreateException("Error while stopping an unbuffered query", [], '', $e);
        }
    }

    // --------------------------------------- OTHER METHODS -----------------------------------------

    /**
     * Display error page
     *
     * @param mixed $msg
     */
    protected function displayConnectionErrorPage($msg = null): void
    {
        if (! $msg) {
            $msg = _("Connection failed, please contact your administrator");
        }
        echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
            "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
              <html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en-US" lang="en-US">
                <head>
                <style type="text/css">
                       div.Error{background-color:#fa6f6c;border:1px #AEAEAE solid;width: 500px;}
                       div.Error{border-radius:4px;}
                       div.Error{padding: 15px;}
                       a, div.Error{font-family:"Bitstream Vera Sans", arial, Tahoma, "Sans serif";font-weight: bold;}
                </style>
                <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
                <title>Centreon</title>
              </head>
                <body>
                  <center>
                  <div style="padding-top:150px;padding-bottom:50px;">
                        <img src="./img/centreon.png" alt="Centreon"/><br/>
                  </div>
                  <div class="Error">' . $msg . '</div>
                  <div style="padding: 50px;"><a href="#" onclick="location.reload();">Refresh Here</a></div>
                  </center>
                </body>
              </html>';
        exit;
    }

    /**
     * Factory for singleton
     *
     * @param string $name The name of centreon datasource
     *
     * @return CentreonDB
     * @throws Exception
     */
    public static function factory($name = self::LABEL_DB_CONFIGURATION)
    {
        if (! in_array($name, [self::LABEL_DB_CONFIGURATION, self::LABEL_DB_REALTIME])) {
            throw new Exception("The datasource isn't defined in configuration file.");
        }
        if (! isset(self::$instance[$name])) {
            self::$instance[$name] = new CentreonDB($name);
        }

        return self::$instance[$name];
    }

    /**
     * return database Properties
     *
     * <code>
     * $dataCentreon = getProperties();
     * </code>
     *
     * @return array<mixed> dbsize, numberOfRow, freeSize
     */
    public function getProperties()
    {
        $unitMultiple = 1024 * 1024;

        $info = [
            'version' => null,
            'engine' => null,
            'dbsize' => 0,
            'rows' => 0,
            'datafree' => 0,
            'indexsize' => 0
        ];
        /*
         * Get Version
         */
        if ($res = $this->query("SELECT VERSION() AS mysql_version")) {
            $row = $res->fetch();
            $versionInformation = explode('-', $row['mysql_version']);
            $info["version"] = $versionInformation[0];
            $info["engine"] = $versionInformation[1] ?? 'MySQL';
            if ($dbResult = $this->query("SHOW TABLE STATUS FROM `" . $this->dbConfig->dbName . "`")) {
                while ($data = $dbResult->fetch()) {
                    $info['dbsize'] += $data['Data_length'] + $data['Index_length'];
                    $info['indexsize'] += $data['Index_length'];
                    $info['rows'] += $data['Rows'];
                    $info['datafree'] += $data['Data_free'];
                }
                $dbResult->closeCursor();
            }
            foreach ($info as $key => $value) {
                if ($key != "rows" && $key != "version" && $key != "engine") {
                    $info[$key] = round($value / $unitMultiple, 2);
                }
            }
        }

        return $info;
    }

    /**
     * As 'ALTER TABLE IF NOT EXIST' queries are supported only by mariaDB (no more by mysql),
     * This method check if a column was already added in a previous upgrade script.
     *
     * @param string|null $table  - the table on which we'll search the column
     * @param string|null $column - the column name to be checked
     *
     * @return int
     */
    public function isColumnExist(string $table = null, string $column = null): int
    {
        if (! $table || ! $column) {
            return -1;
        }

        $table = HtmlAnalyzer::sanitizeAndRemoveTags($table);
        $column = HtmlAnalyzer::sanitizeAndRemoveTags($column);

        $query = "SELECT COLUMN_NAME
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = :dbName
            AND TABLE_NAME = :tableName
            AND COLUMN_NAME = :columnName";

        $stmt = $this->prepare($query);

        try {
            $stmt->bindValue(':dbName', $this->dbConfig->dbName, PDO::PARAM_STR);
            $stmt->bindValue(':tableName', $table, PDO::PARAM_STR);
            $stmt->bindValue(':columnName', $column, PDO::PARAM_STR);
            $stmt->execute();
            $stmt->fetch();

            if ($stmt->rowCount()) {
                return 1; // column already exist
            }

            return 0; // column to add
        } catch (PDOException $e) {
            $this->writeDbLog(
                'Error while checking if the column exists',
                [
                    'table' => $table,
                    'column' => $column,
                ],
                query: $stmt->queryString,
                exception: $e
            );

            return -1;
        }
    }

    /**
     * Indicates whether an index exists or not.
     *
     * @param string $table
     * @param string $indexName
     *
     * @return bool
     * @throws PDOException
     */
    public function isIndexExists(string $table, string $indexName): bool
    {
        $statement = $this->prepare(
            <<<'SQL'
            SELECT 1
            FROM information_schema.STATISTICS
            WHERE TABLE_SCHEMA = :db_name
              AND TABLE_NAME = :table_name
              AND INDEX_NAME = :index_name;
            SQL
        );
        $statement->bindValue(':db_name', $this->dbConfig->dbName);
        $statement->bindValue(':table_name', $table);
        $statement->bindValue(':index_name', $indexName);

        $statement->execute();

        return ! empty($statement->fetch(PDO::FETCH_ASSOC));
    }

    /**
     * Indicates whether a constraint on table exists or not.
     *
     * @param string $table
     * @param string $constraintName
     *
     * @return bool
     * @throws PDOException
     */
    public function isConstraintExists(string $table, string $constraintName): bool
    {
        $statement = $this->prepare(
            <<<'SQL'
            SELECT 1
            FROM information_schema.TABLE_CONSTRAINTS
            WHERE CONSTRAINT_SCHEMA = :db_name
              AND TABLE_NAME = :table_name
              AND CONSTRAINT_NAME = :constraint_name;
            SQL
        );
        $statement->bindValue(':db_name', $this->dbConfig->dbName);
        $statement->bindValue(':table_name', $table);
        $statement->bindValue(':constraint_name', $constraintName);

        $statement->execute();

        return ! empty($statement->fetch(PDO::FETCH_ASSOC));
    }

    /**
     * This method returns a column type from a given table and column.
     *
     * @param string $tableName
     * @param string $columnName
     *
     * @return string
     */
    public function getColumnType(string $tableName, string $columnName): string
    {
        $tableName = HtmlAnalyzer::sanitizeAndRemoveTags($tableName);
        $columnName = HtmlAnalyzer::sanitizeAndRemoveTags($columnName);

        $query = 'SELECT COLUMN_TYPE
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = :dbName
            AND TABLE_NAME = :tableName
            AND COLUMN_NAME = :columnName';

        $stmt = $this->prepare($query);

        try {
            $stmt->bindValue(':dbName', $this->dbConfig->dbName, PDO::PARAM_STR);
            $stmt->bindValue(':tableName', $tableName, PDO::PARAM_STR);
            $stmt->bindValue(':columnName', $columnName, PDO::PARAM_STR);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            if (! empty($result)) {
                return $result['COLUMN_TYPE'];
            }
            throw new PDOException("Unable to get column type");
        } catch (PDOException $e) {
            $this->writeDbLog(
                'Error while checking if the column exists',
                [
                    'table' => $tableName,
                    'column' => $columnName,
                ],
                query: $stmt->queryString,
                exception: $e
            );
        }
    }

    // --------------------------------------- PRIVATE METHODS -----------------------------------------

    /**
     * To easily log and create a CentreonDbException.
     *
     * @param string         $message
     * @param array          $context
     * @param string         $query
     * @param Throwable|null $exception
     *
     * @return void
     * @throws CentreonDbException
     */
    private function logAndCreateException(
        string $message,
        array $context = [],
        string $query = '',
        ?Throwable $exception = null
    ): void {
        $this->writeDbLog(
            $message,
            $context,
            query: $query,
            exception: $exception
        );
        $exceptionOptions = array_merge(['query' => $query], $context);
        if ($exception instanceof PDOException) {
            $exceptionOptions['pdo_error_code'] = $exception->getCode();
            $exceptionOptions['pdo_error_infos'] = $exception->errorInfo;
        }

        throw new CentreonDbException($message, $exceptionOptions, $exception);
    }

    /**
     * Write SQL errors messages
     *
     * @param string         $message
     * @param array          $customContext
     * @param string         $query
     * @param Throwable|null $exception
     * @param string         $level
     */
    private function writeDbLog(
        string $message,
        array $customContext = [],
        string $query = '',
        ?Throwable $exception = null,
        string $level = CentreonLog::LEVEL_ERROR
    ): void {
        $defaultContext = ['db_name' => $this->dbConfig->dbName];
        if (! empty($query)) {
            $defaultContext['query'] = $query;
        }
        if ($exception instanceof PDOException) {
            $defaultContext['pdo_error_infos'] = $exception->errorInfo;
            $defaultContext['pdo_error_code'] = $exception->getCode();
        }
        $context = array_merge($defaultContext, $customContext);
        $this->logger->log(CentreonLog::TYPE_SQL, $level, "[CentreonDb] $message", $context, $exception);
    }

    /**
     * Validate a SELECT query
     *
     * @param string $query
     * @param string $queryKeyword
     * @param bool   $checkEmptyQuery
     *
     * @throws CentreonDbException
     */
    private function validateQueryString(string $query, string $queryKeyword, bool $checkEmptyQuery): void
    {
        if ($checkEmptyQuery && empty($query)) {
            throw new CentreonDbException(
                'Query must not be empty',
                ['query' => $query]
            );
        }
        if (
            ! str_starts_with($query, mb_strtoupper($queryKeyword) . ' ')
            && ! str_starts_with($query, mb_strtolower($queryKeyword) . ' ')
        ) {
            throw new CentreonDbException(
                'The query must to start by ' . mb_strtoupper($queryKeyword),
                ['query' => $query]
            );
        }
    }

    //******************************************** DEPRECATED METHODS ***********************************************//

    /**
     * @param PDOStatement $pdoStatement
     * @param int          $column
     *
     * @return array|bool
     *
     * @throws CentreonDbException
     *
     * @deprecated Instead use {@see CentreonDB::fetchByColumn()}
     * @see        CentreonDB::fetchByColumn()
     */
    public function fetchColumn(PDOStatement $pdoStatement, int $column = 0): mixed
    {
        try {
            return $pdoStatement->fetchColumn($column);
        } catch (Throwable $e) {
            $this->closeQuery($pdoStatement);
            $message = "Error while fetching all the rows by column: {$e->getMessage()}";
            $this->writeDbLog($message, ['column' => $column], query: $pdoStatement->queryString, exception: $e);
            $options = ['query' => $pdoStatement->queryString];
            if ($e instanceof PDOException) {
                $options['pdo_error_code'] = $e->getCode();
                $options['pdo_error_infos'] = $e->errorInfo;
            }
            throw new CentreonDbException($message, $options, $e);
        }
    }

    /**
     * Without prepared query
     *
     * @param string $query
     * @param int    $column
     *
     * @return array
     *
     * @throws CentreonDbException
     *
     * @deprecated Instead use {@see CentreonDB::fetchAllByColumn()}
     * @see        CentreonDB::fetchAllByColumn()
     */
    public function executeQueryFetchColumn(string $query, int $column = 0): array
    {
        try {
            $this->validateQueryString($query, 'SELECT', true);
            $pdoSth = $this->executeQuery($query, PDO::FETCH_COLUMN, [$column]);

            return $this->fetchAll($pdoSth);
        } catch (Throwable $e) {
            throw new CentreonDbException(
                "Error while fetching data with executeQueryFetchColumn() : {$e->getMessage()}",
                [
                    'query' => $query,
                    'column' => $column,
                ],
                $e
            );
        }
    }

    /**
     * Without prepared query
     *
     * @param string $query
     *
     * @return array
     * @throws CentreonDbException
     *
     * @deprecated Instead use {@see CentreonDB::fetchAllAssociative()}
     * @see        CentreonDB::fetchAllAssociative()
     */
    public function executeQueryFetchAll(string $query): array
    {
        try {
            $this->validateQueryString($query, 'SELECT', true);
            $pdoSth = $this->executeQuery($query);

            return $this->fetchAll($pdoSth);
        } catch (Throwable $e) {
            throw new CentreonDbException(
                "Error while fetching data with executeQueryFetchAll() : {$e->getMessage()}",
                ['query' => $query],
                $e
            );
        }
    }

    /**
     * @param mixed $val
     *
     * @return void
     * @deprecated No longer used by internal code and not recommended
     */
    public function autoCommit($val): void
    {
        /* Deprecated */
    }

    /**
     * Escapes a string for query
     *
     * @access     public
     *
     * @param string $str
     * @param bool   $htmlSpecialChars | htmlspecialchars() is used when true
     *
     * @return string
     *
     * @deprecated No longer used by internal code and not recommended, instead use {@see CentreonDB::escapeString()}
     * @see        CentreonDB::escapeString()
     */
    public static function escape($str, $htmlSpecialChars = false)
    {
        if ($htmlSpecialChars) {
            $str = htmlspecialchars($str);
        }

        return addslashes($str ?? '');
    }

    /**
     * Query
     *
     * @param string     $queryString
     * @param null|mixed $parameters
     * @param mixed      ...$parametersArgs
     *
     * @return CentreonDBStatement|false
     *
     * @throws PDOException
     * @deprecated Instead use fetch* methods
     *
     * #[\ReturnTypeWillChange] to fix the change of the method's signature and avoid a fatal error
     */
    #[ReturnTypeWillChange]
    public function query($queryString, $parameters = null, ...$parametersArgs): CentreonDBStatement | false
    {
        if (! is_null($parameters) && ! is_array($parameters)) {
            $parameters = [$parameters];
        }

        /*
         * Launch request
         */
        $sth = null;
        try {
            if (is_null($parameters)) {
                $sth = parent::query($queryString);
            } else {
                $sth = $this->prepare($queryString);
                $sth->execute($parameters);
            }
        } catch (PDOException $e) {
            // skip if we use CentreonDBStatement::execute method
            if (is_null($parameters)) {
                $queryString = str_replace(["`", '*'], ["", "\*"], $queryString);
                $this->writeDbLog(
                    'Error while using CentreonDb::query',
                    ['bind_params' => $parameters],
                    query: $queryString,
                    exception: $e
                );
            }
            throw $e;
        }

        $this->queryNumber++;
        $this->successQueryNumber++;

        return $sth;
    }

    /**
     * launch a getAll
     *
     * @access     public
     *
     * @param string       $query_string query
     * @param array<mixed> $placeHolders
     *
     * @return array|false  getAll result
     *
     * @throws PDOException
     *
     * @deprecated Instead use {@see CentreonDB::executeQuery(), CentreonDB::prepareQuery(), CentreonDB::executePreparedQuery()}
     * @see        CentreonDB::executeQuery(), CentreonDB::prepareQuery(), CentreonDB::executePreparedQuery()
     */
    public function getAll($query_string = null, $placeHolders = [])
    {
        $this->requestExecuted++;

        try {
            $result = $this->query($query_string);
            $rows = $result->fetchAll();
            $this->requestSuccessful++;
        } catch (PDOException $e) {
            $this->writeDbLog(
                'Error while using CentreonDb::getAll',
                query: $query_string,
                exception: $e
            );
            throw new PDOException($e->getMessage(), hexdec($e->getCode()));
        }

        return $rows;
    }

    /**
     * return number of rows
     *
     * @deprecated No longer used by internal code and not recommended, instead use {@see CentreonDB::executeQuery()}
     * @see        CentreonDB::executeQuery()
     */
    public function numberRows(): int
    {
        $number = 0;
        $dbResult = $this->query("SELECT FOUND_ROWS() AS number");
        $data = $dbResult->fetch();
        if (isset($data["number"])) {
            $number = $data["number"];
        }

        return (int) $number;
    }

    /**
     * checks if there is malicious injection
     *
     * @param string $sString
     *
     * @deprecated  No longer used by internal code and not recommended
     *
     * @description NOT DELETING BECAUSE IT USED IN centreon-modules/centreon-bam-server
     */
    public static function checkInjection($sString): int
    {
        return 0;
    }

}
