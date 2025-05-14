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
		// TODO: use multi-get for download
		foreach ($response['response'] as $resource => $status) {
			$cardUri = basename($resource);
			if (isset($status[200])) {
				$absoluteUrl = $this->prepareUri($url, $resource);
				$vCard = $this->download($absoluteUrl, $userName, $sharedSecret);
				$this->atomic(function () use ($addressBookId, $cardUri, $vCard): void {
					$existingCard = $this->backend->getCard($addressBookId, $cardUri);
					if ($existingCard === false) {
						$this->backend->createCard($addressBookId, $cardUri, $vCard);
					} else {
						$this->backend->updateCard($addressBookId, $cardUri, $vCard);
					}
				}, $this->dbConnection);
			} else {
				$this->backend->deleteCard($addressBookId, $cardUri);
			}
		}

		return [
			$response['token'],
			$response['truncated'],
		];
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
			$this->atomic(function () use ($addressBookId, $cardId, $user): void {
				$card = $this->backend->getCard($addressBookId, $cardId);
				if ($card === false) {
					$vCard = $this->converter->createCardFromUser($user);
					if ($vCard !== null) {
						$this->backend->createCard($addressBookId, $cardId, $vCard->serialize(), false);
					}
				} else {
					$vCard = $this->converter->createCardFromUser($user);
					if (is_null($vCard)) {
						$this->backend->deleteCard($addressBookId, $cardId);
					} else {
						$this->backend->updateCard($addressBookId, $cardId, $vCard->serialize());
					}
				}
			}, $this->dbConnection);
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
			if (!$this->userManager->userExists($uid)) {
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
}
