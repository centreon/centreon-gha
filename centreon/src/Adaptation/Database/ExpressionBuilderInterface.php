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

declare(strict_types=1);

namespace Adaptation\Database;

/**
 * Interface
 *
 * @class   ExpressionBuilderInterface
 * @package Adaptation\Database
 *
 * To dynamically create SQL query parts.
 */
interface ExpressionBuilderInterface
{
    /**
     * Comparison operators.
     */
    public const EQ = '=';
    public const NEQ = '<>';
    public const LT = '<';
    public const LTE = '<=';
    public const GT = '>';
    public const GTE = '>=';

    /**
     * Creates a conjunction of the given expressions.
     *
     * @param string $expression
     * @param string ...$expressions
     *
     * @return string
     *
     * @example
     *         method : and("field1 = :value1", ["field2 = :value2","field3 = :value3"])
     *         return : "(field1 = :value1) AND (field2 = :value2) AND (field3 = :value3)"
     */
    public function and(string $expression, string ...$expressions): string;

    /**
     * Creates a disjunction of the given expressions.
     *
     * @param string $expression
     * @param string ...$expressions
     *
     * @return string
     *
     * @example
     *         method : or("field1 = :value1", ["field2 = :value2","field3 = :value3"])
     *         return : "(field1 = :value1) OR (field2 = :value2) OR (field3 = :value3)"
     */
    public function or(string $expression, string ...$expressions): string;

    /**
     * Creates a comparison expression.
     *
     * @param string $x the left expression
     * @param string $operator the comparison operator
     * @param string $y the right expression
     *
     * @return string
     *
     * @example
     *          method : comparison('field1', '=', ':value1')
     *          return : "field1 = :value1"
     */
    public function comparison(string $x, string $operator, string $y): string;

    /**
     * Creates an equality comparison expression with the given arguments.
     *
     * First argument is considered the left expression and the second is the right expression.
     * When converted to string, it will generated a <left expr> = <right expr>.
     *
     * @param string $x the left expression
     * @param string $y the right expression
     *
     * @return string
     *
     * @example
     *         method : equal('field1', ':value1')
     *         return : "field1 = :value1"
     */
    public function equal(string $x, string $y): string;

    /**
     * Creates a non equality comparison expression with the given arguments.
     *
     * First argument is considered the left expression and the second is the right expression.
     * When converted to string, it will generated a <left expr> <> <right expr>.
     *
     * @param string $x the left expression
     * @param string $y the right expression
     *
     * @return string
     *
     * @example
     *         method : notEqual('field1', ':value1')
     *         return : "field1 <> :value1"
     */
    public function notEqual(string $x, string $y): string;

    /**
     * Creates a lower-than comparison expression with the given arguments.
     *
     * First argument is considered the left expression and the second is the right expression.
     * When converted to string, it will generated a <left expr> < <right expr>.
     *
     * @param string $x the left expression
     * @param string $y the right expression
     *
     * @return string
     *
     * @example
     *         method : lowerThan('field1', ':value1')
     *         return : "field1 < :value1"
     */
    public function lowerThan(string $x, string $y): string;

    /**
     * Creates a lower-than-equal comparison expression with the given arguments.
     *
     * First argument is considered the left expression and the second is the right expression.
     * When converted to string, it will generated a <left expr> <= <right expr>.
     *
     * @param string $x the left expression
     * @param string $y the right expression
     *
     * @return string
     *
     * @example
     *         method : lowerThanEqual('field1', ':value1')
     *         return : "field1 <= :value1"
     */
    public function lowerThanEqual(string $x, string $y): string;

    /**
     * Creates a greater-than comparison expression with the given arguments.
     * First argument is considered the left expression and the second is the right expression.
     * When converted to string, it will generated a <left expr> > <right expr>.
     *
     * @param string $x the left expression
     * @param string $y the right expression
     *
     * @return string
     *
     * @example
     *         method : greaterThan('field1', ':value1')
     *         return : "field1 > :value1"
     */
    public function greaterThan(string $x, string $y): string;

    /**
     * Creates a greater-than-equal comparison expression with the given arguments.
     *
     * First argument is considered the left expression and the second is the right expression.
     * When converted to string, it will generated a <left expr> >= <right expr>.
     *
     * @param string $x the left expression
     * @param string $y the right expression
     *
     * @return string
     *
     * @example
     *         method : greaterThanEqual('field1', ':value1')
     *         return : "field1 >= :value1"
     */
    public function greaterThanEqual(string $x, string $y): string;

    /**
     * Creates an IS NULL expression with the given arguments.
     *
     * @param string $x the expression to be restricted by IS NULL
     *
     * @return string
     *
     * @example
     *         method : isNull('field1')
     *         return : "field1 IS NULL"
     */
    public function isNull(string $x): string;

    /**
     * Creates an IS NOT NULL expression with the given arguments.
     *
     * @param string $x the expression to be restricted by IS NOT NULL
     *
     * @return string
     *
     * @example
     *         method : isNotNull('field1')
     *         return : "field1 IS NOT NULL"
     */
    public function isNotNull(string $x): string;

    /**
     * Creates a LIKE comparison expression.
     *
     * @param string $expression The expression to be inspected by the LIKE comparison
     * @param string $pattern The pattern to compare against
     * @param string|null $escapeChar To indicate the escape character, by default it's '\'  (optional)
     *
     * @return string
     *
     * @example
     *         method : like('field1', ':value1')
     *         return : "field1 LIKE :value1"
     *         method : like('field1', ':value1','$')
     *         return : "field1 LIKE :value1" ESCAPE '$'
     */
    public function like(string $expression, string $pattern, ?string $escapeChar = null): string;

    /**
     * Creates a NOT LIKE comparison expression
     *
     * @param string $expression The expression to be inspected by the NOT LIKE comparison
     * @param string $pattern The pattern to compare against
     * @param string|null $escapeChar To indicate the escape character, by default it's '\' (optional)
     *
     * @return string
     *
     * @example
     *         method : notLike('field1', ':value1')
     *         return : "field1 NOT LIKE :value1"
     *         method : notLike('field1', ':value1','$')
     *         return : "field1 NOT LIKE :value1" ESCAPE '$'
     */
    public function notLike(string $expression, string $pattern, ?string $escapeChar = null): string;

    /**
     * Creates an IN () comparison expression with the given arguments.
     *
     * @param string $x the SQL expression to be matched against the set
     * @param string|string[] $y the SQL expression or an array of SQL expressions representing the set
     *
     * @return string
     *
     * @example
     *          method : in('field1', [:value1, :value2, :value3])
     *          return : "field1 IN (:value1, :value2, :value3)"
     */
    public function in(string $x, string|array $y): string;

    /**
     * Creates a NOT IN () comparison expression with the given arguments.
     *
     * @param string $x the SQL expression to be matched against the set
     * @param string|string[] $y the SQL expression or an array of SQL expressions representing the set
     *
     * @return string
     *
     * @example
     *          method : notIn('field1', [:value1, :value2, :value3])
     *          return : "field1 NOT IN (:value1, :value2, :value3)"
     */
    public function notIn(string $x, string|array $y): string;
}
