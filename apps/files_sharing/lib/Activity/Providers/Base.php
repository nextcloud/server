<?php
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Files_Sharing\Activity\Providers;

use OCP\Activity\Exceptions\UnknownActivityException;
use OCP\Activity\IEvent;
use OCP\Activity\IEventMerger;
use OCP\Activity\IManager;
use OCP\Activity\IProvider;
use OCP\Contacts\IManager as IContactsManager;
use OCP\Federation\ICloudIdManager;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\IUserManager;
use OCP\L10N\IFactory;

abstract class Base implements IProvider {
	/** @var IL10N */
	protected $l;

	/** @var array */
	protected $displayNames = [];

	public function __construct(
		protected IFactory $languageFactory,
		protected IURLGenerator $url,
		protected IManager $activityManager,
		protected IUserManager $userManager,
		protected ICloudIdManager $cloudIdManager,
		protected IContactsManager $contactsManager,
		protected IEventMerger $eventMerger,
	) {
	}

	/**
	 * @param string $language
	 * @param IEvent $event
	 * @param IEvent|null $previousEvent
	 * @return IEvent
	 * @throws UnknownActivityException
	 * @since 11.0.0
	 */
	public function parse($language, IEvent $event, ?IEvent $previousEvent = null) {
		if ($event->getApp() !== 'files_sharing') {
			throw new UnknownActivityException();
		}

		$this->l = $this->languageFactory->get('files_sharing', $language);

		if ($this->activityManager->isFormattingFilteredObject()) {
			try {
				return $this->parseShortVersion($event);
			} catch (\InvalidArgumentException $e) {
				// Ignore and simply use the long version...
			}
		}

		return $this->parseLongVersion($event, $previousEvent);
	}

	/**
	 * @param IEvent $event
	 * @return IEvent
	 * @throws \InvalidArgumentException
	 * @since 11.0.0
	 */
	abstract protected function parseShortVersion(IEvent $event);

	/**
	 * @param IEvent $event
	 * @param IEvent|null $previousEvent
	 * @return IEvent
	 * @throws \InvalidArgumentException
	 * @since 11.0.0
	 */
	abstract protected function parseLongVersion(IEvent $event, ?IEvent $previousEvent = null);

	/**
	 * @throws \InvalidArgumentException
	 */
	protected function setSubjects(IEvent $event, string $subject, array $parameters): void {
		$event->setRichSubject($subject, $parameters);
	}

	/**
	 * @param array|string $parameter
	 * @param IEvent|null $event
	 * @return array
	 * @throws \InvalidArgumentException
	 */
	protected function getFile($parameter, ?IEvent $event = null) {
		if (is_array($parameter)) {
			$path = reset($parameter);
			$id = (string)key($parameter);
		} elseif ($event !== null) {
			$path = $parameter;
			$id = (string)$event->getObjectId();
		} else {
			throw new \InvalidArgumentException('Could not generate file parameter');
		}

		return [
			'type' => 'file',
			'id' => $id,
			'name' => basename($path),
			'path' => trim($path, '/'),
			'link' => $this->url->linkToRouteAbsolute('files.viewcontroller.showFile', ['fileid' => $id]),
		];
	}

	/**
	 * @param string $uid
	 * @param string $overwriteDisplayName - overwrite display name, only if user is not local
	 *
	 * @return array
	 */
	protected function getUser(string $uid, string $overwriteDisplayName = '') {
		// First try local user
		$displayName = $this->userManager->getDisplayName($uid);
		if ($displayName !== null) {
			return [
				'type' => 'user',
				'id' => $uid,
				'name' => $displayName,
			];
		}

		// Then a contact from the addressbook
		if ($this->cloudIdManager->isValidCloudId($uid)) {
			$cloudId = $this->cloudIdManager->resolveCloudId($uid);
			return [
				'type' => 'user',
				'id' => $cloudId->getUser(),
				'name' => (($overwriteDisplayName !== '') ? $overwriteDisplayName : $this->getDisplayNameFromAddressBook($cloudId->getDisplayId())),
				'server' => $cloudId->getRemote(),
			];
		}

		// Fallback to empty dummy data
		return [
			'type' => 'user',
			'id' => $uid,
			'name' => (($overwriteDisplayName !== '') ? $overwriteDisplayName : $uid),
		];
	}

	protected function getDisplayNameFromAddressBook(string $search): string {
		if (isset($this->displayNames[$search])) {
			return $this->displayNames[$search];
		}

		$addressBookContacts = $this->contactsManager->search($search, ['CLOUD'], [
			'limit' => 1,
			'enumeration' => false,
			'fullmatch' => false,
			'strict_search' => true,
		]);
		foreach ($addressBookContacts as $contact) {
			if (isset($contact['isLocalSystemBook'])) {
				continue;
			}

			if (isset($contact['CLOUD'])) {
				$cloudIds = $contact['CLOUD'];
				if (is_string($cloudIds)) {
					$cloudIds = [$cloudIds];
				}

				$lowerSearch = strtolower($search);
				foreach ($cloudIds as $cloudId) {
					if (strtolower($cloudId) === $lowerSearch) {
						$this->displayNames[$search] = $contact['FN'] . " ($cloudId)";
						return $this->displayNames[$search];
					}
				}
			}
		}

		return $search;
	}
}
