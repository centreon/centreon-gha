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

namespace Core\User\Application\Exception;

class UserException extends \Exception
{
    /**
     * @param \Throwable $ex
     *
     * @return self
     */
    public static function errorWhileSearchingForUser(\Throwable $ex): self
    {
        return new self(
            _('Error while searching for the user'),
            0,
            $ex
        );
    }

    /**
     * @return self
     */
    public static function accessNotAllowed(): self
    {
        return new self(_('You are not allowed to access users'));
    }

    /**
     * @param \Throwable $ex
     *
     * @return self
     */
    public static function errorWhileSearching(\Throwable $ex): self
    {
        return new self(_('Error while searching for users'), 0, $ex);
    }
}
