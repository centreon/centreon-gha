<?php

/*
 * Copyright 2005 - 2021 Centreon (https://www.centreon.com/)
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

namespace Centreon\Domain\ServiceConfiguration;

use Centreon\Domain\Macro\Interfaces\MacroInterface;
use Centreon\Domain\Annotation\EntityDescriptor;

class ServiceMacro implements MacroInterface
{
    /**
<<<<<<< HEAD
     * @var int|null
=======
     * @var int
>>>>>>> centreon/dev-21.10.x
     */
    private $id;

    /**
<<<<<<< HEAD
     * @var string|null Macro name
=======
     * @var string Macro name
>>>>>>> centreon/dev-21.10.x
     */
    private $name;

    /**
     * @var string|null Macro value
     */
    private $value;

    /**
     * @var bool Indicates whether this macro contains a password
     * @EntityDescriptor(column="is_password", modifier="setPassword")
     */
    private $isPassword = false;

    /**
     * @var string|null Macro description
     */
    private $description;

    /**
     * @var int|null
     */
    private $order;

    /**
     * @var int|null
     */
    private $serviceId;

    /**
<<<<<<< HEAD
     * @return int|null
     */
    public function getId(): ?int
=======
     * @return int
     */
    public function getId(): int
>>>>>>> centreon/dev-21.10.x
    {
        return $this->id;
    }

    /**
<<<<<<< HEAD
     * @param int|null $id
     * @return self
     */
    public function setId(?int $id): self
=======
     * @param int $id
     * @return self
     */
    public function setId(int $id): self
>>>>>>> centreon/dev-21.10.x
    {
        $this->id = $id;
        return $this;
    }

    /**
<<<<<<< HEAD
     * @return string|null
     */
    public function getName(): ?string
=======
     * @return string
     */
    public function getName(): string
>>>>>>> centreon/dev-21.10.x
    {
        return $this->name;
    }

    /**
<<<<<<< HEAD
     * @param string|null $name
     * @return self
     */
    public function setName(?string $name): self
    {
        $patternToBeFound = '$_SERVICE';
        if ($name !== null) {
            if (strpos($name, $patternToBeFound) !== 0) {
                $name = $patternToBeFound . $name;
                if ($name[-1] !== '$') {
                    $name .= '$';
                }
            }
            $this->name = strtoupper($name);
        } else {
            $this->name = null;
        }
=======
     * @param string $name
     * @return self
     */
    public function setName(string $name): self
    {
        $patternToBeFound = '$_SERVICE';
        if (strpos($name, $patternToBeFound) !== 0) {
            $name = $patternToBeFound . $name;
            if ($name[-1] !== '$') {
                $name .= '$';
            }
        }
        $this->name = strtoupper($name);
>>>>>>> centreon/dev-21.10.x
        return $this;
    }

    /**
     * @return string|null
     */
    public function getValue(): ?string
    {
        return $this->value;
    }

    /**
     * @param string|null $value
     * @return self
     */
    public function setValue(?string $value): self
    {
        $this->value = $value;
        return $this;
    }

    /**
     * @return bool
     */
    public function isPassword(): bool
    {
        return $this->isPassword;
    }

    /**
     * @param bool $isPassword
     * @return self
     */
    public function setPassword(bool $isPassword): self
    {
        $this->isPassword = $isPassword;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @param string|null $description
     * @return self
     */
    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getOrder(): ?int
    {
        return $this->order;
    }

    /**
     * @param int|null $order
     * @return self
     */
    public function setOrder(?int $order): self
    {
        $this->order = $order;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getServiceId(): ?int
    {
        return $this->serviceId;
    }

    /**
     * @param int|null $serviceId
     * @return self
     */
    public function setServiceId(?int $serviceId): self
    {
        $this->serviceId = $serviceId;
        return $this;
    }
}
