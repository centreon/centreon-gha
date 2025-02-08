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

namespace Adaptation\Database\Collection;

use Adaptation\Database\ValueObject\QueryParameter;
use Core\Common\Domain\Collection\Collection;
use Core\Common\Domain\Exception\CollectionException;

/**
 * Class
 *
 * @class   QueryParameters
 * @package Adaptation\Database\Collection
 */
class QueryParameters extends Collection
{
    /**
     * @return string
     */
    protected function itemClass(): string
    {
        return QueryParameter::class;
    }

    /**
     * Factory
     *
     * @param QueryParameter[] $queryParameters
     *
     * @throws CollectionException
     * @return QueryParameters
     */
    public static function create(array $queryParameters): QueryParameters
    {
        $queryParametersCollection = new QueryParameters();
        foreach ($queryParameters as $queryParameter) {
            $queryParametersCollection->add($queryParameter->name, $queryParameter);
        }
        return $queryParametersCollection;
    }
}
