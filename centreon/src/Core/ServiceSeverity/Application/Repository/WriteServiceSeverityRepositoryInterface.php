<?php

/*
 * Copyright 2005 - 2023 Centreon (https://www.centreon.com/)
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

namespace Core\ServiceSeverity\Application\Repository;

use Core\ServiceSeverity\Domain\Model\NewServiceSeverity;
use Core\ServiceSeverity\Domain\Model\ServiceSeverity;

interface WriteServiceSeverityRepositoryInterface
{
    /**
     * Delete service severity by id.
     *
     * @param int $serviceSeverityId
     */
    public function deleteById(int $serviceSeverityId): void;

    /**
     * Add a service severity
     * Return the id of the service severity.
     *
     * @param NewServiceSeverity $serviceSeverity
     *
     * @throws \Throwable
     *
     * @return int
     */
    public function add(NewServiceSeverity $serviceSeverity): int;

    /**
     * Update a service severity.
     *
     * @param ServiceSeverity $severity
     *
     * @throws \Throwable
     */
    public function update(ServiceSeverity $severity): void;
}
