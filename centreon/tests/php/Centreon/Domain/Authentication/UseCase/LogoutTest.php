<?php

/*
 * Copyright 2005 - 2021 Centreon (https://www.centreon.com/)
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

namespace Tests\Centreon\Domain\Authentication\UseCase;

use PHPUnit\Framework\TestCase;
use Centreon\Domain\Authentication\UseCase\Logout;
use Centreon\Domain\Authentication\UseCase\LogoutRequest;
use Security\Domain\Authentication\Interfaces\AuthenticationServiceInterface;
use Security\Domain\Authentication\Interfaces\AuthenticationRepositoryInterface;

/**
 * @package Tests\Centreon\Domain\Authentication\UseCase
 */
class LogoutTest extends TestCase
{
    /**
     * @var AuthenticationServiceInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $authenticationService;

    /**
     * @var AuthenticationRepositoryInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $authenticationRepository;

    protected function setUp(): void
    {
        $this->authenticationService = $this->createMock(AuthenticationServiceInterface::class);
        $this->authenticationRepository = $this->createMock(AuthenticationRepositoryInterface::class);
    }

    /**
     * test execute
     */
    public function testExecute(): void
    {
        $logout = new Logout($this->authenticationService, $this->authenticationRepository);

        $logoutRequest = new LogoutRequest('abc123');

        $this->authenticationService
            ->expects($this->once())
            ->method('deleteExpiredSecurityTokens');

        $this->authenticationRepository
            ->expects($this->once())
            ->method('deleteSecurityToken')
            ->with('abc123aaaa  ');

        $logout->execute($logoutRequest);
    }
}
