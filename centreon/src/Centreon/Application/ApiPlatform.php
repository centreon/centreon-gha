<?php

/*
 * Copyright 2005 - 2020 Centreon (https://www.centreon.com/)
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

namespace Centreon\Application;

/**
 * Used to get API parameters
 */
class ApiPlatform
{
    /**
<<<<<<< HEAD
     * @var float
=======
     * @var string
>>>>>>> centreon/dev-21.10.x
     */
    private $version;

    /**
     * Get the API version
     *
<<<<<<< HEAD
     * @return float
     */
    public function getVersion(): float
=======
     * @return string
     */
    public function getVersion(): string
>>>>>>> centreon/dev-21.10.x
    {
        return $this->version;
    }

    /**
     * Set the API version
     *
<<<<<<< HEAD
     * @param float $version
     * @return $this
     */
    public function setVersion(float $version): self
=======
     * @param string $version
     * @return $this
     */
    public function setVersion(string $version): self
>>>>>>> centreon/dev-21.10.x
    {
        $this->version = $version;
        return $this;
    }
}
