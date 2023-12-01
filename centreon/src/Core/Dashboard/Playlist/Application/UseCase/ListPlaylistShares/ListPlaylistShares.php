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

namespace Core\Dashboard\Playlist\Application\UseCase\ListPlaylistShares;

use Centreon\Domain\Contact\Interfaces\ContactInterface;
use Centreon\Domain\Log\LoggerTrait;
use Core\Application\Common\UseCase\ForbiddenResponse;
use Core\Application\Common\UseCase\InvalidArgumentResponse;
use Core\Application\Common\UseCase\NotFoundResponse;
use Core\Contact\Application\Repository\ReadContactGroupRepositoryInterface;
use Core\Contact\Application\Repository\ReadContactRepositoryInterface;
use Core\Contact\Domain\Model\ContactGroup;
use Core\Dashboard\Domain\Model\DashboardRights;
use Core\Dashboard\Playlist\Application\Exception\PlaylistException;
use Core\Dashboard\Playlist\Application\Repository\ReadPlaylistRepositoryInterface;
use Core\Dashboard\Playlist\Application\Repository\ReadPlaylistShareRepositoryInterface;
use Core\Dashboard\Playlist\Domain\Model\PlaylistContactGroupShare;
use Core\Dashboard\Playlist\Domain\Model\PlaylistContactShare;
use Core\Dashboard\Playlist\Domain\Model\PlaylistShare;

final class ListPlaylistShares
{
    use LoggerTrait;

    public function __construct(
        private readonly DashboardRights $rights,
        private readonly ContactInterface $user,
        private readonly ReadPlaylistRepositoryInterface $readPlaylistRepository,
        private readonly ReadPlaylistShareRepositoryInterface $readPlaylistShareRepository,
        private readonly ReadContactGroupRepositoryInterface $readContactGroupRepository,
        private readonly ReadContactRepositoryInterface $readContactRepository
    ) {}

    public function __invoke(int $playlistId, ListPlaylistSharesPresenterInterface $presenter): void
    {
        try {
            if (! $this->rights->canAccess()) {
                $this->error('User does not have sufficient rigths to list playlist shares');
                $presenter->presentResponse(new ForbiddenResponse(
                    PlaylistException::accessNotAllowed()->getMessage()
                ));

                return;
            }

            if (! $this->readPlaylistRepository->exists($playlistId)) {
                $this->error('Playlist not found', ['playlist_id' => $playlistId]);
                $presenter->presentResponse(new NotFoundResponse('Playlist'));

                return;
            }

            if (
                ! $this->rights->hasAdminRole()
                && ! $this->readPlaylistShareRepository->exists($playlistId, $this->user)
            ) {
                $this->error('Playlist not shared with the user', ['contact_id' => $this->user->getId()]);
                $presenter->presentResponse(
                    new InvalidArgumentResponse(PlaylistException::playlistNotShared($playlistId))
                );
            }

            $shares = $this->findPlaylistShares($playlistId);

            $presenter->presentResponse($this->createResponse($shares));
        } catch (\Throwable $ex) {

        }
    }

    private function findPlaylistShares(int $playlistId): PlaylistShare {
        if ($this->rights->hasAdminRole()) {
            return $this->readPlaylistShareRepository->findByPlaylistId($playlistId);
        } else {
            $userContactGroups = $this->readContactGroupRepository->findAllByUserId($this->user->getId());
            $userContactGroupIds = array_map(
                fn (ContactGroup $contactGroup): int => $contactGroup->getId(), $userContactGroups
            );
            return $this->readPlaylistShareRepository->findByPlaylistIdAndContactGroupIds(
                $playlistId,
                $userContactGroupIds
            );
        }
    }

    private function createResponse(PlaylistShare $shares): ListPlaylistSharesResponse
    {
        $response = new ListPlaylistSharesResponse();
        $response->contacts = array_map(function (PlaylistContactShare $contactShare): array {
            return [
                'id' => $contactShare->getContactId(),
                'name' => $contactShare->getContactName(),
                'role' => $contactShare->getRole()
            ];
        }, $shares->getPlaylistContactShares());
        $response->contactGroups = array_map(function (PlaylistContactGroupShare $contactShare): array {
            return [
                'id' => $contactShare->getContactGroupId(),
                'name' => $contactShare->getContactGroupName(),
                'role' => $contactShare->getRole()
            ];
        }, $shares->getPlaylistContactGroupShares());

        return $response;
    }
}