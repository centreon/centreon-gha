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

namespace Tests\Adaptation\Database\Adapter\Pdo\Transformer;

use Adaptation\Database\Connection\Adapter\Pdo\Transformer\PdoParameterTypeTransformer;
use Adaptation\Database\Connection\Enum\QueryParameterTypeEnum;
use Core\Common\Domain\Exception\TransformerException;

it('test pdo parameter type transformer : transform', function () {
    $type = PdoParameterTypeTransformer::transformFromQueryParameterType(QueryParameterTypeEnum::STRING);
    expect($type)->toBeInt()->toBe(\PDO::PARAM_STR);
    $type = \Adaptation\Database\Connection\Adapter\Pdo\Transformer\PdoParameterTypeTransformer::transformFromQueryParameterType(QueryParameterTypeEnum::INTEGER);
    expect($type)->toBeInt()->toBe(\PDO::PARAM_INT);
    $type = PdoParameterTypeTransformer::transformFromQueryParameterType(QueryParameterTypeEnum::LARGE_OBJECT);
    expect($type)->toBeInt()->toBe(\PDO::PARAM_LOB);
    $type = \Adaptation\Database\Connection\Adapter\Pdo\Transformer\PdoParameterTypeTransformer::transformFromQueryParameterType(QueryParameterTypeEnum::NULL);
    expect($type)->toBeInt()->toBe(\PDO::PARAM_NULL);
    $type = \Adaptation\Database\Connection\Adapter\Pdo\Transformer\PdoParameterTypeTransformer::transformFromQueryParameterType(QueryParameterTypeEnum::BOOLEAN);
    expect($type)->toBeInt()->toBe(\PDO::PARAM_BOOL);
});

it('test pdo parameter type transformer : reverse', function () {
    $type = \Adaptation\Database\Connection\Adapter\Pdo\Transformer\PdoParameterTypeTransformer::reverseToQueryParameterType(\PDO::PARAM_STR);
    expect($type)->toBe(QueryParameterTypeEnum::STRING);
    $type = \Adaptation\Database\Connection\Adapter\Pdo\Transformer\PdoParameterTypeTransformer::reverseToQueryParameterType(\PDO::PARAM_INT);
    expect($type)->toBe(QueryParameterTypeEnum::INTEGER);
    $type = \Adaptation\Database\Connection\Adapter\Pdo\Transformer\PdoParameterTypeTransformer::reverseToQueryParameterType(\PDO::PARAM_LOB);
    expect($type)->toBe(QueryParameterTypeEnum::LARGE_OBJECT);
    $type = PdoParameterTypeTransformer::reverseToQueryParameterType(\PDO::PARAM_NULL);
    expect($type)->toBe(QueryParameterTypeEnum::NULL);
    $type = \Adaptation\Database\Connection\Adapter\Pdo\Transformer\PdoParameterTypeTransformer::reverseToQueryParameterType(\PDO::PARAM_BOOL);
    expect($type)->toBe(QueryParameterTypeEnum::BOOLEAN);
});

it('test pdo parameter type transformer : reverse with a bad pdo type', function () {
    $type = \Adaptation\Database\Connection\Adapter\Pdo\Transformer\PdoParameterTypeTransformer::reverseToQueryParameterType(999999);
})->throws(TransformerException::class);
