<?php

/*
 * Copyright 2005 - 2024 Centreon (https://www.centreon.com/)
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

namespace Centreon\Domain\HostConfiguration\Interfaces\HostMacro;

use Centreon\Domain\HostConfiguration\Host;
use Centreon\Domain\HostConfiguration\HostMacro;

/**
 * This interface gathers all the reading operations on the repository.
 *
 * @package Centreon\Domain\HostConfiguration\Interfaces\HostMacro
 */
interface HostMacroReadRepositoryInterface
{
    /**
     * Find all macros linked to a host.
     *
     * @param Host $host
     * @return HostMacro[]
     */
    public function findAllByHost(Host $host): array;
}
