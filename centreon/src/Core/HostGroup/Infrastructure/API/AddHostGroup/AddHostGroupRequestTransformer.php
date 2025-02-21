<?php

/*
 * Copyright 2005 - 2025 Centreon (https://www.centreon.com/)
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

namespace Core\HostGroup\Infrastructure\API\AddHostGroup;

use Core\HostGroup\Application\UseCase\AddHostGroup\AddHostGroupRequest;

final class AddHostGroupRequestTransformer
{
    /**
     * @param AddHostGroupInput $input
     * @return AddHostGroupRequest
     */
    public static function transform(AddHostGroupInput $input): AddHostGroupRequest
    {
        $request = new AddHostGroupRequest();
        $request->name = $input->name;
        $request->alias = $input->alias ?? '';
        $request->geoCoords = $input->geoCoords;
        $request->comment = $input->comment ?? '';

        return $request;
    }
}
