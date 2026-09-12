<?php

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace NCU\ResourceRight;

use InvalidArgumentException;
use NCU\Actor\IActor;
use NCU\Resource\IResource;
use OCP\AppFramework\Attribute\Dispatchable;
use OCP\AppFramework\Attribute\Listenable;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Server;

/**
 * Asks whether rights are currently allowed on resources, for an actor.
 *
 * Default allow: a (resource, right) pair is restricted only if a listener
 * calls {@see self::restrict()} for it. Deny wins, and the first restriction
 * recorded for a pair is the one reported.
 *
 * One batch covers one resource type, so a listener resolves it with a single
 * bulk lookup, and skips a batch it has no opinion on by comparing
 * {@see $resourceType}.
 *
 * Listeners MUST NOT throw: an exception aborts the dispatch for every other
 * listener and every other pair in the batch. Call {@see self::restrict()} instead.
 *
 * @experimental 35.0.0
 */
#[Listenable(since: '35.0.0')]
#[Dispatchable(since: '35.0.0')]
final class RestrictRightEvent extends Event {
	/**
	 * @var array<string, array<class-string<IRight>, string>> resource id => (right class => hint); first restriction for a given pair wins
	 */
	private array $restrictions = [];
	private bool $dispatched = false;

	/**
	 * @var array<string, true>
	 */
	private array $resourceIdIndex = [];

	/**
	 * @var array<class-string<IRight>, true>
	 */
	private array $rightIndex = [];

	/**
	 * @var non-empty-list<string>
	 */
	public readonly array $resourceIds;

	/**
	 * @var non-empty-list<class-string<IRight>>
	 */
	public readonly array $rights;

	/**
	 * @param class-string<IResource> $resourceType
	 * @psalm-param array<array-key, mixed> $resourceIds
	 * @param non-empty-list<string> $resourceIds ids of that type
	 * @psalm-param array<array-key, mixed> $rights
	 * @param non-empty-list<class-string<IRight>> $rights
	 * @throws InvalidArgumentException on an empty batch
	 * @experimental 35.0.0
	 */
	public function __construct(
		public readonly IActor $actor,
		public readonly string $resourceType,
		array $resourceIds,
		array $rights,
	) {
		parent::__construct();

		$uniqueResourceIds = [];
		foreach ($resourceIds as $resourceId) {
			if (!is_string($resourceId)) {
				throw new InvalidArgumentException('resourceId must be a string');
			}

			$resourceId = trim($resourceId);
			if ($resourceId === '' || isset($this->resourceIdIndex[$resourceId])) {
				continue;
			}

			$uniqueResourceIds[] = $resourceId;
			$this->resourceIdIndex[$resourceId] = true;
		}

		$uniqueRights = [];
		foreach ($rights as $right) {
			if (!is_string($right) || !is_a($right, IRight::class, true)) {
				throw new InvalidArgumentException('Right should be a class-string<IRight>');
			}

			if (isset($this->rightIndex[$right])) {
				continue;
			}

			$uniqueRights[] = $right;
			$this->rightIndex[$right] = true;
		}

		if ($resourceIds === [] || $rights === []) {
			throw new InvalidArgumentException('A RestrictRightEvent needs at least one resource and one right');
		}

		/** @var non-empty-list<string> $uniqueResourceIds */
		$this->resourceIds = $uniqueResourceIds;
		/** @var non-empty-list<class-string<IRight>> $uniqueRights */
		$this->rights = $uniqueRights;
	}

	/**
	 * Listener-facing: flag one (resource, right) pair as restricted.
	 *
	 * @param class-string<IRight> $right
	 * @throws InvalidArgumentException when $resourceId or $right isn't part of this event
	 * @experimental 35.0.0
	 */
	public function restrict(string $resourceId, string $right, string $hint): void {
		if (!isset($this->resourceIdIndex[$resourceId])) {
			throw new InvalidArgumentException('restrict() called for a resource not in this event: ' . $resourceId);
		}
		if (!isset($this->rightIndex[$right])) {
			throw new InvalidArgumentException('restrict() called for a right not in this event: ' . $right);
		}

		$this->restrictions[$resourceId][$right] ??= $hint;
	}

	/**
	 * Dispatches once. Every registered listener is consulted, for every
	 * resource and every right in the batch.
	 *
	 * @return array<string, array<class-string<IRight>, string>> resource id => (right class => hint),
	 *                                                            restricted pairs only - an id absent entirely, or missing a given right, is allowed.
	 * @experimental 35.0.0
	 */
	public function queryRestrictions(): array {
		if (!$this->dispatched) {
			$this->dispatched = true;
			Server::get(IEventDispatcher::class)->dispatchTyped($this);
		}

		return $this->restrictions;
	}

	/**
	 * The verdict for one (resource, right) pair of the batch.
	 *
	 * @param class-string<IRight> $right
	 * @return false|string a user-friendly restriction reason, or false if allowed
	 * @experimental 35.0.0
	 */
	public function isRestrictedFor(string $resourceId, string $right): false|string {
		return $this->queryRestrictions()[$resourceId][$right] ?? false;
	}

	/**
	 * Convenience for the common single-resource, single-right call site.
	 *
	 * @return false|string a user-friendly restriction reason, or false if allowed
	 * @experimental 35.0.0
	 */
	public function isRestricted(): false|string {
		return $this->isRestrictedFor($this->resourceIds[0], $this->rights[0]);
	}

	/**
	 * @return false|string the first restriction found anywhere in the batch, or false if nothing is restricted
	 * @experimental 35.0.0
	 */
	public function isAnyRestricted(): false|string {
		foreach ($this->queryRestrictions() as $byRight) {
			foreach ($byRight as $hint) {
				return $hint;
			}
		}

		return false;
	}
}
