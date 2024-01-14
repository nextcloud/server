<?php

declare(strict_types=1);

/**
 * @copyright 2020 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
namespace OCA\ContactsInteraction\Listeners;

use OCA\ContactsInteraction\Db\CardSearchDao;
use OCA\ContactsInteraction\Db\RecentContact;
use OCA\ContactsInteraction\Db\RecentContactMapper;
use OCP\AppFramework\Db\TTransactional;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Contacts\Events\ContactInteractedWithEvent;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\IDBConnection;
use OCP\IL10N;
use OCP\IUserManager;
use Psr\Log\LoggerInterface;
use Sabre\VObject\Component\VCard;
use Sabre\VObject\UUIDUtil;

class ContactInteractionListener implements IEventListener {

	use TTransactional;

	public function __construct(
		private RecentContactMapper $mapper,
		private CardSearchDao $cardSearchDao,
		private IUserManager $userManager,
		private IDBConnection $dbConnection,
		private ITimeFactory $timeFactory,
		private IL10N $l10n,
		private LoggerInterface $logger,
	) {
	}

	public function handle(Event $event): void {
		if (!($event instanceof ContactInteractedWithEvent)) {
			return;
		}

		if ($event->getUid() === null && $event->getEmail() === null && $event->getFederatedCloudId() === null) {
			$this->logger->warning("Contact interaction event has no user identifier set");
			return;
		}

		if ($event->getUid() !== null && $event->getUid() === $event->getActor()->getUID()) {
			$this->logger->info("Ignoring contact interaction with self");
			return;
		}

		$this->atomic(function () use ($event) {
			$uid = $event->getUid();
			$email = $event->getEmail();
			$federatedCloudId = $event->getFederatedCloudId();
		
			$existingContact = $this->cardSearchDao->findExisting(
				$event->getActor(),
				$uid,
				$email,
				$federatedCloudId);
			if ($existingContact !== null) {
				return;
			}

			$existingRecentlyContacted = $this->mapper->findMatch(
				$event->getActor(),
				$uid,
				$email,
				$federatedCloudId
			);
			if (!empty($existingRecentlyContacted)) {
				$now = $this->timeFactory->getTime();
				foreach ($existingRecentlyContacted as $c) {
					$c->setLastContact($now);
					$this->mapper->update($c);
				}

				return;
			}

			$contact = new RecentContact();
			$contact->setActorUid($event->getActor()->getUID());
			if ($uid !== null) {
				$contact->setUid($uid);
			}
			if ($email !== null) {
				$contact->setEmail($email);
			}
			if ($federatedCloudId !== null) {
				$contact->setFederatedCloudId($federatedCloudId);
			}
			$contact->setLastContact($this->timeFactory->getTime());
			$contact->setCard($this->generateCard($contact));

			$this->mapper->insert($contact);
		}, $this->dbConnection);
	}

	private function getDisplayName(?string $uid): ?string {
		if ($uid === null) {
			return null;
		}
		if (($user = $this->userManager->get($uid)) === null) {
			return null;
		}

		return $user->getDisplayName();
	}

	private function generateCard(RecentContact $contact): string {
		$props = [
			'URI' => UUIDUtil::getUUID(),
			'FN' => $this->getDisplayName($contact->getUid()) ?? $contact->getEmail() ?? $contact->getFederatedCloudId(),
			'CATEGORIES' => $this->l10n->t('Recently contacted'),
		];

		if ($contact->getEmail() !== null) {
			$props['EMAIL'] = $contact->getEmail();
		}
		if ($contact->getFederatedCloudId() !== null) {
			$props['CLOUD'] = $contact->getFederatedCloudId();
		}

		return (new VCard($props))->serialize();
	}
}
