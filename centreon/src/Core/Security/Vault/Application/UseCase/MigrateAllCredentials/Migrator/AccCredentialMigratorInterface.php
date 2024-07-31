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

declare(strict_types = 1);

namespace Core\Security\Vault\Application\UseCase\MigrateAllCredentials\Migrator;

use Core\AdditionalConnector\Domain\Model\AdditionalConnector;
use Core\AdditionalConnector\Domain\Model\Type;
use Core\Security\Vault\Application\UseCase\MigrateAllCredentials\CredentialDto;

interface AccCredentialMigratorInterface
{
    /**
     * @param Type $type
     *
     * @return bool
     */
    public function isValidFor(Type $type): bool;

    /**
     * @param AdditionalConnector $acc
     *
     * @return CredentialDto[]
     */
    public function createCredentialDtos(AdditionalConnector $acc): array;

    /**
     * @param AdditionalConnector $acc
     * @param CredentialDto $credential
     * @param string $vaultPath
     *
     * @return AdditionalConnector
     */
    public function updateMigratedCredential(
        AdditionalConnector $acc,
        CredentialDto $credential,
        string $vaultPath
    ): AdditionalConnector;
}
