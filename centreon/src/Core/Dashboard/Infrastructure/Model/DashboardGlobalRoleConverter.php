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

namespace Core\Dashboard\Infrastructure\Model;

use Core\Dashboard\Domain\Model\Role\DashboardGlobalRole;

class DashboardGlobalRoleConverter
{
    public static function toString(DashboardGlobalRole $role): string
    {
        return match ($role) {
            DashboardGlobalRole::Administrator => 'administrator',
            DashboardGlobalRole::Creator => 'creator',
            DashboardGlobalRole::Viewer => 'viewer',
        };
    }

    /**
     * @param string $role
     *
     * @throws \UnexpectedValueException
     *
     * @return DashboardGlobalRole
     */
    public static function fromString(string $role): DashboardGlobalRole
    {
        return match ($role) {
            'Administrator' => DashboardGlobalRole::Administrator,
            'Creator' => DashboardGlobalRole::Creator,
            'Viewer' => DashboardGlobalRole::Viewer,
            default => throw new \UnexpectedValueException('Invalid role provided')
        };
    }
}
