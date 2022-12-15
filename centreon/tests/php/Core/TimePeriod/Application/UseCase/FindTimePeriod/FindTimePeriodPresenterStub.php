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

namespace Tests\Core\TimePeriod\Application\UseCase\FindTimePeriod;

use Core\Application\Common\UseCase\{AbstractPresenter, CreatedResponse, PresenterInterface};
use Core\TimePeriod\Application\UseCase\FindTimePeriod\FindTimePeriodResponse;
use Symfony\Component\HttpFoundation\Response;

class FindTimePeriodPresenterStub extends AbstractPresenter implements PresenterInterface
{
    /**
     * @var FindTimePeriodResponse
     */
    public $response;

    /**
     * @param FindTimePeriodResponse $response
     */
    public function present(mixed $response): void
    {
        $this->response = $response;
    }

    /**
     * @return Response
     */
    public function show(): Response
    {
        return new Response();
    }
}
