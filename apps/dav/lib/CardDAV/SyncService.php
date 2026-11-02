<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\DAV\CardDAV;

use OCA\DAV\Service\ASyncService;
use OCP\AppFramework\Db\TTransactional;
use OCP\AppFramework\Http;
use OCP\DB\Exception;
use OCP\Http\Client\IClientService;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\IUser;
use OCP\IUserManager;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Log\LoggerInterface;
use Sabre\VObject\Reader;
use function is_null;

class SyncService extends ASyncService {

	use TTransactional;
	private ?array $localSystemAddressBook = null;
	protected string $certPath;

	public function __construct(
		IClientService $clientService,
		IConfig $config,
		private CardDavBackend $backend,
		private IUserManager $userManager,
		private IDBConnection $dbConnection,
		private LoggerInterface $logger,
		private Converter $converter,
	) {
		parent::__construct($clientService, $config);

		$this->certPath = '';
	}

	/**
	 * @psalm-return list{0: ?string, 1: boolean}
	 * @throws \Exception
	 */
	public function syncRemoteAddressBook(string $url, string $userName, string $addressBookUrl, string $sharedSecret, ?string $syncToken, string $targetBookHash, string $targetPrincipal, array $targetProperties): array {
		// 1. create addressbook
		$book = $this->ensureSystemAddressBookExists($targetPrincipal, $targetBookHash, $targetProperties);
		$addressBookId = $book['id'];

		// 2. query changes
		try {
			$absoluteUri = $this->prepareUri($url, $addressBookUrl);
			$response = $this->requestSyncReport($absoluteUri, $userName, $sharedSecret, $syncToken);
		} catch (ClientExceptionInterface $ex) {
			if ($ex->getCode() === Http::STATUS_UNAUTHORIZED) {
				// remote server revoked access to the address book, remove it
				$this->backend->deleteAddressBook($addressBookId);
				$this->logger->error('Authorization failed, remove address book: ' . $url, ['app' => 'dav']);
				throw $ex;
			}
			$this->logger->error('Client exception:', ['app' => 'dav', 'exception' => $ex]);
			throw $ex;
		}

		// 3. apply changes
		$changedResources = [];
		foreach ($response['response'] as $resource => $status) {
			if (isset($status[200])) {
				$changedResources[] = $resource;
			} else {
				$this->backend->deleteCard($addressBookId, basename($resource));
			}
		}

		if ($changedResources !== []) {
			foreach ($this->fetchCards($url, $absoluteUri, $userName, $sharedSecret, $changedResources) as $resource => $vCard) {
				$cardUri = basename($resource);
				$existingCard = $this->backend->getCard($addressBookId, $cardUri);
				if ($existingCard === false) {
					$this->backend->createCard($addressBookId, $cardUri, $vCard);
				} else {
					$this->backend->updateCard($addressBookId, $cardUri, $vCard);
				}
			}
		}

		return [
			$response['token'],
			$response['truncated'],
		];
	}

	/**
	 * Download the vCards of the given resources, using addressbook-multiget when the remote supports it.
	 *
	 * @param list<string> $hrefs
	 * @return \Generator<string, string> vCard data keyed by href
	 */
	private function fetchCards(string $url, string $addressBookUrl, string $userName, string $sharedSecret, array $hrefs): \Generator {
		$reportName = '{' . Plugin::NS_CARDDAV . '}addressbook-multiget';
		if (!$this->requestReportSupport($addressBookUrl, $userName, $sharedSecret, $reportName)) {
			foreach ($hrefs as $href) {
				yield $href => $this->requestGet($this->prepareUri($url, $href), $userName, $sharedSecret);
			}
			return;
		}

		foreach (array_chunk($hrefs, self::MULTIGET_BATCH_SIZE) as $batch) {
			yield from $this->requestMultiGet(
				$addressBookUrl,
				$userName,
				$sharedSecret,
				$batch,
				$reportName,
				'{' . Plugin::NS_CARDDAV . '}address-data',
			);
		}
	}

	/**
	 * @throws \Sabre\DAV\Exception\BadRequest
	 */
	public function ensureSystemAddressBookExists(string $principal, string $uri, array $properties): ?array {
		try {
			return $this->atomic(function () use ($principal, $uri, $properties) {
				$book = $this->backend->getAddressBooksByUri($principal, $uri);
				if (!is_null($book)) {
					return $book;
				}
				$this->backend->createAddressBook($principal, $uri, $properties);

				return $this->backend->getAddressBooksByUri($principal, $uri);
			}, $this->dbConnection);
		} catch (Exception $e) {
			// READ COMMITTED doesn't prevent a nonrepeatable read above, so
			// two processes might create an address book here. Ignore our
			// failure and continue loading the entry written by the other process
			if ($e->getReason() !== Exception::REASON_UNIQUE_CONSTRAINT_VIOLATION) {
				throw $e;
			}

			// If this fails we might have hit a replication node that does not
			// have the row written in the other process.
			// TODO: find an elegant way to handle this
			$ab = $this->backend->getAddressBooksByUri($principal, $uri);
			if ($ab === null) {
				throw new Exception('Could not create system address book', $e->getCode(), $e);
			}
			return $ab;
		}
	}

	public function ensureLocalSystemAddressBookExists(): ?array {
		return $this->ensureSystemAddressBookExists('principals/system/system', 'system', [
			'{' . Plugin::NS_CARDDAV . '}addressbook-description' => 'System addressbook which holds all users of this instance'
		]);
	}

	/**
	 * @param IUser $user
	 */
	public function updateUser(IUser $user): void {
		$systemAddressBook = $this->getLocalSystemAddressBook();
		$addressBookId = $systemAddressBook['id'];

		$cardId = self::getCardUri($user);
		if ($user->isEnabled()) {
			$vCard = $this->converter->createCardFromUser($user);
			$card = $this->backend->getCard($addressBookId, $cardId);
			if ($card === false) {
				if ($vCard !== null) {
					$this->backend->createCard($addressBookId, $cardId, $vCard->serialize(), false);
				}
			} else {
				if (is_null($vCard)) {
					$this->backend->deleteCard($addressBookId, $cardId);
				} else {
					$cardData = $vCard->serialize();
					// Writing an identical card would still bump the address book
					// sync token and make every client re-download the card
					if ($card['carddata'] !== $cardData) {
						$this->backend->updateCard($addressBookId, $cardId, $cardData);
					}
				}
			}
		} else {
			$this->backend->deleteCard($addressBookId, $cardId);
		}
	}

	/**
	 * @param IUser|string $userOrCardId
	 */
	public function deleteUser($userOrCardId) {
		$systemAddressBook = $this->getLocalSystemAddressBook();
		if ($userOrCardId instanceof IUser) {
			$userOrCardId = self::getCardUri($userOrCardId);
		}
		$this->backend->deleteCard($systemAddressBook['id'], $userOrCardId);
	}

	/**
	 * @return array|null
	 */
	public function getLocalSystemAddressBook() {
		if (is_null($this->localSystemAddressBook)) {
			$this->localSystemAddressBook = $this->ensureLocalSystemAddressBookExists();
		}

		return $this->localSystemAddressBook;
	}

	/**
	 * @return void
	 */
	public function syncInstance(?\Closure $progressCallback = null) {
		$systemAddressBook = $this->getLocalSystemAddressBook();
		$this->userManager->callForAllUsers(function ($user) use ($systemAddressBook, $progressCallback): void {
			$this->updateUser($user);
			if (!is_null($progressCallback)) {
				$progressCallback();
			}
		});

		// remove no longer existing
		$allCards = $this->backend->getCards($systemAddressBook['id']);
		foreach ($allCards as $card) {
			$vCard = Reader::read($card['carddata']);
			$uid = $vCard->UID->getValue();
			// load backend and see if user exists
			$user = $this->userManager->get($uid);

			// If the user does not exist
			if ($user === null || self::getCardUri($user) !== $card['uri']) {
				$this->deleteUser($card['uri']);
			}
		}
	}

	/**
	 * @param IUser $user
	 * @return string
	 */
	public static function getCardUri(IUser $user): string {
		return $user->getBackendClassName() . ':' . $user->getUID() . '.vcf';
	}

	public function markCardsAsPending(int $addressBookId): void {
		$this->backend->markCardsAsPending($addressBookId);
	}

	public function deletePendingCards(int $addressBookId): void {
		$cards = $this->backend->getPendingCards($addressBookId);
		foreach ($cards as $card) {
			$this->backend->deleteCard($addressBookId, $card['uri']);
		}
	}
}
