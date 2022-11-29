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

namespace Tests\Core\Security\Vault\Application\UseCase\FindVaultConfigurations;

use Core\Security\Vault\Domain\Model\Vault;
use Core\Application\Common\UseCase\ErrorResponse;
use Core\Application\Common\UseCase\NotFoundResponse;
use Core\Application\Common\UseCase\ForbiddenResponse;
use Centreon\Domain\Contact\Interfaces\ContactInterface;
use Centreon\Legacy\EventLogs\Export\Presenter;
use Core\Security\Vault\Domain\Model\VaultConfiguration;
use Core\Infrastructure\Common\Presenter\PresenterFormatterInterface;
use Core\Security\Vault\Application\Exceptions\VaultConfigurationException;
use Core\Security\Vault\Application\Repository\ReadVaultRepositoryInterface;
use Core\Security\Vault\Application\Repository\ReadVaultConfigurationRepositoryInterface;
use Core\Security\Vault\Application\UseCase\FindVaultConfigurations\FindVaultConfigurations;
use Core\Security\Vault\Application\UseCase\UpdateVaultConfiguration\VaultConfigurationFactory;
use Core\Security\Vault\Application\UseCase\FindVaultConfigurations\FindVaultConfigurationsRequest;
use Core\Security\Vault\Application\UseCase\FindVaultConfigurations\FindVaultConfigurationsResponse;

beforeEach(function () {
    $this->readVaultConfigurationRepository = $this->createMock(ReadVaultConfigurationRepositoryInterface::class);
    $this->readVaultRepository = $this->createMock(ReadVaultRepositoryInterface::class);
    $this->presenterFormatter = $this->createMock(PresenterFormatterInterface::class);
    $this->factory = $this->createMock(VaultConfigurationFactory::class);
    $this->user = $this->createMock(ContactInterface::class);
});

it('should present Forbidden Response when user is not admin', function () {
    $this->user
        ->expects($this->once())
        ->method('isAdmin')
        ->willReturn(false);

    $presenter = new FindVaultConfigurationsPresenterStub($this->presenterFormatter);
    $useCase = new FindVaultConfigurations(
        $this->readVaultConfigurationRepository,
        $this->readVaultRepository,
        $this->user
    );

    $findVaultConfigurationsRequest = new FindVaultConfigurationsRequest();

    $useCase($presenter, $findVaultConfigurationsRequest);

    expect($presenter->getResponseStatus())->toBeInstanceOf(ForbiddenResponse::class);
    expect($presenter->getResponseStatus()?->getMessage())->toBe('Only admin user can create vault configuration');
});

it('should present NotFound Response when vault provider does not exist', function () {
    $this->user
        ->expects($this->once())
        ->method('isAdmin')
        ->willReturn(true);

    $this->readVaultRepository
        ->expects($this->once())
        ->method('findById')
        ->willReturn(null);

    $presenter = new FindVaultConfigurationsPresenterStub($this->presenterFormatter);
    $useCase = new FindVaultConfigurations(
        $this->readVaultConfigurationRepository,
        $this->readVaultRepository,
        $this->user
    );

    $findVaultConfigurationsRequest = new FindVaultConfigurationsRequest();

    $useCase($presenter, $findVaultConfigurationsRequest);

    expect($presenter->getResponseStatus())->toBeInstanceOf(NotFoundResponse::class);
    expect($presenter->getResponseStatus()?->getMessage())->toBe(
        (new NotFoundResponse('Vault provider'))->getMessage()
    );
});

it('should present ErrorResponse when an unhandled error occurs', function () {
    $this->user
        ->expects($this->once())
        ->method('isAdmin')
        ->willReturn(true);

    $this->readVaultRepository
        ->expects($this->once())
        ->method('findById')
        ->willThrowException(new \Exception());

    $presenter = new FindVaultConfigurationsPresenterStub($this->presenterFormatter);
    $useCase = new FindVaultConfigurations(
        $this->readVaultConfigurationRepository,
        $this->readVaultRepository,
        $this->user
    );

    $findVaultConfigurationsRequest = new FindVaultConfigurationsRequest();

    $useCase($presenter, $findVaultConfigurationsRequest);

    expect($presenter->getResponseStatus())->toBeInstanceOf(ErrorResponse::class);
    expect($presenter->getResponseStatus()?->getMessage())->toBe(
        VaultConfigurationException::impossibleToCreate()->getMessage()
    );
});

it('should present FindVaultConfigurationsResponse', function () {
    $this->user
        ->expects($this->once())
        ->method('isAdmin')
        ->willReturn(true);

    $vault = new Vault(1, 'myVaultProvider');

    $this->readVaultRepository
        ->expects($this->once())
        ->method('findById')
        ->willReturn($vault);

    $vaultConfiguration = new VaultConfiguration(
        1,
        'myVaultConfiguration',
        $vault,
        '127.0.0.1',
        8200,
        'myStorageFolder',
        'myRoleId',
        'mySecretId',
        'mySalt'
    );

    $this->readVaultConfigurationRepository
        ->expects($this->once())
        ->method('findVaultConfigurationsByVault')
        ->willReturn([$vaultConfiguration]);

    $presenter = new FindVaultConfigurationsPresenterStub($this->presenterFormatter);
    $useCase = new FindVaultConfigurations(
        $this->readVaultConfigurationRepository,
        $this->readVaultRepository,
        $this->user
    );

    $findVaultConfigurationsRequest = new FindVaultConfigurationsRequest();
    $findVaultConfigurationsRequest->vaultId = $vault->getId();

    $findVaultConfigurationsResponse = new FindVaultConfigurationsResponse();
    $findVaultConfigurationsResponse->vaultConfigurations = [
        [
            'id' => $vaultConfiguration->getId(),
            'name' => $vaultConfiguration->getName(),
            'vault_id' => $vault->getId(),
            'url' => $vaultConfiguration->getAddress(),
            'port' => $vaultConfiguration->getPort(),
            'storage' => 'myStorageFolder',
            'role_id' => 'myRoleId'
        ]
    ];

    $useCase($presenter, $findVaultConfigurationsRequest);

    expect($presenter->response)
        ->toBeInstanceOf(FindVaultConfigurationsResponse::class)
        ->and($presenter->response->vaultConfigurations)
        ->toBe($findVaultConfigurationsResponse->vaultConfigurations);
});
