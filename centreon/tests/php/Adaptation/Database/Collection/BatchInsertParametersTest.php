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

use Adaptation\Database\Collection\BatchInsertParameters;
use Adaptation\Database\Collection\QueryParameters;
use Adaptation\Database\ValueObject\QueryParameter;
use Core\Common\Domain\Collection\Collection;
use Core\Common\Domain\Exception\CollectionException;

beforeEach(function () {
    $this->batchInsertParameters = new BatchInsertParameters();
});

it('test batch insert parameters collection : create with good type', function () {
    $batchInsertParam1 = QueryParameters::create([
        QueryParameter::int('contact_id', 110),
        QueryParameter::string('contact_name', 'foo_name'),
        QueryParameter::string('contact_alias', 'foo_alias')
    ]);
    $batchInsertParam2 = QueryParameters::create([
        QueryParameter::int('contact_id', 111),
        QueryParameter::string('contact_name', 'bar_name'),
        QueryParameter::string('contact_alias', 'bar_alias')
    ]);
    $batchInsertParam3 = QueryParameters::create([
        QueryParameter::int('contact_id', 112),
        QueryParameter::string('contact_name', 'baz_name'),
        QueryParameter::string('contact_alias', 'baz_alias')
    ]);
    $batchQueryParameters = BatchInsertParameters::create([
        $batchInsertParam1,
        $batchInsertParam2,
        $batchInsertParam3
    ]);
    expect($batchQueryParameters->length())->toBe(3)
        ->and($batchQueryParameters->get('batch_insert_param_1'))->toBe($batchInsertParam1)
        ->and($batchQueryParameters->get('batch_insert_param_2'))->toBe($batchInsertParam2)
        ->and($batchQueryParameters->get('batch_insert_param_3'))->toBe($batchInsertParam3);
});

it('test batch insert parameters collection : create with bad type', function () {
    BatchInsertParameters::create([
        new stdClass(),
        new stdClass(),
        new stdClass()
    ]);
})->throws(CollectionException::class);

it('test batch insert parameters collection : create with different length', function () {
    $batchInsertParam1 = QueryParameters::create([
        QueryParameter::int('contact_id', 110),
        QueryParameter::string('contact_name', 'foo_name'),
        QueryParameter::string('contact_alias', 'foo_alias')
    ]);
    $batchInsertParam2 = QueryParameters::create([
        QueryParameter::int('contact_id', 111),
        QueryParameter::string('contact_name', 'bar_name')
    ]);
    BatchInsertParameters::create([
        $batchInsertParam1,
        $batchInsertParam2
    ]);
})->throws(CollectionException::class);
