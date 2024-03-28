<?php

/*
 * Copyright 2005 - 2024 Centreon (https://www.centreon.com/)
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * https://www.apache.org/licenses/LICENSE-2.0
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

namespace Core\Application\Configuration\MetaService\Repository;

use Core\Domain\Configuration\Model\MetaService;
use Core\Domain\Configuration\Model\MetaServiceNamesById;

interface ReadMetaServiceRepositoryInterface
{
    /**
     * Find MetaService configuration without ACL.
     *
     * @param int $metaId
     *
     * @return MetaService|null
     */
    public function findMetaServiceById(int $metaId): ?MetaService;

    /**
     * Find MetaService configuration with ACL.
     *
     * @param int $metaId
     * @param int[] $accessGroupIds
     *
     * @return MetaService|null
     */
    public function findMetaServiceByIdAndAccessGroupIds(int $metaId, array $accessGroupIds): ?MetaService;

    /**
     * @param int[] $metaIds
     *
     * @return int[]
     */
    public function exist(array $metaIds): array;

    /**
     * Find Meta Services names by their IDs.
     *
     * @param int ...$ids
     *
     * @return MetaServiceNamesById
     */
    public function findNames(int ...$ids): MetaServiceNamesById;
}
