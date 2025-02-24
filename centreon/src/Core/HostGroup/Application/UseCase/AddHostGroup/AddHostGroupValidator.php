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

namespace Core\HostGroup\Application\UseCase\AddHostGroup;

use Centreon\Domain\Contact\Interfaces\ContactInterface;
use Centreon\Domain\Log\LoggerTrait;
use Core\Contact\Application\Repository\ReadContactGroupRepositoryInterface;
use Core\Host\Application\Repository\ReadHostRepositoryInterface;
use Core\HostGroup\Application\Exceptions\HostGroupException;
use Core\HostGroup\Application\Repository\ReadHostGroupRepositoryInterface;
use Core\ResourceAccess\Application\Repository\ReadResourceAccessRepositoryInterface;
use Core\Security\AccessGroup\Application\Repository\ReadAccessGroupRepositoryInterface;

final class AddHostGroupValidator
{
    use LoggerTrait;

    public function __construct(
        private readonly ReadHostGroupRepositoryInterface $readHostGroupRepository,
        private readonly ReadResourceAccessRepositoryInterface $readResourceAccessRepository,
        private readonly ReadContactGroupRepositoryInterface $readContactGroupRepository,
        private readonly ReadHostRepositoryInterface $readHostRepository,
        private readonly ReadAccessGroupRepositoryInterface $readAccessGroupRepository,
        private readonly ContactInterface $user
    ) {
    }

    /**
     * @param AddHostGroupRequest $request
     *
     * @throws HostGroupException
     *
     * @throws \Throwable
     */
    public function assertNameDoesNotAlreadyExists(string $hostGroupName): void
    {
        if ($this->readHostGroupRepository->nameAlreadyExists($hostGroupName)) {
            throw HostGroupException::nameAlreadyExists($hostGroupName);
        }
    }

    public function assertHostsExist(array $hostIds): void
    {
        $unexistentHosts = $this->user->isAdmin()
        ? array_diff($hostIds, $this->readHostRepository->exist($hostIds))
        : array_filter($hostIds, function ($hostId) {
            return ! $this->readHostRepository->existsByAccessGroups(
                $hostId,
                $this->readAccessGroupRepository->findByContact($this->user)
            );
        });

        if (! empty($unexistentHosts)) {
            $this->warning(
                'Some hosts are not accessible by the user, they will not be linked to the host group.',
                ['unexistentHosts' => $unexistentHosts]
            );
            throw new HostGroupException(
                'Some hosts are not accessible by the user, they will not be linked to the host group.',

            );
        }
    }

    public function assertResourceAccessRulesExist(array $resourceAccessRuleIds): void
    {     // Add Link between RAM rule and HG
        $unexistentAccessRules = array_diff(
            $resourceAccessRuleIds,
            $this->readResourceAccessRepository->exist($resourceAccessRuleIds)
        );

        if (! empty($unexistentAccessRules)) {
            throw new \Exception('Some rules do not exist');
        }

        $existentRulesByContact = $this->readResourceAccessRepository->existByContact($this->user->getId());
        $existentRulesByContactGroup = $this->readResourceAccessRepository->existByContactGroup(
            $this->readContactGroupRepository->findAllByUserId($this->user->getId())
        );

        $existentRules = array_unique(
            array_merge($resourceAccessRuleIds, $existentRulesByContact, $existentRulesByContactGroup)
        );

        if ([] === array_intersect($existentRules, $resourceAccessRuleIds)) {
            throw new \Exception('Some rules do not exist');
        }
    }
}
