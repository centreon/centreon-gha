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

namespace Tests\Core\AdditionalConnector\Application\UseCase\FindPollers;

use Centreon\Domain\Contact\Interfaces\ContactInterface;
use Centreon\Domain\RequestParameters\Interfaces\RequestParametersInterface;
use Core\AdditionalConnector\Application\Exception\AdditionalConnectorException;
use Core\AdditionalConnector\Application\Repository\ReadAdditionalConnectorRepositoryInterface;
use Core\AdditionalConnector\Application\UseCase\FindPollers\FindPollers;
use Core\AdditionalConnector\Application\UseCase\FindPollers\FindPollersResponse;
use Core\AdditionalConnector\Domain\Model\Poller;
use Core\Application\Common\UseCase\ErrorResponse;
use Core\Application\Common\UseCase\ForbiddenResponse;
use Core\Security\AccessGroup\Application\Repository\ReadAccessGroupRepositoryInterface;

beforeEach(function (): void {
    $this->useCase = new FindPollers(
        $this->requestParameters = $this->createMock(RequestParametersInterface::class),
        $this->readAdditionalConnectorRepository = $this->createMock(ReadAdditionalConnectorRepositoryInterface::class),
        $this->readAccessGroupRepository = $this->createMock(ReadAccessGroupRepositoryInterface::class),
        $this->user = $this->createMock(ContactInterface::class),
    );
    $this->presenter = new FindPollersPresenterStub();

    $this->type = 'vmware_v6';

    $this->poller = new Poller(1, 'my-poller');
});

it('should present a ForbiddenResponse when a user has insufficient rights', function (): void {
    $this->user
        ->expects($this->once())
        ->method('hasTopologyRole')
        ->willReturn(false);

    ($this->useCase)($this->type, $this->presenter);

    expect($this->presenter->data)
        ->toBeInstanceOf(ForbiddenResponse::class)
        ->and($this->presenter->data->getMessage())
        ->toBe(AdditionalConnectorException::accessNotAllowed()->getMessage());
});

it('should present a ErrorResponse when an exception is thrown', function (): void {
    $this->user
        ->expects($this->once())
        ->method('hasTopologyRole')
        ->willReturn(true);
    $this->user
        ->expects($this->once())
        ->method('isAdmin')
        ->willReturn(true);
    $this->readAdditionalConnectorRepository
        ->expects($this->once())
        ->method('findAvailablePollersByType')
        ->willThrowException(new \Exception());

    ($this->useCase)($this->type, $this->presenter);

    expect($this->presenter->data)
        ->toBeInstanceOf(ErrorResponse::class)
        ->and($this->presenter->data->getMessage())
        ->toBe(AdditionalConnectorException::findPollers($this->type)->getMessage());
});

it('should present a FindPollersResponse on success', function (): void {
    $this->user
        ->expects($this->once())
        ->method('hasTopologyRole')
        ->willReturn(true);
    $this->user
        ->expects($this->once())
        ->method('isAdmin')
        ->willReturn(true);
   $this->readAdditionalConnectorRepository
       ->expects($this->once())
       ->method('findAvailablePollersByType')
       ->willReturn([$this->poller]);

    ($this->useCase)($this->type, $this->presenter);

    $result = $this->presenter->data;
    expect($result)
        ->toBeInstanceOf(FindPollersResponse::class)
        ->and($result->pollers[0]->id)->toBe($this->poller->id)
        ->and($result->pollers[0]->name)->toBe($this->poller->name);
});
