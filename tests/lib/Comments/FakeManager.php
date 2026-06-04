<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace Test\Comments;

use OC\Comments\Comment;
use OCP\Comments\IComment;
use OCP\Comments\ICommentsManager;
use OCP\IUser;

/**
 * Class FakeManager
 */
class FakeManager implements ICommentsManager {
	#[\Override]
	public function get($id): IComment {
		throw new \Exception('Not implemented');
	}

	#[\Override]
	public function getTree($id, $limit = 0, $offset = 0): array {
		return ['comment' => new Comment(), 'replies' => []];
	}

	#[\Override]
	public function getForObject(
		$objectType,
		$objectId,
		$limit = 0,
		$offset = 0,
		?\DateTime $notOlderThan = null,
	): array {
		return [];
	}

	#[\Override]
	public function getForObjectSince(
		string $objectType,
		string $objectId,
		int $lastKnownCommentId,
		string $sortDirection = 'asc',
		int $limit = 30,
		bool $includeLastKnown = false,
		string $topmostParentId = '',
	): array {
		return [];
	}

	#[\Override]
	public function getCommentsWithVerbForObjectSinceComment(
		string $objectType,
		string $objectId,
		array $verbs,
		int $lastKnownCommentId,
		string $sortDirection = 'asc',
		int $limit = 30,
		bool $includeLastKnown = false,
		string $topmostParentId = '',
	): array {
		return [];
	}

	#[\Override]
	public function getNumberOfCommentsForObject($objectType, $objectId, ?\DateTime $notOlderThan = null, $verb = ''): int {
		return 0;
	}

	#[\Override]
	public function getNumberOfCommentsForObjects(string $objectType, array $objectIds, ?\DateTime $notOlderThan = null, string $verb = ''): array {
		return array_fill_keys($objectIds, 0);
	}

	#[\Override]
	public function search(string $search, string $objectType, string $objectId, string $verb, int $offset, int $limit = 50): array {
		return [];
	}

	#[\Override]
	public function create($actorType, $actorId, $objectType, $objectId): IComment {
		return new Comment();
	}

	#[\Override]
	public function delete($id): bool {
		return false;
	}

	#[\Override]
	public function getReactionComment(int $parentId, string $actorType, string $actorId, string $reaction): IComment {
		return new Comment();
	}

	#[\Override]
	public function retrieveAllReactions(int $parentId): array {
		return [];
	}

	#[\Override]
	public function retrieveAllReactionsWithSpecificReaction(int $parentId, string $reaction): array {
		return [];
	}

	#[\Override]
	public function supportReactions(): bool {
		return false;
	}

	#[\Override]
	public function save(IComment $comment): bool {
		return false;
	}

	#[\Override]
	public function deleteReferencesOfActor($actorType, $actorId): bool {
		return false;
	}

	#[\Override]
	public function deleteCommentsAtObject($objectType, $objectId): bool {
		return false;
	}

	#[\Override]
	public function setReadMark($objectType, $objectId, \DateTime $dateTime, IUser $user): bool {
		return false;
	}

	#[\Override]
	public function getReadMark($objectType, $objectId, IUser $user): bool {
		return false;
	}

	#[\Override]
	public function deleteReadMarksFromUser(IUser $user): bool {
		return false;
	}

	#[\Override]
	public function deleteReadMarksOnObject($objectType, $objectId): bool {
		return false;
	}

	#[\Override]
	public function registerEventHandler(\Closure $closure): void {
	}

	#[\Override]
	public function registerDisplayNameResolver($type, \Closure $closure): void {
	}

	#[\Override]
	public function resolveDisplayName($type, $id): string {
		return '';
	}

	#[\Override]
	public function getNumberOfUnreadCommentsForFolder($folderId, IUser $user): array {
		return [];
	}

	#[\Override]
	public function getNumberOfUnreadCommentsForObjects(string $objectType, array $objectIds, IUser $user, $verb = ''): array {
		return [];
	}

	#[\Override]
	public function load(): void {
	}

	#[\Override]
	public function searchForObjects(string $search, string $objectType, array $objectIds, string $verb, int $offset, int $limit = 50): array {
		return [];
	}

	#[\Override]
	public function getNumberOfCommentsForObjectSinceComment(string $objectType, string $objectId, int $lastRead, string $verb = ''): int {
		return 0;
	}

	#[\Override]
	public function getNumberOfCommentsWithVerbsForObjectSinceComment(string $objectType, string $objectId, int $lastRead, array $verbs): int {
		return 0;
	}

	#[\Override]
	public function getLastCommentBeforeDate(string $objectType, string $objectId, \DateTime $beforeDate, string $verb = ''): int {
		return 0;
	}

	#[\Override]
	public function getLastCommentDateByActor(string $objectType, string $objectId, string $verb, string $actorType, array $actors): array {
		return [];
	}

	#[\Override]
	public function deleteCommentsExpiredAtObject(string $objectType, string $objectId = ''): bool {
		return true;
	}
}
