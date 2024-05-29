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

namespace Tests\Core\Security\Vault\Application\UseCase\MigrateAllCredentials;

use Core\Application\Common\UseCase\ResponseStatusInterface;
use Core\Security\Vault\Application\UseCase\MigrateAllCredentials\MigrateAllCredentialsPresenterInterface;
use Core\Security\Vault\Application\UseCase\MigrateAllCredentials\MigrateAllCredentialsResponse;

class MigrateAllCredentialsPresenterStub implements MigrateAllCredentialsPresenterInterface
{
    public ResponseStatusInterface|MigrateAllCredentialsResponse $response;

    public function presentResponse(ResponseStatusInterface|MigrateAllCredentialsResponse $response): void
    {
        $this->response = $response;
    }
}