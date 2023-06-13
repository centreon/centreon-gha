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
 * For more information : user@centreon.com
 *
 */

declare(strict_types=1);

namespace Core\Notification\Application\UseCase\FindNotification;

use Core\Notification\Application\Converter\NotificationHostEventConverter;
use Core\Notification\Application\Converter\NotificationServiceEventConverter;

class FindNotificationResponse
{
    public int $id = 0;

    public string $name = '';

    public int $timeperiodId = 1;

    public string $timeperiodName = '24x7';

    public bool $isActivated = true;

    /**
     * @var array<array{
     *  channel: string,
     *  subject: string,
     *  message: string
     * }>
     */
    public array $messages = [];

    /**
     * @var array<array{
     *  id: int,
     *  name: string
     * }>
     */
    public array $users = [];

    /**
     * @var array<array{
     *  type: string,
     *  events: int,
     *  ids: int[],
     *  event_services?: int
     * }>
     */
    public array $resources = [];

    /**
     * @param NotificationHostEvent[]|NotificationServiceEvent[] $enums
     * @return int
     */
    public function convertHostEventsToBitFlags(array $enums): int
    {
        /**
         * @var NotificationHostEvent[] $enums
         */
        return NotificationHostEventConverter::toBitFlags($enums);
    }

    /**
     * @param NotificationServiceEvent[]|NotificationHostEvent[] $enums
     * @return int
     */
    public function convertServiceEventsToBitFlags(array $enums): int
    {
        /**
         * @var NotificationServiceEvent[] $enums
         */
        return NotificationServiceEventConverter::toBitFlags($enums);
    }

}