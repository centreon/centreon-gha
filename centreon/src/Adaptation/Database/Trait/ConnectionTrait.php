<?php

/*
 * Copyright 2005 - 2025 Centreon (https://www.centreon.com/)
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
declare(strict_types=1);

namespace Adaptation\Database\Trait;

use Adaptation\Database\Collection\BatchInsertParameters;
use Adaptation\Database\Collection\QueryParameters;
use Adaptation\Database\ConnectionInterface;
use Adaptation\Database\Exception\ConnectionException;
use Adaptation\Database\ExpressionBuilderInterface;
use Adaptation\Database\Model\ConnectionConfig;
use Adaptation\Database\QueryBuilderInterface;
use Adaptation\Database\ValueObject\QueryParameter;

/**
 * Trait
 *
 * @class   ConnectionTrait
 * @package Adaptation\Database\Trait
 */
trait ConnectionTrait
{

    // ----------------------------------------- FACTORY METHODS -----------------------------------------

    /**
     * Factory
     *
     * @param ConnectionConfig $connectionConfig
     *
     * @throws ConnectionException
     * @return ConnectionInterface
     */
    public static function createFromConfig(ConnectionConfig $connectionConfig): ConnectionInterface
    {
        throw ConnectionException::notImplemented('createFromConfig');
    }

    /**
     * Creates a new instance of a SQL query builder.
     *
     * @throws ConnectionException
     * @return QueryBuilderInterface
     */
    public function createQueryBuilder(): QueryBuilderInterface
    {
        throw ConnectionException::notImplemented('createQueryBuilder');
    }

    /**
     * Creates an expression builder for the connection.
     *
     * @throws ConnectionException
     * @return ExpressionBuilderInterface
     */
    public function createExpressionBuilder(): ExpressionBuilderInterface
    {
        throw ConnectionException::notImplemented('createExpressionBuilder');
    }

    // ----------------------------------------- CUD METHODS -----------------------------------------

    /**
     * Executes an SQL statement with the given parameters and returns the number of affected rows.
     *
     * Could be only used for INSERT.
     *
     * @param string $query
     * @param QueryParameters|null $queryParameters
     *
     * @throws ConnectionException
     * @return int
     *
     * @example $queryParameters = QueryParameters::create([QueryParameter::int('id', 1), QueryParameter::string('name', 'John')]);
     *          $nbAffectedRows = $db->insert('INSERT INTO table (id, name) VALUES (:id, :name)', $queryParameters);
     *          // $nbAffectedRows = 1
     */
    public function insert(string $query, ?QueryParameters $queryParameters = null): int
    {
        try {
            if (! str_starts_with($query, 'INSERT INTO ')
                && ! str_starts_with($query, 'insert into ')
            ) {
                throw ConnectionException::insertQueryBadFormat($query);
            }

            return $this->executeStatement($query, $queryParameters);
        } catch (\Throwable $exception) {
            throw ConnectionException::insertQueryFailed($exception, $query, $queryParameters);
        }
    }

    /**
     * Executes an SQL statement with the given parameters and returns the number of affected rows for multiple inserts.
     *
     * Could be only used for several INSERT.
     *
     * @param string $tableName
     * @param array<string> $columns
     * @param BatchInsertParameters $batchInsertParameters
     *
     * @throws ConnectionException
     * @return int
     *
     * @example $batchInsertParameters = BatchInsertParameters::create([
     *              QueryParameters::create([QueryParameter::int('id', 1), QueryParameter::string('name', 'John')]),
     *              QueryParameters::create([QueryParameter::int('id', 2), QueryParameter::string('name', 'Jean')]),
     *          ]);
     *          $nbAffectedRows = $db->batchInsert('table', ['id', 'name'], $batchInsertParameters);
     *          // $nbAffectedRows = 2
     */
    public function batchInsert(string $tableName, array $columns, BatchInsertParameters $batchInsertParameters): int
    {
        try {
            if (empty($tableName)) {
                throw ConnectionException::batchInsertQueryBadUsage('Table name must not be empty');
            }
            if (empty($columns)) {
                throw ConnectionException::batchInsertQueryBadUsage('Columns must not be empty');
            }
            if ($batchInsertParameters->isEmpty()) {
                throw ConnectionException::batchInsertQueryBadUsage('Batch insert parameters must not be empty');
            }

            $query = "INSERT INTO {$tableName} (" . implode(', ', $columns) . ') VALUES';

            $valuesInsert = [];
            $queryParametersToInsert = new QueryParameters([]);

            $indexQueryParameterToInsert = 1;

            /*
             * $batchInsertParameters is a collection of QueryParameters, each QueryParameters is a collection of QueryParameter
             * We need to iterate over the QueryParameters to build the final query.
             * Then, for each QueryParameters, we need to iterate over the QueryParameter to build :
             *  - to check if the query parameters are not empty (queryParameters)
             *  - to check if the columns and query parameters have the same length (columns, queryParameters)
             *  - to rename the parameter name to avoid conflicts with a suffix (indexQueryParameterToInsert)
             *  - the values block of the query (valuesInsert)
             *  - the query parameters to insert (queryParametersToInsert)
             */

            foreach ($batchInsertParameters->getIterator() as $queryParameters) {
                if ($queryParameters->isEmpty()) {
                    throw ConnectionException::batchInsertQueryBadUsage('Query parameters must not be empty');
                }
                if (count($columns) !== $queryParameters->length()) {
                    throw ConnectionException::batchInsertQueryBadUsage(
                        'Columns and query parameters must have the same length'
                    );
                }

                $valuesInsertItem = '';

                foreach ($queryParameters->getIterator() as $queryParameter) {
                    if (! empty($valuesInsertItem)) {
                        $valuesInsertItem .= ', ';
                    }
                    $parameterName = "{$queryParameter->getName()}_{$indexQueryParameterToInsert}";
                    $queryParameterToInsert = QueryParameter::create(
                        $parameterName,
                        $queryParameter->getValue(),
                        $queryParameter->getType()
                    );
                    $valuesInsertItem .= ":{$parameterName}";
                    $queryParametersToInsert->add($queryParameterToInsert->getName(), $queryParameterToInsert);
                }

                $valuesInsert[] = "({$valuesInsertItem})";
                $indexQueryParameterToInsert++;
            }

            if (count($valuesInsert) === $queryParametersToInsert->length()) {
                throw ConnectionException::batchInsertQueryBadUsage(
                    'Error while building the final query : values block and query parameters have not the same length'
                );
            }

            $query .= implode(', ', $valuesInsert);

            return $this->executeStatement($query, $queryParametersToInsert);
        } catch (\Throwable $exception) {
            throw ConnectionException::batchInsertQueryFailed(
                previous: $exception,
                tableName: $tableName,
                columns: $columns,
                batchInsertParameters: $batchInsertParameters,
                query: $query ?? ''
            );
        }
    }

    /**
     * Executes an SQL statement with the given parameters and returns the number of affected rows.
     *
     * Could be only used for UPDATE.
     *
     * @param string $query
     * @param QueryParameters|null $queryParameters
     *
     * @throws ConnectionException
     * @return int
     *
     * @example $queryParameters = QueryParameters::create([QueryParameter::int('id', 1), QueryParameter::string('name', 'John')]);
     *          $nbAffectedRows = $db->update('UPDATE table SET name = :name WHERE id = :id', $queryParameters);
     *          // $nbAffectedRows = 1
     */
    public function update(string $query, ?QueryParameters $queryParameters = null): int
    {
        try {
            if (! str_starts_with($query, 'UPDATE ')
                && ! str_starts_with($query, 'update ')
            ) {
                throw ConnectionException::updateQueryBadFormat($query);
            }

            return $this->executeStatement($query, $queryParameters);
        } catch (\Throwable $exception) {
            throw ConnectionException::updateQueryFailed($exception, $query, $queryParameters);
        }
    }

    /**
     * Executes an SQL statement with the given parameters and returns the number of affected rows.
     *
     * Could be only used for DELETE.
     *
     * @param string $query
     * @param QueryParameters|null $queryParameters
     *
     * @throws ConnectionException
     * @return int
     *
     * @example $queryParameters = QueryParameters::create([QueryParameter::int('id', 1)]);
     *          $nbAffectedRows = $db->delete('DELETE FROM table WHERE id = :id', $queryParameters);
     *          // $nbAffectedRows = 1
     */
    public function delete(string $query, ?QueryParameters $queryParameters = null): int
    {
        try {
            if (! str_starts_with($query, 'DELETE ')
                && ! str_starts_with($query, 'delete ')
            ) {
                throw ConnectionException::deleteQueryBadFormat($query);
            }

            return $this->executeStatement($query, $queryParameters);
        } catch (\Throwable $exception) {
            throw ConnectionException::deleteQueryFailed($exception, $query, $queryParameters);
        }
    }

    // ----------------------------------------- FETCH METHODS -----------------------------------------

    /**
     * Prepares and executes an SQL query and returns the result as an associative array with the keys mapped
     * to the first column and the values being an associative array representing the rest of the columns
     * and their values.
     *
     * Could be only used with SELECT.
     *
     * @param string $query
     * @param QueryParameters|null $queryParameters
     *
     * @throws ConnectionException
     * @return array<mixed,array<string,mixed>>
     *
     * @example $queryParameters = QueryParameters::create([QueryParameter::bool('active', true)]);
     *          $result = $db->fetchAllAssociativeIndexed('SELECT id, name, surname FROM table WHERE active = :active', $queryParameters);
     *          // $result = [1 => ['name' => 'John', 'surname' => 'Doe'], 2 => ['name' => 'Jean', 'surname' => 'Dupont']]
     */
    public function fetchAllAssociativeIndexed(string $query, ?QueryParameters $queryParameters = null): array
    {
        try {
            $data = [];
            foreach ($this->fetchAllAssociative($query, $queryParameters) as $row) {
                $data[array_shift($row)] = $row;
            }

            return $data;
        } catch (\Throwable $exception) {
            throw ConnectionException::fetchAllAssociativeIndexedQueryFailed($exception, $query, $queryParameters);
        }
    }

    // ----------------------------------------- ITERATE METHODS -----------------------------------------

    /**
     * Prepares and executes an SQL query and returns the result as an iterator over rows represented as numeric arrays.
     *
     * Could be only used with SELECT.
     *
     * @param string $query
     * @param QueryParameters|null $queryParameters
     *
     * @throws ConnectionException
     * @return \Traversable<int,list<mixed>>
     *
     * @example $queryParameters = QueryParameters::create([QueryParameter::bool('active', true)]);
     *          $result = $db->iterateNumeric('SELECT * FROM table WHERE active = :active', $queryParameters);
     *          foreach ($result as $row) {
     *              // $row = [0 => 1, 1 => 'John', 2 => 'Doe']
     *              // $row = [0 => 2, 1 => 'Jean', 2 => 'Dupont']
     *          }
     */
    public function iterateNumeric(string $query, ?QueryParameters $queryParameters = null): \Traversable
    {
        try {
            $this->validateSelectQuery($query);

            while (($row = $this->fetchNumeric($query, $queryParameters)) !== false) {
                yield $row;
            }
        } catch (\Throwable $exception) {
            throw ConnectionException::iterateNumericQueryFailed($exception, $query, $queryParameters);
        }
    }

    /**
     * Prepares and executes an SQL query and returns the result as an iterator over rows represented
     * as associative arrays.
     *
     * Could be only used with SELECT.
     *
     * @param string $query
     * @param QueryParameters|null $queryParameters
     *
     * @throws ConnectionException
     * @return \Traversable<int,array<string,mixed>>
     *
     * @example $queryParameters = QueryParameters::create([QueryParameter::bool('active', true)]);
     *          $result = $db->iterateAssociative('SELECT * FROM table WHERE active = :active', $queryParameters);
     *          foreach ($result as $row) {
     *              // $row = ['id' => 1, 'name' => 'John', 'surname' => 'Doe']
     *              // $row = ['id' => 2, 'name' => 'Jean', 'surname' => 'Dupont']
     *          }
     */
    public function iterateAssociative(string $query, ?QueryParameters $queryParameters = null): \Traversable
    {
        try {
            $this->validateSelectQuery($query);

            while (($row = $this->fetchAssociative($query, $queryParameters)) !== false) {
                yield $row;
            }
        } catch (\Throwable $exception) {
            throw ConnectionException::iterateAssociativeQueryFailed($exception, $query, $queryParameters);
        }
    }

    /**
     * Prepares and executes an SQL query and returns the result as an iterator over the column values.
     *
     * Could be only used with SELECT.
     *
     * @param string $query
     * @param QueryParameters|null $queryParameters
     *
     * @throws ConnectionException
     * @return \Traversable<int,list<mixed>>
     *
     * @example $queryParameters = QueryParameters::create([QueryParameter::bool('active', true)]);
     *          $result = $db->iterateFirstColumn('SELECT name FROM table WHERE active = :active', $queryParameters);
     *          foreach ($result as $value) {
     *              // $value = 'John'
     *              // $value = 'Jean'
     *          }
     */
    public function iterateColumn(string $query, ?QueryParameters $queryParameters = null): \Traversable
    {
        try {
            $this->validateSelectQuery($query);

            while (($value = $this->fetchOne($query, $queryParameters)) !== false) {
                yield $value;
            }
        } catch (\Throwable $exception) {
            throw ConnectionException::iterateColumnQueryFailed($exception, $query, $queryParameters);
        }
    }

    /**
     * Prepares and executes an SQL query and returns the result as an iterator with the keys
     * mapped to the first column and the values mapped to the second column.
     *
     * Could be only used with SELECT.
     *
     * @param string $query
     * @param QueryParameters|null $queryParameters
     *
     * @throws ConnectionException
     * @return \Traversable<mixed,mixed>
     *
     * @example $queryParameters = QueryParameters::create([QueryParameter::bool('active', true)]);
     *          $result = $db->iterateKeyValue('SELECT name, surname FROM table WHERE active = :active', $queryParameters);
     *          foreach ($result as $key => $value) {
     *              // $key = 'John', $value = 'Doe'
     *              // $key = 'Jean', $value = 'Dupont'
     *          }
     */
    public function iterateKeyValue(string $query, ?QueryParameters $queryParameters = null): \Traversable
    {
        try {
            $this->validateSelectQuery($query);
            foreach ($this->iterateNumeric($query, $queryParameters) as $row) {
                if (count($row) < 2) {
                    throw ConnectionException::iterateKeyValueQueryBadFormat(
                        'The query must return at least two columns',
                        $query
                    );
                }
                [$key, $value] = $row;

                yield $key => $value;
            }
        } catch (\Throwable $exception) {
            throw ConnectionException::iterateKeyValueQueryFailed($exception, $query, $queryParameters);
        }
    }

    /**
     * Prepares and executes an SQL query and returns the result as an iterator with the keys mapped
     * to the first column and the values being an associative array representing the rest of the columns
     * and their values.
     *
     * Could be only used with SELECT.
     *
     * @param string $query
     * @param QueryParameters|null $queryParameters
     *
     * @throws ConnectionException
     * @return \Traversable<mixed,array<string,mixed>>
     *
     * @example $queryParameters = QueryParameters::create([QueryParameter::bool('active', true)]);
     *          $result = $db->iterateAssociativeIndexed('SELECT id, name, surname FROM table WHERE active = :active', $queryParameters);
     *          foreach ($result as $key => $row) {
     *              // $key = 1, $row = ['name' => 'John', 'surname' => 'Doe']
     *              // $key = 2, $row = ['name' => 'Jean', 'surname' => 'Dupont']
     *          }
     */
    public function iterateAssociativeIndexed(string $query, ?QueryParameters $queryParameters = null): \Traversable
    {
        try {
            $this->validateSelectQuery($query);

            foreach ($this->iterateAssociative($query, $queryParameters) as $row) {
                yield array_shift($row) => $row;
            }
        } catch (\Throwable $exception) {
            throw ConnectionException::iterateAssociativeIndexedQueryFailed($exception, $query, $queryParameters);
        }
    }

    // ----------------------------------------- PRIVATE METHODS -----------------------------------------

    /**
     * @param string $query
     *
     * @throws ConnectionException
     * @return void
     */
    private function validateSelectQuery(string $query): void
    {
        if (empty($query)) {
            throw ConnectionException::notEmptyQuery();
        }
        if (! str_starts_with($query, 'SELECT') && ! str_starts_with($query, 'select')) {
            throw ConnectionException::selectQueryBadFormat($query);
        }
    }
}
