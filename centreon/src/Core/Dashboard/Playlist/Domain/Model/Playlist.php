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
use Core\Dashboard\Playlist\Application\Exception\PlaylistException;

class Playlist
{
    public const NAME_MIN_LENGTH = 1;
    public const NAME_MAX_LENGTH = 255;
    public const DESCRIPTION_MIN_LENGTH = 1;
    public const DESCRIPTION_MAX_LENGTH = 65535;
    public const MINIMUM_ROTATION_TIME = 10; // time in seconds
    public const MAXIMUM_ROTATION_TIME = 60; // time in seconds

    /** @var DashboardOrder[] */
    private array $dashboardsOrder = [];

    private ?string $description = null;

    private ?int $authorId = null;

    private array $dashboardIds = [];

    public function __construct(
        private readonly int $id,
        private readonly string $name,
        private readonly int $rotationTime,
        private readonly bool $isPublic,
        private readonly \DateTimeImmutable $createdAt
    ) {
        Assertion::minLength($name, self::NAME_MIN_LENGTH, 'NewPlaylist::name');
        Assertion::maxLength($name, self::NAME_MAX_LENGTH, 'NewPlaylist::name');
        Assertion::range(
            $rotationTime,
            self::MINIMUM_ROTATION_TIME,
            self::MAXIMUM_ROTATION_TIME,
            'NewPlaylist::rotationTime'
        );
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDashboardIds(): array
    {
        return $this->dashboardIds;
    }

    public function getRotationTime(): int
    {
        return $this->rotationTime;
    }


    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * @param DashboardOrder[] $dashboardsOrder
     *
     * @throws NewPlaylistException
     *
     * @return self
     */
    public function setDashboardsOrder(array $dashboardsOrder): self
    {
        $this->dashboardsOrder = [];

        foreach ($dashboardsOrder as $dashboardOrder) {
            $this->addDashboardsOrder($dashboardOrder);
        }

        return $this;
    }

    /**
     * @param DashboardOrder $dashboardOrder
     *
     * @throws NewPlaylistException
     *
     * @return self
     */
    public function addDashboardsOrder(DashboardOrder $dashboardOrder): self
    {
        $this->validateDashboardOrder($dashboardOrder);
        $this->dashboardsOrder[] = $dashboardOrder;

        return $this;
    }

    /**
     * @return DashboardOrder[]
     */
    public function getDashboardsOrder(): array
    {
        return $this->dashboardsOrder;
    }

    /**
     * @param string|null $description
     *
     * @throws \Assert\AssertionFailedException
     *
     * @return self
     */
    public function setDescription(?string $description): self
    {
        if (is_string($description)) {
            Assertion::minLength($description, self::DESCRIPTION_MIN_LENGTH, 'Playlist::description');
            Assertion::maxLength($description, self::DESCRIPTION_MAX_LENGTH, 'Playlist::description');
        }

        $this->description = $description;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function isPublic(): bool
    {
        return $this->isPublic;
    }

    public function setAuthor(?int $authorId): self
    {
        $this->authorId = $authorId;

        return $this;
    }

    public function getAuthorId(): ?int
    {
        return $this->authorId;
    }

    /**
     * @param DashboardOrder $dashboardOrder
     *
     * @throws NewPlaylistException
     */
    private function validateDashboardOrder(DashboardOrder $dashboardOrder): void
    {
        foreach ($this->dashboardsOrder as $existingDashboardOrder) {
            if ($existingDashboardOrder->getOrder() === $dashboardOrder->getOrder()) {
                throw PlaylistException::orderMustBeUnique();
            }
        }
    }
}