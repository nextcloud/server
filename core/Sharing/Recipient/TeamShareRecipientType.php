<?php

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OC\Core\Sharing\Recipient;

use Exception;
use OC\Core\AppInfo\Application;
use OCA\Circles\Events\DestroyingCircleEvent;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\EventDispatcher\IEventListener;
use OCP\IDBConnection;
use OCP\Interaction\InteractionReceiver;
use OCP\Interaction\Receivers\CircleReceiver;
use OCP\IUser;
use OCP\L10N\IFactory;
use OCP\Server;
use OCP\Share\IShare;
use OCP\Sharing\Icon\ShareIconSVG;
use OCP\Sharing\Icon\ShareIconURL;
use OCP\Sharing\ISharingManager;
use OCP\Sharing\Recipient\AShareRecipientTypeSearchCollaborator;
use OCP\Sharing\Recipient\ShareRecipient;
use OCP\Sharing\ShareAccessContext;
use OCP\Teams\ITeamManager;
use OCP\Teams\Team;

/**
 * @template-implements IEventListener<DestroyingCircleEvent>
 */
final class TeamShareRecipientType extends AShareRecipientTypeSearchCollaborator implements IEventListener {
	private ?ITeamManager $teamManager = null;

	public function __construct(
		IEventDispatcher $eventDispatcher,
		private readonly IDBConnection $dbConnection,
		private readonly ISharingManager $manager,
	) {
		$eventDispatcher->addServiceListener(DestroyingCircleEvent::class, self::class);
	}

	private function getTeamManager(): ITeamManager {
		return $this->teamManager ??= Server::get(ITeamManager::class);
	}

	#[\Override]
	public function getDisplayName(IFactory $l10nFactory): string {
		return $l10nFactory->get(Application::APP_ID)->t('Team');
	}

	#[\Override]
	public function validateRecipient(string $recipient): bool {
		return $this->getTeamManager()->getTeam($recipient) instanceof Team;
	}

	#[\Override]
	public function getRecipients(?IUser $currentUser, mixed $arguments): array {
		if (!$currentUser instanceof IUser) {
			return [];
		}

		return array_map(static fn (Team $team): string => $team->getId(), $this->getTeamManager()->getTeamsForUser($currentUser->getUID()));
	}

	#[\Override]
	public function getRecipientDisplayName(string $recipient): ?string {
		return $this->getTeamManager()->getTeam($recipient)?->getDisplayName();
	}

	#[\Override]
	public function getRecipientIcon(string $recipient): null|ShareIconSVG|ShareIconURL {
		return null;
	}

	#[\Override]
	public function getRecipientInteractionReceiver(string $recipient): InteractionReceiver {
		return new CircleReceiver($recipient);
	}

	#[\Override]
	public function getCollaboratorType(): int {
		return IShare::TYPE_CIRCLE;
	}

	#[\Override]
	public function getCollaboratorKey(): string {
		return 'circles';
	}

	#[\Override]
	public function handle(Event $event): void {
		try {
			$this->dbConnection->beginTransaction();
			$this->manager->onRecipientDeleted(new ShareAccessContext(overrideChecks: true), new ShareRecipient(self::class, $event->getCircle()->getSingleId(), null));
			$this->dbConnection->commit();
		} catch (Exception $exception) {
			$this->dbConnection->rollBack();
			throw $exception;
		}
	}
}
