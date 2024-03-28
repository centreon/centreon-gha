<?php

/*
 * Copyright 2005 - 2024 Centreon (https://www.centreon.com/)
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

namespace Core\Category\RealTime\Infrastructure\Api\FindServiceCategory;

use Centreon\Domain\RequestParameters\Interfaces\RequestParametersInterface;
use Core\Application\Common\UseCase\AbstractPresenter;
use Core\Category\RealTime\Application\UseCase\FindServiceCategory\FindServiceCategoryPresenterInterface;
use Core\Category\RealTime\Application\UseCase\FindServiceCategory\FindServiceCategoryResponse;
use Core\Infrastructure\Common\Presenter\PresenterFormatterInterface;

class FindServiceCategoryPresenter extends AbstractPresenter implements FindServiceCategoryPresenterInterface
{
    /**
     * @param RequestParametersInterface $requestParameters
     * @param PresenterFormatterInterface $presenterFormatter
     */
    public function __construct(
        private RequestParametersInterface $requestParameters,
        protected PresenterFormatterInterface $presenterFormatter,
    ) {
    }

    /**
     * {@inheritDoc}
     *
     * @param FindServiceCategoryResponse $data
     */
    public function present(mixed $data): void
    {
        parent::present([
            'result' => $data->tags,
            'meta' => $this->requestParameters->toArray(),
        ]);
    }
}
