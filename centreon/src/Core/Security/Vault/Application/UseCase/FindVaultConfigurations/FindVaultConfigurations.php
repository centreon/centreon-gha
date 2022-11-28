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

namespace Core\Security\Vault\Application\UseCase\FindVaultConfigurations;

use Centreon\Domain\Log\LoggerTrait;
use Core\Application\Common\UseCase\ErrorResponse;
use Core\Application\Common\UseCase\NotFoundResponse;
use Core\Application\Common\UseCase\ForbiddenResponse;
use Centreon\Domain\Contact\Interfaces\ContactInterface;
use Core\Security\Vault\Domain\Model\VaultConfiguration;
use Core\Security\Vault\Application\Exceptions\VaultConfigurationException;
use Core\Security\Vault\Application\Repository\ReadVaultRepositoryInterface;
use Core\Security\Vault\Application\Repository\ReadVaultConfigurationRepositoryInterface;
use Core\Security\Vault\Application\UseCase\FindVaultConfigurations\FindVaultConfigurationsPresenterInterface;

final class FindVaultConfigurations
{
    use LoggerTrait;

    /**
     * @param ReadVaultConfigurationRepositoryInterface $readVaultConfigurationRepository
     * @param ReadVaultRepositoryInterface $readVaultRepository
     * @param ContactInterface $user
     */
    public function __construct(
        private ReadVaultConfigurationRepositoryInterface $readVaultConfigurationRepository,
        private ReadVaultRepositoryInterface $readVaultRepository,
        private ContactInterface $user
    ) {
    }

    public function __invoke(
        FindVaultConfigurationsPresenterInterface $presenter,
        FindVaultConfigurationsRequest $findVaultConfigurationsRequest
    ): void {
        try {
            if (! $this->user->isAdmin()) {
                $this->error('User is not admin', ['user' => $this->user->getName()]);
                $presenter->setResponseStatus(
                    new ForbiddenResponse(VaultConfigurationException::onlyForAdmin()->getMessage())
                );

                return;
            }

            if (! $this->isVaultExists($findVaultConfigurationsRequest->vaultId)) {
                $this->error('Vault provider not found', ['id' => $findVaultConfigurationsRequest->vaultId]);
                $presenter->setResponseStatus(
                    new NotFoundResponse('Vault provider')
                );

                return;
            }

            $vaultConfigurations = $this->readVaultConfigurationRepository->getVaultConfigurationsByVault(
                $findVaultConfigurationsRequest->vaultId
            );

            $presenter->present($this->createResponse($vaultConfigurations));
        } catch (\Throwable $ex) {
            $this->error(
                'An error occured in while getting vault configurations',
                ['trace' => $ex->getTraceAsString()]
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
     * @param VaultConfiguration[] $vaultConfigurations
     *
     * @return FindVaultConfigurationsResponse
     */
    private function createResponse(array $vaultConfigurations): FindVaultConfigurationsResponse
    {
        return new FindVaultConfigurationsResponse($vaultConfigurations);
    }
}
