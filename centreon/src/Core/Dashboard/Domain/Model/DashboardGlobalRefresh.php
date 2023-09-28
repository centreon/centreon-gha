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

namespace Core\Dashboard\Domain\Model;

use Core\Dashboard\Domain\Model\Refresh\DashboardGlobalRefreshType;

class DashboardGlobalRefresh
{
    /**
     * @param DashboardGlobalRefreshType $refreshType
     * @param int|null $refreshInterval
     */
    public function __construct(
        private readonly DashboardGlobalRefreshType $refreshType,
        private readonly ?int $refreshInterval
    ) {
    }

    /**
     * @return DashboardGlobalRefreshType
     */
    public function getRefreshType(): DashboardGlobalRefreshType
    {
        return $this->refreshType;
    }

    /**
     * @return int|null
     */
    public function getRefreshInterval(): ?int
    {
        return $this->refreshInterval;
    }
}
