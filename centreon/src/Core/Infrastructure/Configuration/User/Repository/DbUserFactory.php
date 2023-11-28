<?php

/*
 * Copyright 2005 - 2022 Centreon (https://www.centreon.com/)
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

namespace Core\Infrastructure\Configuration\User\Repository;

use Core\Domain\Configuration\User\Model\User;

/**
 * @phpstan-import-type _UserRecord from DbReadUserRepository
 */
class DbUserFactory
{
    /**
     * @param _UserRecord $user
     *
     * @throws \Assert\AssertionFailedException
     *
     * @return User
     * @throws \Assert\AssertionFailedException
     */
    public static function createFromRecord(array $user): User
    {
        return new User(
            (int) $user['contact_id'],
            $user['contact_alias'],
            $user['contact_name'],
            $user['contact_email'],
            $user['contact_admin'] === '1',
            $user['contact_theme'],
            $user['user_interface_density'],
            $user['user_can_reach_frontend'] === '1'
        );
    }
}
