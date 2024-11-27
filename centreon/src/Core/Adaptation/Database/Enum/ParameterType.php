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

namespace Core\Adaptation\Database\Enum;

/**
 * Enum
 *
 * @class ParameterType
 * @package Core\Adaptation\Database
 */
enum ParameterType: int
{
    /**
     * Represents the SQL NULL data type.
     */
    case NULL = 0;
    /**
     * Represents the SQL INTEGER data type.
     */
    case INT = 1;
    /**
     * Represents the SQL CHAR, VARCHAR, or other string data type.
     */
    case STRING = 2;
    /**
     * Represents the SQL large object data type.
     */
    case LARGE_OBJECT = 3;
    /**
     * Represents a boolean data type.
     */
    case BOOL = 5;
    /**
     * Represents a binary string data type.
     */
    case BINARY = 16;
    /**
     * Represents an ASCII string data type
     */
    case ASCII = 17;
}
