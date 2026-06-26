<?php

/**
 * SPDX-FileCopyrightText: Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Talk\Share\Helper;

/**
 * Helper of OCA\Files_Sharing\Controller\ShareAPIController for room shares.
 *
 * The methods of this class are called from the ShareAPIController to perform
 * actions or checks specific to room shares.
 */
class ShareAPIController
{
    public function __construct(protected string $userId, protected \OCA\Talk\Manager $manager, protected \OCA\Talk\Service\ParticipantService $participantService, protected \OCP\AppFramework\Utility\ITimeFactory $timeFactory, protected \OCP\IL10N $l, protected \OCP\IURLGenerator $urlGenerator)
    {
    }
    /**
     * Formats the specific fields of a room share for OCS output.
     *
     * The returned fields override those set by the main ShareAPIController.
     */
    public function formatShare(\OCP\Share\IShare $share): array
    {
    }
    /**
     * Prepares the given share to be passed to OC\Share20\Manager::createShare.
     *
     * @throws OCSNotFoundException
     */
    public function createShare(\OCP\Share\IShare $share, string $shareWith, int $permissions, string $expireDate): void
    {
    }
    /**
     * Returns whether the given user can access the given room share or not.
     *
     * A user can access a room share only if they are a participant of the room.
     */
    public function canAccessShare(\OCP\Share\IShare $share, string $user): bool
    {
    }
}
