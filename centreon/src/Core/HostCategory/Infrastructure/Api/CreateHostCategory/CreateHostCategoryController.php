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

namespace Core\HostCategory\Infrastructure\Api\CreateHostCategory;

use Centreon\Application\Controller\AbstractController;
use Core\HostCategory\Application\UseCase\CreateHostCategory\CreateHostCategory;
use Core\HostCategory\Application\UseCase\CreateHostCategory\CreateHostCategoryRequest;
use Core\HostCategory\Infrastructure\Api\CreateHostCategory\CreateHostCategoryPresenter;
use Symfony\Component\HttpFoundation\Request;

class CreateHostCategoryController extends AbstractController
{
    public function __invoke(
        Request $request,
        CreateHostCategory $useCase,
        CreateHostCategoryPresenter $presenter
    ): object {
        $this->denyAccessUnlessGrantedForApiConfiguration();

        /** @var array{name:string,alias:string} $data */
        $data = $this->validateAndRetrieveDataSent($request, __DIR__ . '/CreateHostCategorySchema.json');

        $hostCategoryRequest = $this->createRequestDto($data);

        $useCase($hostCategoryRequest, $presenter);

        return $presenter->show();
    }

    /**
     * @param array{name:string,alias:string} $data
     * @return CreateHostCategoryRequest
     */
    private function createRequestDto(array $data): CreateHostCategoryRequest
    {
        $hostCategoryRequest = new CreateHostCategoryRequest();
        $hostCategoryRequest->name = $data['name'];
        $hostCategoryRequest->alias = $data['alias'];

        return $hostCategoryRequest;
    }
}
