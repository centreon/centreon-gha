<?php

/*
 * Copyright 2005 - 2023 Centreon (https://www.centreon.com/)
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

namespace Core\Security\ProviderConfiguration\Application\Local\UseCase\UpdateConfiguration;

class UpdateConfigurationRequest
{
    /** @var int */
    public int $passwordMinimumLength;

    /** @var bool */
    public bool $hasUppercase;

    /** @var bool */
    public bool $hasLowercase;

    /** @var bool */
    public bool $hasNumber;

    /** @var bool */
    public bool $hasSpecialCharacter;

    /** @var bool */
    public bool $canReusePasswords;

    /** @var int|null */
    public ?int $attempts;

    /** @var int|null */
    public ?int $blockingDuration;

    /** @var int|null */
    public ?int $passwordExpirationDelay;

    /** @var string[] */
    public array $passwordExpirationExcludedUserAliases;

    /** @var int|null */
    public ?int $delayBeforeNewPassword;
}
