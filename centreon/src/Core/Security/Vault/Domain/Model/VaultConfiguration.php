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

namespace Core\Security\Vault\Domain\Model;

use Assert\AssertionFailedException;
use Security\Interfaces\EncryptionInterface;

/**
 * This class represents already existing vault configuration.
 */
class VaultConfiguration extends NewVaultConfiguration
{
    private int $id;

    /**
     * @param EncryptionInterface $encryption
     * @param int $id
     * @param string $name
     * @param Vault $vault
     * @param string $address
     * @param int $port
     * @param string $storage
     * @param string $unencryptedRoleId
     * @param string $unencryptedSecretId
     *
     * @throws AssertionFailedException
     */
    public function __construct(
        EncryptionInterface $encryption,
        int $id,
        string $name,
        Vault $vault,
        string $address,
        int $port,
        string $storage,
        string $unencryptedRoleId,
        string $unencryptedSecretId
    ) {
        $this->id = $id;
        parent::__construct(
            $encryption,
            $name,
            $vault,
            $address,
            $port,
            $storage,
            $unencryptedRoleId,
            $unencryptedSecretId
        );
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }
}
