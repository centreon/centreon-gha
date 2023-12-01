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

namespace Core\Dashboard\Playlist\Domain\Model;

use Centreon\Domain\Common\Assertion\Assertion;

class PlaylistContactShare
{
    public const PLAYLIST_VIEWER_ROLE = 'viewer';
    public const PLAYLIST_EDITOR_ROLE = 'editor';
    public const PLAYLIST_ROLES = [self::PLAYLIST_VIEWER_ROLE, self::PLAYLIST_EDITOR_ROLE];

    /**
     * @param int $playlistId
     * @param int $contactId
     * @param string $contactName
     * @param string $role
     *
     * @throws \Assert\AssertionFailedException
     */
    public function __construct(
        private readonly int $playlistId,
        private readonly int $contactId,
        private readonly string $contactName,
        private readonly string $role,
    ) {
        Assertion::positiveInt($playlistId, 'PlaylistContactShare::playlistId');
        Assertion::positiveInt($contactId, 'PlaylistContactShare::contactId');
        Assertion::notEmptyString($contactName, 'PlaylistContactShare::contactName');
        Assertion::inArray($role, self::PLAYLIST_ROLES, 'PlaylistContactShare::role');
    }

    public function getPlaylistId(): int
    {
        return $this->playlistId;
    }

    public function getContactId(): int
    {
        return $this->contactId;
    }

    public function getContactName(): string
    {
        return $this->contactName;
    }

    public function getRole(): string
    {
        return $this->role;
    }
}