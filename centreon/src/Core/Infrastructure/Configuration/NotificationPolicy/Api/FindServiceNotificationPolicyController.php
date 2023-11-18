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

namespace Core\Infrastructure\Configuration\NotificationPolicy\Api;

use Centreon\Application\Controller\AbstractController;
use Core\Application\Configuration\NotificationPolicy\UseCase\FindNotificationPolicyPresenterInterface;
use Core\Application\Configuration\NotificationPolicy\UseCase\FindServiceNotificationPolicy;

final class FindServiceNotificationPolicyController extends AbstractController
{
    /**
     * @param int $hostId
     * @param int $serviceId
     * @param FindServiceNotificationPolicy $useCase
     * @param FindNotificationPolicyPresenterInterface $presenter
     *
     * @return object
     */
    public function __invoke(
        int $hostId,
        int $serviceId,
        FindServiceNotificationPolicy $useCase,
        FindNotificationPolicyPresenterInterface $presenter
    ): object {
        /**
         * Access denied if no rights given to the configuration and realtime for the current user.
         */
        $this->denyAccessUnlessGrantedForApiConfiguration();
        $this->denyAccessUnlessGrantedForApiRealtime();

        $useCase($hostId, $serviceId, $presenter);

        return $presenter->show();
    }
}
