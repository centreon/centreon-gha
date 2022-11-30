<?php

/*
 * Copyright 2005 - 2022 Centreon (https://www.centreon.com/)
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

namespace Core\TimePeriod\Domain\Model;

class TimePeriod extends NewTimePeriod
{
    /**
     * @var TimePeriodException[]
     */
    private array $exceptions = [];

    /**
     * @param int $id
     * @param string $name
     * @param string $alias
     */
    public function __construct(
        private int $id,
        string $name,
        string $alias,
    ) {
        parent::__construct($name, $alias);
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param TimePeriodException $exception
     * @return void
     */
    public function addException(NewTimePeriodException $exception): void
    {
        $this->exceptions[] = $exception;
    }

    /**
     * @return TimePeriodException[]
     */
    public function getExceptions(): array
    {
        return $this->exceptions;
    }

    /**
     * @param TimePeriodException[] $exceptions
     */
    public function setExceptions(array $exceptions): void
    {
        $this->exceptions = [];
        foreach ($exceptions as $exception) {
            $this->addException($exception);
        }
    }
}
