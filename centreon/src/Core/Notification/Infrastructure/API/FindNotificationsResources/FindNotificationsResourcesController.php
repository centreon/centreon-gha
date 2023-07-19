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

namespace Core\Notification\Infrastructure\API\FindNotificationsResources;

use Centreon\Application\Controller\AbstractController;
use Centreon\Domain\Log\LoggerTrait;
use Core\Application\Common\UseCase\InvalidArgumentResponse;
use Core\Notification\Application\UseCase\FindNotificationsResources\FindNotificationsResources;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

final class FindNotificationsResourcesController extends AbstractController
{
    use LoggerTrait;
    /**
     * @param FindNotificationsResources $useCase
     * @param FindNotificationsResourcesPresenter $presenter
     *
     * @throws AccessDeniedException
     *
     * @return Response
     */
    public function __invoke(
        Request $request,
        FindNotificationsResources $useCase,
        FindNotificationsResourcesPresenter $presenter
    ): Response {

        $this->denyAccessUnlessGrantedForApiConfiguration();

        $requestUID = $request->headers->get('X-Notifications-Resources-UID', null);
        if (! \is_string($requestUID)) {
            $presenter->presentResponse(new InvalidArgumentResponse('Missing header'));
            $this->error(
                'Missing header "X-Notifications-Resources-UID"',
                ['X-Notifications-Resources-UID' => $requestUID]
            );
        } else {
            $useCase($presenter, $requestUID);
        }

        return $presenter->show();
    }
}
