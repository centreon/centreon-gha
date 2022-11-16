<?php

/*
 * Copyright 2005 - 2022 Centreon (https://www.centreon.com/)
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

namespace Core\Security\Vault\Application\UseCase\UpdateVaultConfiguration;

use Assert\InvalidArgumentException;
use Centreon\Domain\Log\LoggerTrait;
use Core\Application\Common\UseCase\ErrorResponse;
use Core\Application\Common\UseCase\ForbiddenResponse;
use Centreon\Domain\Contact\Interfaces\ContactInterface;
use Core\Security\Vault\Domain\Exceptions\VaultException;
use Core\Application\Common\UseCase\InvalidArgumentResponse;
use Core\Application\Common\UseCase\NoContentResponse;
use Core\Security\Vault\Domain\Exceptions\VaultConfigurationException;
use Core\Security\Vault\Application\Repository\ReadVaultRepositoryInterface;
use Core\Security\Vault\Application\Repository\ReadVaultConfigurationRepositoryInterface;
use Core\Security\Vault\Application\Repository\WriteVaultConfigurationRepositoryInterface;

final class UpdateVaultConfiguration
{
    use LoggerTrait;

    /**
     * @param ReadVaultConfigurationRepositoryInterface $readVaultConfigurationRepository
     * @param WriteVaultConfigurationRepositoryInterface $writeVaultConfigurationRepository
     * @param ReadVaultRepositoryInterface $readVaultRepository
     * @param VaultConfigurationFactory $factory
     * @param ContactInterface $user
     */
    public function __construct(
        private ReadVaultConfigurationRepositoryInterface $readVaultConfigurationRepository,
        private WriteVaultConfigurationRepositoryInterface $writeVaultConfigurationRepository,
        private ReadVaultRepositoryInterface $readVaultRepository,
        private VaultConfigurationFactory $factory,
        private ContactInterface $user
    ) {
    }

    public function __invoke(
        UpdateVaultConfigurationPresenterInterface $presenter,
        UpdateVaultConfigurationRequest $updateVaultConfigurationRequest
    ): void {
        try {
            if (! $this->user->isAdmin()) {
                $presenter->setResponseStatus(
                    new ForbiddenResponse('Only admin user can create vault configuration')
                );

                return;
            }

            if (! $this->isVaultExists($updateVaultConfigurationRequest->typeId)) {
                $presenter->setResponseStatus(
                    new InvalidArgumentResponse(VaultException::providerDoesNotExist()->getMessage())
                );

                return;
            }

            if (! $this->isVaultConfigurationExists($updateVaultConfigurationRequest->vaultConfigurationId)) {
                $presenter->setResponseStatus(
                    new InvalidArgumentResponse(
                        VaultConfigurationException::configurationWithIdDoesNotExist()->getMessage()
                    )
                );

                return;
            }

            $vaultConfiguration = $this->factory->create($updateVaultConfigurationRequest);

            if ($this->isSameVaultConfigurationExists(
                $vaultConfiguration->getAddress(),
                $vaultConfiguration->getPort(),
                $vaultConfiguration->getStorage())
            ) {
                $presenter->setResponseStatus(
                    new InvalidArgumentResponse(VaultConfigurationException::configurationExists()->getMessage())
                );

                return;
            }

            $this->writeVaultConfigurationRepository->update($vaultConfiguration);
            $presenter->setResponseStatus(new NoContentResponse());
        } catch (InvalidArgumentException|VaultException $ex) {
            $this->error('Some parameters are not valid', ['trace' => (string) $ex]);
            $presenter->setResponseStatus(
                new InvalidArgumentResponse($ex->getMessage())
            );

            return;
        } catch (\Throwable $ex) {
            $this->error(
                'An error occured in while creating vault configuration',
                ['trace' => (string) $ex]
            );
            $presenter->setResponseStatus(
                new ErrorResponse(VaultConfigurationException::impossibleToCreate()->getMessage())
            );

            return;
        }
    }

    /**
     * Checks if vault provider exists.
     *
     * @param int $id
     *
     * @return bool
     */
    private function isVaultExists(int $id): bool
    {
        return $this->readVaultRepository->findById($id) !== null;
    }

    /**
     * Checks if vault configuration exists.
     *
     * @param int $id
     *
     * @return bool
     */
    private function isVaultConfigurationExists(int $id): bool
    {
        return $this->readVaultConfigurationRepository->findById($id) !== null;
    }

    /**
     * Checks if same vault configuration exists.
     *
     * @param string $address
     * @param int $port
     * @param string $storage
     *
     * @throws \Throwable
     *
     * @return bool
     */
    private function isSameVaultConfigurationExists(
        string $address,
        int $port,
        string $storage,
    ): bool {
        if (
            $this->readVaultConfigurationRepository->findByAddressAndPortAndStorage(
                $address,
                $port,
                $storage
            ) !== null
        ) {
            $this->error(
                'Vault configuration with these properties already exists',
                [
                    'address' => $address,
                    'port' => $port,
                    'storage' => $storage,
                ]
            );

            return true;
        }

        return false;
    }
}
