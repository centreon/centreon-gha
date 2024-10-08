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

namespace Tests\Core\AgentConfiguration\Application\UseCase\FindPollers;

use Centreon\Domain\Contact\Interfaces\ContactInterface;
use Centreon\Domain\RequestParameters\Interfaces\RequestParametersInterface;
use Core\AgentConfiguration\Application\UseCase\FindPollers\FindPollersResponse;
use Core\AgentConfiguration\Application\Exception\AgentConfigurationException;
use Core\AgentConfiguration\Application\UseCase\FindPollers\FindPollers;
use Core\AgentConfiguration\Application\Repository\ReadAgentConfigurationRepositoryInterface;
use Core\AgentConfiguration\Application\UseCase\FindPollers\PollerDto;
use Core\AgentConfiguration\Domain\Model\Poller;
use Core\Application\Common\UseCase\ErrorResponse;
use Core\Security\AccessGroup\Application\Repository\ReadAccessGroupRepositoryInterface;

beforeEach(function (): void {
    $this->useCase = new FindPollers(
        user: $this->user = $this->createMock(ContactInterface::class),
        requestParameters: $this->requestParameters = $this->createMock(RequestParametersInterface::class),
        readRepository: $this->readRepository = $this->createMock(ReadAgentConfigurationRepositoryInterface::class),
        readAccessGroupRepository: $this->readAccessGroupRepository = $this->createMock(ReadAccessGroupRepositoryInterface::class),
    );
    $this->presenter = new FindPollersPresenterStub();
});

it('should present an ErrorResponse when an exception is thrown', function () {
    $this->user
        ->expects($this->once())
        ->method('isAdmin')
        ->willReturn(false);

    $this->readRepository
        ->expects($this->once())
        ->method('findAvailablePollersByRequestParametersAndAccessGroups')
        ->willThrowException(new \Exception());

    $response = ($this->useCase)($this->presenter);

    expect($response)
        ->toBeInstanceOf(ErrorResponse::class)
        ->and($response->getMessage())
        ->toBe(AgentConfigurationException::findPollers()->getMessage());
});

it('should retrieve poller with no ACLs calculation for an admin user', function () {
    $this->user
        ->expects($this->once())
        ->method('isAdmin')
        ->willReturn(true);

    $this->readRepository
        ->expects($this->once())
        ->method('findAvailablePollersByRequestParameters');

    ($this->useCase)($this->presenter);
});

it('should retrieve poller with ACLs calculation for a non admin user', function () {
    $this->user
        ->expects($this->once())
        ->method('isAdmin')
        ->willReturn(false);

    $this->readRepository
        ->expects($this->once())
        ->method('findAvailablePollersByRequestParametersAndAccessGroups');

    ($this->useCase)($this->presenter);
});

it('should present a FindPollersResponse when no errors occurred', function () {
    $pollerOne = new Poller(1, 'pollerOne');

    $pollerTwo = new Poller(2, 'pollerTwo');

    $this->user
        ->expects($this->once())
        ->method('isAdmin')
        ->willReturn(false);

    $this->readRepository
        ->expects($this->once())
        ->method('findAvailablePollersByRequestParametersAndAccessGroups')
        ->willReturn([$pollerOne, $pollerTwo]);

    $response = ($this->useCase)($this->presenter);

    expect($response)
        ->toBeInstanceOf(FindPollersResponse::class)
        ->and($response->pollers)
        ->toBeArray()
        ->and($response->pollers[0])
        ->toBeInstanceOf(Poller::class)
        ->and($response->pollers[0]->id)
        ->toBe($pollerOne->getId())
        ->and($response->pollers[0]->name)
        ->toBe($pollerOne->getName())
        ->and($response->pollers[1])
        ->toBeInstanceOf(Poller::class)
        ->and($response->pollers[1]->id)
        ->toBe($pollerTwo->getId())
        ->and($response->pollers[1]->name)
        ->toBe($pollerTwo->getName());
});