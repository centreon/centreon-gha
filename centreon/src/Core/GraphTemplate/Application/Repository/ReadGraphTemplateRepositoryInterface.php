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

namespace Core\GraphTemplate\Application\Repository;

use Centreon\Domain\RequestParameters\Interfaces\RequestParametersInterface;
use Core\GraphTemplate\Domain\Model\GraphTemplate;

interface ReadGraphTemplateRepositoryInterface
{
    /**
     * Determine if a graph template exists by its ID.
     *
     * @param int $id
     *
     * @throws \Throwable
     *
     * @return bool
     */
    public function exists(int $id): bool;

    /**
     * Search for all commands based on request parameters.
     *
     * @param RequestParametersInterface $requestParameters
     *
     * @throws \Throwable
     *
     * @return GraphTemplate[]
     */
    public function findByRequestParameters(RequestParametersInterface $requestParameters): array;
}
