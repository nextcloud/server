<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Arne Hamann <kontakt+github@arne.email>
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Björn Schießle <bjoern@schiessle.org>
 * @author Chih-Hsuan Yen <yan12125@gmail.com>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Georg Ehrke <oc.list@georgehrke.com>
 * @author Joas Schilling <coding@schilljs.com>
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 * @author matt <34400929+call-me-matt@users.noreply.github.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Stefan Weil <sw@weilnetz.de>
 * @author Thomas Citharel <nextcloud@tcit.fr>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OCA\DAV\CardDAV;

use OCA\DAV\Connector\Sabre\Principal;
use OCA\DAV\DAV\Sharing\Backend;
use OCA\DAV\DAV\Sharing\IShareable;
use OCA\DAV\Events\AddressBookCreatedEvent;
use OCA\DAV\Events\AddressBookDeletedEvent;
use OCA\DAV\Events\AddressBookShareUpdatedEvent;
use OCA\DAV\Events\AddressBookUpdatedEvent;
use OCA\DAV\Events\CardCreatedEvent;
use OCA\DAV\Events\CardDeletedEvent;
use OCA\DAV\Events\CardMovedEvent;
use OCA\DAV\Events\CardUpdatedEvent;
use OCP\AppFramework\Db\TTransactional;
use OCP\DB\Exception;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IDBConnection;
use OCP\IGroupManager;
use OCP\IUserManager;
use OC\Search\Filter\DateTimeFilter;
use PDO;
use Sabre\CardDAV\Backend\BackendInterface;
use Sabre\CardDAV\Backend\SyncSupport;
use Sabre\CardDAV\Plugin;
use Sabre\DAV\Exception\BadRequest;
use Sabre\VObject\Component\VCard;
use Sabre\VObject\Reader;

class CardDavBackend implements BackendInterface, SyncSupport {
	use TTransactional;

	public const PERSONAL_ADDRESSBOOK_URI = 'contacts';
	public const PERSONAL_ADDRESSBOOK_NAME = 'Contacts';

	private Principal $principalBackend;
	private string $dbCardsTable = 'cards';
	private string $dbCardsPropertiesTable = 'cards_properties';
	private IDBConnection $db;
	private Backend $sharingBackend;

	/** @var array properties to index */
	public static array $indexProperties = [
		'BDAY', 'UID', 'N', 'FN', 'TITLE', 'ROLE', 'NOTE', 'NICKNAME',
		'ORG', 'CATEGORIES', 'EMAIL', 'TEL', 'IMPP', 'ADR', 'URL', 'GEO',
		'CLOUD', 'X-SOCIALPROFILE'];

	/**
	 * @var string[] Map of uid => display name
	 */
	protected array $userDisplayNames;
	private IUserManager $userManager;
	private IEventDispatcher $dispatcher;
	private array $etagCache = [];

	/**
	 * CardDavBackend constructor.
	 *
	 * @param IDBConnection $db
	 * @param Principal $principalBackend
	 * @param IUserManager $userManager
	 * @param IGroupManager $groupManager
	 * @param IEventDispatcher $dispatcher
	 */
	public function __construct(IDBConnection $db,
								Principal $principalBackend,
								IUserManager $userManager,
								IGroupManager $groupManager,
								IEventDispatcher $dispatcher) {
		$this->db = $db;
		$this->principalBackend = $principalBackend;
		$this->userManager = $userManager;
		$this->dispatcher = $dispatcher;
		$this->sharingBackend = new Backend($this->db, $this->userManager, $groupManager, $principalBackend, 'addressbook');
	}

	/**
	 * Return the number of address books for a principal
	 *
	 * @param $principalUri
	 * @return int
	 */
	public function getAddressBooksForUserCount($principalUri) {
		$principalUri = $this->convertPrincipal($principalUri, true);
		$query = $this->db->getQueryBuilder();
		$query->select($query->func()->count('*'))
			->from('addressbooks')
			->where($query->expr()->eq('principaluri', $query->createNamedParameter($principalUri)));

		$result = $query->executeQuery();
		$column = (int) $result->fetchOne();
		$result->closeCursor();
		return $column;
	}

	/**
	 * Returns the list of address books for a specific user.
	 *
	 * Every addressbook should have the following properties:
	 *   id - an arbitrary unique id
	 *   uri - the 'basename' part of the url
	 *   principaluri - Same as the passed parameter
	 *
	 * Any additional clark-notation property may be passed besides this. Some
	 * common ones are :
	 *   {DAV:}displayname
	 *   {urn:ietf:params:xml:ns:carddav}addressbook-description
	 *   {http://calendarserver.org/ns/}getctag
	 *
	 * @param string $principalUri
	 * @return array
	 */
	public function getAddressBooksForUser($principalUri) {
		return $this->atomic(function () use ($principalUri) {
			$principalUriOriginal = $principalUri;
			$principalUri = $this->convertPrincipal($principalUri, true);
			$query = $this->db->getQueryBuilder();
			$query->select(['id', 'uri', 'displayname', 'principaluri', 'description', 'synctoken'])
				->from('addressbooks')
				->where($query->expr()->eq('principaluri', $query->createNamedParameter($principalUri)));

			$addressBooks = [];

			$result = $query->execute();
			while ($row = $result->fetch()) {
				$addressBooks[$row['id']] = [
					'id' => $row['id'],
					'uri' => $row['uri'],
					'principaluri' => $this->convertPrincipal($row['principaluri'], false),
					'{DAV:}displayname' => $row['displayname'],
					'{' . Plugin::NS_CARDDAV . '}addressbook-description' => $row['description'],
					'{http://calendarserver.org/ns/}getctag' => $row['synctoken'],
					'{http://sabredav.org/ns}sync-token' => $row['synctoken'] ?: '0',
				];

				$this->addOwnerPrincipal($addressBooks[$row['id']]);
			}
			$result->closeCursor();

			// query for shared addressbooks
			$principals = $this->principalBackend->getGroupMembership($principalUriOriginal, true);
			$principals = array_merge($principals, $this->principalBackend->getCircleMembership($principalUriOriginal));

			$principals[] = $principalUri;

			$query = $this->db->getQueryBuilder();
			$result = $query->select(['a.id', 'a.uri', 'a.displayname', 'a.principaluri', 'a.description', 'a.synctoken', 's.access'])
				->from('dav_shares', 's')
				->join('s', 'addressbooks', 'a', $query->expr()->eq('s.resourceid', 'a.id'))
				->where($query->expr()->in('s.principaluri', $query->createParameter('principaluri')))
				->andWhere($query->expr()->eq('s.type', $query->createParameter('type')))
				->setParameter('type', 'addressbook')
				->setParameter('principaluri', $principals, IQueryBuilder::PARAM_STR_ARRAY)
				->execute();

			$readOnlyPropertyName = '{' . \OCA\DAV\DAV\Sharing\Plugin::NS_OWNCLOUD . '}read-only';
			while ($row = $result->fetch()) {
				if ($row['principaluri'] === $principalUri) {
					continue;
				}

				$readOnly = (int)$row['access'] === Backend::ACCESS_READ;
				if (isset($addressBooks[$row['id']])) {
					if ($readOnly) {
						// New share can not have more permissions then the old one.
						continue;
					}
					if (isset($addressBooks[$row['id']][$readOnlyPropertyName]) &&
						$addressBooks[$row['id']][$readOnlyPropertyName] === 0) {
						// Old share is already read-write, no more permissions can be gained
						continue;
					}
				}

				[, $name] = \Sabre\Uri\split($row['principaluri']);
				$uri = $row['uri'] . '_shared_by_' . $name;
				$displayName = $row['displayname'] . ' (' . ($this->userManager->getDisplayName($name) ?? $name ?? '') . ')';

				$addressBooks[$row['id']] = [
					'id' => $row['id'],
					'uri' => $uri,
					'principaluri' => $principalUriOriginal,
					'{DAV:}displayname' => $displayName,
					'{' . Plugin::NS_CARDDAV . '}addressbook-description' => $row['description'],
					'{http://calendarserver.org/ns/}getctag' => $row['synctoken'],
					'{http://sabredav.org/ns}sync-token' => $row['synctoken'] ?: '0',
					'{' . \OCA\DAV\DAV\Sharing\Plugin::NS_OWNCLOUD . '}owner-principal' => $row['principaluri'],
					$readOnlyPropertyName => $readOnly,
				];

				$this->addOwnerPrincipal($addressBooks[$row['id']]);
			}
			$result->closeCursor();

			return array_values($addressBooks);
		}, $this->db);
	}

	public function getUsersOwnAddressBooks($principalUri) {
		$principalUri = $this->convertPrincipal($principalUri, true);
		$query = $this->db->getQueryBuilder();
		$query->select(['id', 'uri', 'displayname', 'principaluri', 'description', 'synctoken'])
			->from('addressbooks')
			->where($query->expr()->eq('principaluri', $query->createNamedParameter($principalUri)));

		$addressBooks = [];

		$result = $query->execute();
		while ($row = $result->fetch()) {
			$addressBooks[$row['id']] = [
				'id' => $row['id'],
				'uri' => $row['uri'],
				'principaluri' => $this->convertPrincipal($row['principaluri'], false),
				'{DAV:}displayname' => $row['displayname'],
				'{' . Plugin::NS_CARDDAV . '}addressbook-description' => $row['description'],
				'{http://calendarserver.org/ns/}getctag' => $row['synctoken'],
				'{http://sabredav.org/ns}sync-token' => $row['synctoken'] ?: '0',
			];

			$this->addOwnerPrincipal($addressBooks[$row['id']]);
		}
		$result->closeCursor();

		return array_values($addressBooks);
	}

	/**
	 * @param int $addressBookId
	 */
	public function getAddressBookById(int $addressBookId): ?array {
		$query = $this->db->getQueryBuilder();
		$result = $query->select(['id', 'uri', 'displayname', 'principaluri', 'description', 'synctoken'])
			->from('addressbooks')
			->where($query->expr()->eq('id', $query->createNamedParameter($addressBookId, IQueryBuilder::PARAM_INT)))
			->executeQuery();
		$row = $result->fetch();
		$result->closeCursor();
		if (!$row) {
			return null;
		}

		$addressBook = [
			'id' => $row['id'],
			'uri' => $row['uri'],
			'principaluri' => $row['principaluri'],
			'{DAV:}displayname' => $row['displayname'],
			'{' . Plugin::NS_CARDDAV . '}addressbook-description' => $row['description'],
			'{http://calendarserver.org/ns/}getctag' => $row['synctoken'],
			'{http://sabredav.org/ns}sync-token' => $row['synctoken'] ?: '0',
		];

		$this->addOwnerPrincipal($addressBook);

		return $addressBook;
	}

	public function getAddressBooksByUri(string $principal, string $addressBookUri): ?array {
		$query = $this->db->getQueryBuilder();
		$result = $query->select(['id', 'uri', 'displayname', 'principaluri', 'description', 'synctoken'])
			->from('addressbooks')
			->where($query->expr()->eq('uri', $query->createNamedParameter($addressBookUri)))
			->andWhere($query->expr()->eq('principaluri', $query->createNamedParameter($principal)))
			->setMaxResults(1)
			->executeQuery();

		$row = $result->fetch();
		$result->closeCursor();
		if ($row === false) {
			return null;
		}

		$addressBook = [
			'id' => $row['id'],
			'uri' => $row['uri'],
			'principaluri' => $row['principaluri'],
			'{DAV:}displayname' => $row['displayname'],
			'{' . Plugin::NS_CARDDAV . '}addressbook-description' => $row['description'],
			'{http://calendarserver.org/ns/}getctag' => $row['synctoken'],
			'{http://sabredav.org/ns}sync-token' => $row['synctoken'] ?: '0',

		];

		// system address books are always read only
		if ($principal === 'principals/system/system') {
			$addressBook['{' . \OCA\DAV\DAV\Sharing\Plugin::NS_OWNCLOUD . '}owner-principal'] = $row['principaluri'];
			$addressBook['{' . \OCA\DAV\DAV\Sharing\Plugin::NS_OWNCLOUD . '}read-only'] = true;
		}

		$this->addOwnerPrincipal($addressBook);

		return $addressBook;
	}

	/**
	 * Updates properties for an address book.
	 *
	 * The list of mutations is stored in a Sabre\DAV\PropPatch object.
	 * To do the actual updates, you must tell this object which properties
	 * you're going to process with the handle() method.
	 *
	 * Calling the handle method is like telling the PropPatch object "I
	 * promise I can handle updating this property".
	 *
	 * Read the PropPatch documentation for more info and examples.
	 *
	 * @param string $addressBookId
	 * @param \Sabre\DAV\PropPatch $propPatch
	 * @return void
	 */
	public function updateAddressBook($addressBookId, \Sabre\DAV\PropPatch $propPatch) {
		$this->atomic(function () use ($addressBookId, $propPatch) {
			$supportedProperties = [
				'{DAV:}displayname',
				'{' . Plugin::NS_CARDDAV . '}addressbook-description',
			];

			$propPatch->handle($supportedProperties, function ($mutations) use ($addressBookId) {
				$updates = [];
				foreach ($mutations as $property => $newValue) {
					switch ($property) {
						case '{DAV:}displayname':
							$updates['displayname'] = $newValue;
							break;
						case '{' . Plugin::NS_CARDDAV . '}addressbook-description':
							$updates['description'] = $newValue;
							break;
					}
				}
				$query = $this->db->getQueryBuilder();
				$query->update('addressbooks');

				foreach ($updates as $key => $value) {
					$query->set($key, $query->createNamedParameter($value));
				}
				$query->where($query->expr()->eq('id', $query->createNamedParameter($addressBookId)))
					->executeStatement();

				$this->addChange($addressBookId, "", 2);

				$addressBookRow = $this->getAddressBookById((int)$addressBookId);
				$shares = $this->getShares((int)$addressBookId);
				$this->dispatcher->dispatchTyped(new AddressBookUpdatedEvent((int)$addressBookId, $addressBookRow, $shares, $mutations));

				return true;
			});
		}, $this->db);
	}

	/**
	 * Creates a new address book
	 *
	 * @param string $principalUri
	 * @param string $url Just the 'basename' of the url.
	 * @param array $properties
	 * @return int
	 * @throws BadRequest
	 */
	public function createAddressBook($principalUri, $url, array $properties) {
		if (strlen($url) > 255) {
			throw new BadRequest('URI too long. Address book not created');
		}

		$values = [
			'displayname' => null,
			'description' => null,
			'principaluri' => $principalUri,
			'uri' => $url,
			'synctoken' => 1
		];

		foreach ($properties as $property => $newValue) {
			switch ($property) {
				case '{DAV:}displayname':
					$values['displayname'] = $newValue;
					break;
				case '{' . Plugin::NS_CARDDAV . '}addressbook-description':
					$values['description'] = $newValue;
					break;
				default:
					throw new BadRequest('Unknown property: ' . $property);
			}
		}

		// Fallback to make sure the displayname is set. Some clients may refuse
		// to work with addressbooks not having a displayname.
		if (is_null($values['displayname'])) {
			$values['displayname'] = $url;
		}

		[$addressBookId, $addressBookRow] = $this->atomic(function () use ($values) {
			$query = $this->db->getQueryBuilder();
			$query->insert('addressbooks')
				->values([
					'uri' => $query->createParameter('uri'),
					'displayname' => $query->createParameter('displayname'),
					'description' => $query->createParameter('description'),
					'principaluri' => $query->createParameter('principaluri'),
					'synctoken' => $query->createParameter('synctoken'),
				])
				->setParameters($values)
				->execute();

			$addressBookId = $query->getLastInsertId();
			return [
				$addressBookId,
				$this->getAddressBookById($addressBookId),
			];
		}, $this->db);

		$this->dispatcher->dispatchTyped(new AddressBookCreatedEvent($addressBookId, $addressBookRow));

		return $addressBookId;
	}

	/**
	 * Deletes an entire addressbook and all its contents
	 *
	 * @param mixed $addressBookId
	 * @return void
	 */
	public function deleteAddressBook($addressBookId) {
		$this->atomic(function () use ($addressBookId) {
			$addressBookId = (int)$addressBookId;
			$addressBookData = $this->getAddressBookById($addressBookId);
			$shares = $this->getShares($addressBookId);

			$query = $this->db->getQueryBuilder();
			$query->delete($this->dbCardsTable)
				->where($query->expr()->eq('addressbookid', $query->createParameter('addressbookid')))
				->setParameter('addressbookid', $addressBookId, IQueryBuilder::PARAM_INT)
				->executeStatement();

			$query = $this->db->getQueryBuilder();
			$query->delete('addressbookchanges')
				->where($query->expr()->eq('addressbookid', $query->createParameter('addressbookid')))
				->setParameter('addressbookid', $addressBookId, IQueryBuilder::PARAM_INT)
				->executeStatement();

			$query = $this->db->getQueryBuilder();
			$query->delete('addressbooks')
				->where($query->expr()->eq('id', $query->createParameter('id')))
				->setParameter('id', $addressBookId, IQueryBuilder::PARAM_INT)
				->executeStatement();

			$this->sharingBackend->deleteAllShares($addressBookId);

			$query = $this->db->getQueryBuilder();
			$query->delete($this->dbCardsPropertiesTable)
				->where($query->expr()->eq('addressbookid', $query->createNamedParameter($addressBookId, IQueryBuilder::PARAM_INT)))
				->executeStatement();

			if ($addressBookData) {
				$this->dispatcher->dispatchTyped(new AddressBookDeletedEvent($addressBookId, $addressBookData, $shares));
			}
		}, $this->db);
	}

	/**
	 * Returns all cards for a specific addressbook id.
	 *
	 * This method should return the following properties for each card:
	 *   * carddata - raw vcard data
	 *   * uri - Some unique url
	 *   * lastmodified - A unix timestamp
	 *
	 * It's recommended to also return the following properties:
	 *   * etag - A unique etag. This must change every time the card changes.
	 *   * size - The size of the card in bytes.
	 *
	 * If these last two properties are provided, less time will be spent
	 * calculating them. If they are specified, you can also omit carddata.
	 * This may speed up certain requests, especially with large cards.
	 *
	 * @param mixed $addressbookId
	 * @return array
	 */
	public function getCards($addressbookId) {
		$query = $this->db->getQueryBuilder();
		$query->select(['id', 'uri', 'lastmodified', 'etag', 'size', 'carddata', 'uid'])
			->from($this->dbCardsTable)
			->where($query->expr()->eq('addressbookid', $query->createNamedParameter($addressbookId)));

		$cards = [];

		$result = $query->execute();
		while ($row = $result->fetch()) {
			$row['etag'] = '"' . $row['etag'] . '"';

			$modified = false;
			$row['carddata'] = $this->readBlob($row['carddata'], $modified);
			if ($modified) {
				$row['size'] = strlen($row['carddata']);
			}

			$cards[] = $row;
		}
		$result->closeCursor();

		return $cards;
	}

	/**
	 * Returns a specific card.
	 *
	 * The same set of properties must be returned as with getCards. The only
	 * exception is that 'carddata' is absolutely required.
	 *
	 * If the card does not exist, you must return false.
	 *
	 * @param mixed $addressBookId
	 * @param string $cardUri
	 * @return array
	 */
	public function getCard($addressBookId, $cardUri) {
		$query = $this->db->getQueryBuilder();
		$query->select(['id', 'uri', 'lastmodified', 'etag', 'size', 'carddata', 'uid'])
			->from($this->dbCardsTable)
			->where($query->expr()->eq('addressbookid', $query->createNamedParameter($addressBookId)))
			->andWhere($query->expr()->eq('uri', $query->createNamedParameter($cardUri)))
			->setMaxResults(1);

		$result = $query->execute();
		$row = $result->fetch();
		if (!$row) {
			return false;
		}
		$row['etag'] = '"' . $row['etag'] . '"';

		$modified = false;
		$row['carddata'] = $this->readBlob($row['carddata'], $modified);
		if ($modified) {
			$row['size'] = strlen($row['carddata']);
		}

		return $row;
	}

	/**
	 * Returns a list of cards.
	 *
	 * This method should work identical to getCard, but instead return all the
	 * cards in the list as an array.
	 *
	 * If the backend supports this, it may allow for some speed-ups.
	 *
	 * @param mixed $addressBookId
	 * @param array $uris
	 * @return array
	 */
	public function getMultipleCards($addressBookId, array $uris) {
		if (empty($uris)) {
			return [];
		}

		$chunks = array_chunk($uris, 100);
		$cards = [];

		$query = $this->db->getQueryBuilder();
		$query->select(['id', 'uri', 'lastmodified', 'etag', 'size', 'carddata', 'uid'])
			->from($this->dbCardsTable)
			->where($query->expr()->eq('addressbookid', $query->createNamedParameter($addressBookId)))
			->andWhere($query->expr()->in('uri', $query->createParameter('uri')));

		foreach ($chunks as $uris) {
			$query->setParameter('uri', $uris, IQueryBuilder::PARAM_STR_ARRAY);
			$result = $query->execute();

			while ($row = $result->fetch()) {
				$row['etag'] = '"' . $row['etag'] . '"';

				$modified = false;
				$row['carddata'] = $this->readBlob($row['carddata'], $modified);
				if ($modified) {
					$row['size'] = strlen($row['carddata']);
				}

				$cards[] = $row;
			}
			$result->closeCursor();
		}
		return $cards;
	}

	/**
	 * Creates a new card.
	 *
	 * The addressbook id will be passed as the first argument. This is the
	 * same id as it is returned from the getAddressBooksForUser method.
	 *
	 * The cardUri is a base uri, and doesn't include the full path. The
	 * cardData argument is the vcard body, and is passed as a string.
	 *
	 * It is possible to return an ETag from this method. This ETag is for the
	 * newly created resource, and must be enclosed with double quotes (that
	 * is, the string itself must contain the double quotes).
	 *
	 * You should only return the ETag if you store the carddata as-is. If a
	 * subsequent GET request on the same card does not have the same body,
	 * byte-by-byte and you did return an ETag here, clients tend to get
	 * confused.
	 *
	 * If you don't return an ETag, you can just return null.
	 *
	 * @param mixed $addressBookId
	 * @param string $cardUri
	 * @param string $cardData
	 * @param bool $checkAlreadyExists
	 * @return string
	 */
	public function createCard($addressBookId, $cardUri, $cardData, bool $checkAlreadyExists = true) {
		$etag = md5($cardData);
		$uid = $this->getUID($cardData);
		return $this->atomic(function () use ($addressBookId, $cardUri, $cardData, $checkAlreadyExists, $etag, $uid) {
			if ($checkAlreadyExists) {
				$q = $this->db->getQueryBuilder();
				$q->select('uid')
					->from($this->dbCardsTable)
					->where($q->expr()->eq('addressbookid', $q->createNamedParameter($addressBookId)))
					->andWhere($q->expr()->eq('uid', $q->createNamedParameter($uid)))
					->setMaxResults(1);
				$result = $q->executeQuery();
				$count = (bool)$result->fetchOne();
				$result->closeCursor();
				if ($count) {
					throw new \Sabre\DAV\Exception\BadRequest('VCard object with uid already exists in this addressbook collection.');
				}
			}

			$query = $this->db->getQueryBuilder();
			$query->insert('cards')
				->values([
					'carddata' => $query->createNamedParameter($cardData, IQueryBuilder::PARAM_LOB),
					'uri' => $query->createNamedParameter($cardUri),
					'lastmodified' => $query->createNamedParameter(time()),
					'addressbookid' => $query->createNamedParameter($addressBookId),
					'size' => $query->createNamedParameter(strlen($cardData)),
					'etag' => $query->createNamedParameter($etag),
					'uid' => $query->createNamedParameter($uid),
				])
				->execute();

			$etagCacheKey = "$addressBookId#$cardUri";
			$this->etagCache[$etagCacheKey] = $etag;

			$this->addChange($addressBookId, $cardUri, 1);
			$this->updateProperties($addressBookId, $cardUri, $cardData);

			$addressBookData = $this->getAddressBookById($addressBookId);
			$shares = $this->getShares($addressBookId);
			$objectRow = $this->getCard($addressBookId, $cardUri);
			$this->dispatcher->dispatchTyped(new CardCreatedEvent($addressBookId, $addressBookData, $shares, $objectRow));

			return '"' . $etag . '"';
		}, $this->db);
	}

	/**
	 * Updates a card.
	 *
	 * The addressbook id will be passed as the first argument. This is the
	 * same id as it is returned from the getAddressBooksForUser method.
	 *
	 * The cardUri is a base uri, and doesn't include the full path. The
	 * cardData argument is the vcard body, and is passed as a string.
	 *
	 * It is possible to return an ETag from this method. This ETag should
	 * match that of the updated resource, and must be enclosed with double
	 * quotes (that is: the string itself must contain the actual quotes).
	 *
	 * You should only return the ETag if you store the carddata as-is. If a
	 * subsequent GET request on the same card does not have the same body,
	 * byte-by-byte and you did return an ETag here, clients tend to get
	 * confused.
	 *
	 * If you don't return an ETag, you can just return null.
	 *
	 * @param mixed $addressBookId
	 * @param string $cardUri
	 * @param string $cardData
	 * @return string
	 */
	public function updateCard($addressBookId, $cardUri, $cardData) {
		$uid = $this->getUID($cardData);
		$etag = md5($cardData);

		return $this->atomic(function () use ($addressBookId, $cardUri, $cardData, $uid, $etag) {
			$query = $this->db->getQueryBuilder();

			// check for recently stored etag and stop if it is the same
			$etagCacheKey = "$addressBookId#$cardUri";
			if (isset($this->etagCache[$etagCacheKey]) && $this->etagCache[$etagCacheKey] === $etag) {
				return '"' . $etag . '"';
			}

			$query->update($this->dbCardsTable)
				->set('carddata', $query->createNamedParameter($cardData, IQueryBuilder::PARAM_LOB))
				->set('lastmodified', $query->createNamedParameter(time()))
				->set('size', $query->createNamedParameter(strlen($cardData)))
				->set('etag', $query->createNamedParameter($etag))
				->set('uid', $query->createNamedParameter($uid))
				->where($query->expr()->eq('uri', $query->createNamedParameter($cardUri)))
				->andWhere($query->expr()->eq('addressbookid', $query->createNamedParameter($addressBookId)))
				->execute();

			$this->etagCache[$etagCacheKey] = $etag;

			$this->addChange($addressBookId, $cardUri, 2);
			$this->updateProperties($addressBookId, $cardUri, $cardData);

			$addressBookData = $this->getAddressBookById($addressBookId);
			$shares = $this->getShares($addressBookId);
			$objectRow = $this->getCard($addressBookId, $cardUri);
			$this->dispatcher->dispatchTyped(new CardUpdatedEvent($addressBookId, $addressBookData, $shares, $objectRow));
			return '"' . $etag . '"';
		}, $this->db);
	}

	/**
	 * @throws Exception
	 */
	public function moveCard(int $sourceAddressBookId, int $targetAddressBookId, string $cardUri, string $oldPrincipalUri): bool {
		return $this->atomic(function () use ($sourceAddressBookId, $targetAddressBookId, $cardUri, $oldPrincipalUri) {
			$card = $this->getCard($sourceAddressBookId, $cardUri);
			if (empty($card)) {
				return false;
			}

			$query = $this->db->getQueryBuilder();
			$query->update('cards')
				->set('addressbookid', $query->createNamedParameter($targetAddressBookId, IQueryBuilder::PARAM_INT))
				->where($query->expr()->eq('uri', $query->createNamedParameter($cardUri, IQueryBuilder::PARAM_STR), IQueryBuilder::PARAM_STR))
				->andWhere($query->expr()->eq('addressbookid', $query->createNamedParameter($sourceAddressBookId, IQueryBuilder::PARAM_INT), IQueryBuilder::PARAM_INT))
				->executeStatement();

			$this->purgeProperties($sourceAddressBookId, (int)$card['id']);
			$this->updateProperties($sourceAddressBookId, $card['uri'], $card['carddata']);

			$this->addChange($sourceAddressBookId, $card['uri'], 3);
			$this->addChange($targetAddressBookId, $card['uri'], 1);

			$card = $this->getCard($targetAddressBookId, $cardUri);
			// Card wasn't found - possibly because it was deleted in the meantime by a different client
			if (empty($card)) {
				return false;
			}

			$targetAddressBookRow = $this->getAddressBookById($targetAddressBookId);
			// the address book this card is being moved to does not exist any longer
			if (empty($targetAddressBookRow)) {
				return false;
			}

			$sourceShares = $this->getShares($sourceAddressBookId);
			$targetShares = $this->getShares($targetAddressBookId);
			$sourceAddressBookRow = $this->getAddressBookById($sourceAddressBookId);
			$this->dispatcher->dispatchTyped(new CardMovedEvent($sourceAddressBookId, $sourceAddressBookRow, $targetAddressBookId, $targetAddressBookRow, $sourceShares, $targetShares, $card));
			return true;
		}, $this->db);
	}

	/**
	 * Deletes a card
	 *
	 * @param mixed $addressBookId
	 * @param string $cardUri
	 * @return bool
	 */
	public function deleteCard($addressBookId, $cardUri) {
		return $this->atomic(function () use ($addressBookId, $cardUri) {
			$addressBookData = $this->getAddressBookById($addressBookId);
			$shares = $this->getShares($addressBookId);
			$objectRow = $this->getCard($addressBookId, $cardUri);

			try {
				$cardId = $this->getCardId($addressBookId, $cardUri);
			} catch (\InvalidArgumentException $e) {
				$cardId = null;
			}
			$query = $this->db->getQueryBuilder();
			$ret = $query->delete($this->dbCardsTable)
				->where($query->expr()->eq('addressbookid', $query->createNamedParameter($addressBookId)))
				->andWhere($query->expr()->eq('uri', $query->createNamedParameter($cardUri)))
				->executeStatement();

			$this->addChange($addressBookId, $cardUri, 3);

			if ($ret === 1) {
				if ($cardId !== null) {
					$this->dispatcher->dispatchTyped(new CardDeletedEvent($addressBookId, $addressBookData, $shares, $objectRow));
					$this->purgeProperties($addressBookId, $cardId);
				}
				return true;
			}

			return false;
		}, $this->db);
	}

	/**
	 * The getChanges method returns all the changes that have happened, since
	 * the specified syncToken in the specified address book.
	 *
	 * This function should return an array, such as the following:
	 *
	 * [
	 *   'syncToken' => 'The current synctoken',
	 *   'added'   => [
	 *      'new.txt',
	 *   ],
	 *   'modified'   => [
	 *      'modified.txt',
	 *   ],
	 *   'deleted' => [
	 *      'foo.php.bak',
	 *      'old.txt'
	 *   ]
	 * ];
	 *
	 * The returned syncToken property should reflect the *current* syncToken
	 * of the calendar, as reported in the {http://sabredav.org/ns}sync-token
	 * property. This is needed here too, to ensure the operation is atomic.
	 *
	 * If the $syncToken argument is specified as null, this is an initial
	 * sync, and all members should be reported.
	 *
	 * The modified property is an array of nodenames that have changed since
	 * the last token.
	 *
	 * The deleted property is an array with nodenames, that have been deleted
	 * from collection.
	 *
	 * The $syncLevel argument is basically the 'depth' of the report. If it's
	 * 1, you only have to report changes that happened only directly in
	 * immediate descendants. If it's 2, it should also include changes from
	 * the nodes below the child collections. (grandchildren)
	 *
	 * The $limit argument allows a client to specify how many results should
	 * be returned at most. If the limit is not specified, it should be treated
	 * as infinite.
	 *
	 * If the limit (infinite or not) is higher than you're willing to return,
	 * you should throw a Sabre\DAV\Exception\TooMuchMatches() exception.
	 *
	 * If the syncToken is expired (due to data cleanup) or unknown, you must
	 * return null.
	 *
	 * The limit is 'suggestive'. You are free to ignore it.
	 *
	 * @param string $addressBookId
	 * @param string $syncToken
	 * @param int $syncLevel
	 * @param int|null $limit
	 * @return array
	 */
	public function getChangesForAddressBook($addressBookId, $syncToken, $syncLevel, $limit = null) {
		// Current synctoken
		return $this->atomic(function () use ($addressBookId, $syncToken, $syncLevel, $limit) {
			$qb = $this->db->getQueryBuilder();
			$qb->select('synctoken')
				->from('addressbooks')
				->where(
					$qb->expr()->eq('id', $qb->createNamedParameter($addressBookId))
				);
			$stmt = $qb->executeQuery();
			$currentToken = $stmt->fetchOne();
			$stmt->closeCursor();

			if (is_null($currentToken)) {
				return [];
			}

			$result = [
				'syncToken' => $currentToken,
				'added' => [],
				'modified' => [],
				'deleted' => [],
			];

			if ($syncToken) {
				$qb = $this->db->getQueryBuilder();
				$qb->select('uri', 'operation')
					->from('addressbookchanges')
					->where(
						$qb->expr()->andX(
							$qb->expr()->gte('synctoken', $qb->createNamedParameter($syncToken)),
							$qb->expr()->lt('synctoken', $qb->createNamedParameter($currentToken)),
							$qb->expr()->eq('addressbookid', $qb->createNamedParameter($addressBookId))
						)
					)->orderBy('synctoken');

				if (is_int($limit) && $limit > 0) {
					$qb->setMaxResults($limit);
				}

				// Fetching all changes
				$stmt = $qb->executeQuery();

				$changes = [];

				// This loop ensures that any duplicates are overwritten, only the
				// last change on a node is relevant.
				while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
					$changes[$row['uri']] = $row['operation'];
				}
				$stmt->closeCursor();

				foreach ($changes as $uri => $operation) {
					switch ($operation) {
						case 1:
							$result['added'][] = $uri;
							break;
						case 2:
							$result['modified'][] = $uri;
							break;
						case 3:
							$result['deleted'][] = $uri;
							break;
					}
				}
			} else {
				$qb = $this->db->getQueryBuilder();
				$qb->select('uri')
					->from('cards')
					->where(
						$qb->expr()->eq('addressbookid', $qb->createNamedParameter($addressBookId))
					);
				// No synctoken supplied, this is the initial sync.
				$stmt = $qb->executeQuery();
				$result['added'] = $stmt->fetchAll(\PDO::FETCH_COLUMN);
				$stmt->closeCursor();
			}
			return $result;
		}, $this->db);
	}

	/**
	 * Adds a change record to the addressbookchanges table.
	 *
	 * @param mixed $addressBookId
	 * @param string $objectUri
	 * @param int $operation 1 = add, 2 = modify, 3 = delete
	 * @return void
	 */
	protected function addChange(int $addressBookId, string $objectUri, int $operation): void {
		$this->atomic(function () use ($addressBookId, $objectUri, $operation) {
			$query = $this->db->getQueryBuilder();
			$query->select('synctoken')
				->from('addressbooks')
				->where($query->expr()->eq('id', $query->createNamedParameter($addressBookId)));
			$result = $query->executeQuery();
			$syncToken = (int)$result->fetchOne();
			$result->closeCursor();

			$query = $this->db->getQueryBuilder();
			$query->insert('addressbookchanges')
				->values([
					'uri' => $query->createNamedParameter($objectUri),
					'synctoken' => $query->createNamedParameter($syncToken),
					'addressbookid' => $query->createNamedParameter($addressBookId),
					'operation' => $query->createNamedParameter($operation),
				])
				->executeStatement();

			$query = $this->db->getQueryBuilder();
			$query->update('addressbooks')
				->set('synctoken', $query->createNamedParameter($syncToken + 1, IQueryBuilder::PARAM_INT))
				->where($query->expr()->eq('id', $query->createNamedParameter($addressBookId)))
				->executeStatement();
		}, $this->db);
	}

	/**
	 * @param resource|string $cardData
	 * @param bool $modified
	 * @return string
	 */
	private function readBlob($cardData, &$modified = false) {
		if (is_resource($cardData)) {
			$cardData = stream_get_contents($cardData);
		}

		// Micro optimisation
		// don't loop through
		if (str_starts_with($cardData, 'PHOTO:data:')) {
			return $cardData;
		}

		$cardDataArray = explode("\r\n", $cardData);

		$cardDataFiltered = [];
		$removingPhoto = false;
		foreach ($cardDataArray as $line) {
			if (str_starts_with($line, 'PHOTO:data:')
				&& !str_starts_with($line, 'PHOTO:data:image/')) {
				// Filter out PHOTO data of non-images
				$removingPhoto = true;
				$modified = true;
				continue;
			}

			if ($removingPhoto) {
				if (str_starts_with($line, ' ')) {
					continue;
				}
				// No leading space means this is a new property
				$removingPhoto = false;
			}

			$cardDataFiltered[] = $line;
		}
		return implode("\r\n", $cardDataFiltered);
	}

	/**
	 * @param list<array{href: string, commonName: string, readOnly: bool}> $add
	 * @param list<string> $remove
	 */
	public function updateShares(IShareable $shareable, array $add, array $remove): void {
		$this->atomic(function () use ($shareable, $add, $remove) {
			$addressBookId = $shareable->getResourceId();
			$addressBookData = $this->getAddressBookById($addressBookId);
			$oldShares = $this->getShares($addressBookId);

			$this->sharingBackend->updateShares($shareable, $add, $remove);

			$this->dispatcher->dispatchTyped(new AddressBookShareUpdatedEvent($addressBookId, $addressBookData, $oldShares, $add, $remove));
		}, $this->db);
	}

	/**
	 * Search contacts in a specific address-book
	 *
	 * @param int $addressBookId
	 * @param string $pattern which should match within the $searchProperties
	 * @param array $searchProperties defines the properties within the query pattern should match
	 * @param array $options = array() to define the search behavior
	 * 	  - 'types' boolean (since 15.0.0) If set to true, fields that come with a TYPE property will be an array
	 *    - 'escape_like_param' - If set to false wildcards _ and % are not escaped, otherwise they are
	 *    - 'limit' - Set a numeric limit for the search results
	 *    - 'offset' - Set the offset for the limited search results
	 *    - 'wildcard' - Whether the search should use wildcards
	 * @psalm-param array{types?: bool, escape_like_param?: bool, limit?: int, offset?: int, wildcard?: bool} $options
	 * @return array an array of contacts which are arrays of key-value-pairs
	 */
	public function search($addressBookId, $pattern, $searchProperties, $options = []): array {
		return $this->atomic(function () use ($addressBookId, $pattern, $searchProperties, $options) {
			return $this->searchByAddressBookIds([$addressBookId], $pattern, $searchProperties, $options);
		}, $this->db);
	}

	/**
	 * Search contacts in all address-books accessible by a user
	 *
	 * @param string $principalUri
	 * @param string $pattern
	 * @param array $searchProperties
	 * @param array $options
	 * @return array
	 */
	public function searchPrincipalUri(string $principalUri,
									   string $pattern,
									   array $searchProperties,
									   array $options = []): array {
		return $this->atomic(function () use ($principalUri, $pattern, $searchProperties, $options) {
			$addressBookIds = array_map(static function ($row):int {
				return (int) $row['id'];
			}, $this->getAddressBooksForUser($principalUri));

			return $this->searchByAddressBookIds($addressBookIds, $pattern, $searchProperties, $options);
		}, $this->db);
	}

	/**
	 * @param int[] $addressBookIds
	 * @param string $pattern
	 * @param array $searchProperties
	 * @param array $options
	 * @psalm-param array{
	 *   types?: bool,
	 *   escape_like_param?: bool,
	 *   limit?: int,
	 *   offset?: int,
	 *   wildcard?: bool,
	 *   since?: DateTimeFilter|null,
	 *   until?: DateTimeFilter|null,
	 *   person?: string
	 * } $options
	 * @return array
	 */
	private function searchByAddressBookIds(array $addressBookIds,
											string $pattern,
											array $searchProperties,
											array $options = []): array {
		if (empty($addressBookIds)) {
			return [];
		}
		$escapePattern = !\array_key_exists('escape_like_param', $options) || $options['escape_like_param'] !== false;
		$useWildcards = !\array_key_exists('wildcard', $options) || $options['wildcard'] !== false;

		if ($escapePattern) {
			$searchProperties = array_filter($searchProperties, function ($property) use ($pattern) {
				if ($property === 'EMAIL' && str_contains($pattern, ' ')) {
					// There can be no spaces in emails
					return false;
				}

				if ($property === 'CLOUD' && preg_match('/[^a-zA-Z0-9 :_.@\/\-\']/', $pattern) === 1) {
					// There can be no chars in cloud ids which are not valid for user ids plus :/
					// worst case: CA61590A-BBBC-423E-84AF-E6DF01455A53@https://my.nxt/srv/
					return false;
				}

				return true;
			});
		}

		if (empty($searchProperties)) {
			return [];
		}

		$query2 = $this->db->getQueryBuilder();
		$query2->selectDistinct('cp.cardid')
			->from($this->dbCardsPropertiesTable, 'cp')
			->where($query2->expr()->in('cp.addressbookid', $query2->createNamedParameter($addressBookIds, IQueryBuilder::PARAM_INT_ARRAY), IQueryBuilder::PARAM_INT_ARRAY))
			->andWhere($query2->expr()->in('cp.name', $query2->createNamedParameter($searchProperties, IQueryBuilder::PARAM_STR_ARRAY)));

		// No need for like when the pattern is empty
		if ('' !== $pattern) {
			if (!$useWildcards) {
				$query2->andWhere($query2->expr()->eq('cp.value', $query2->createNamedParameter($pattern)));
			} elseif (!$escapePattern) {
				$query2->andWhere($query2->expr()->ilike('cp.value', $query2->createNamedParameter($pattern)));
			} else {
				$query2->andWhere($query2->expr()->ilike('cp.value', $query2->createNamedParameter('%' . $this->db->escapeLikeParameter($pattern) . '%')));
			}
		}
		if (isset($options['limit'])) {
			$query2->setMaxResults($options['limit']);
		}
		if (isset($options['offset'])) {
			$query2->setFirstResult($options['offset']);
		}

		if (isset($options['person'])) {
			$query2->andWhere($query2->expr()->ilike('cp.value', $query2->createNamedParameter('%' . $this->db->escapeLikeParameter($options['person']) . '%')));
		}
		if (isset($options['since']) || isset($options['until'])) {
			$query2->join('cp', $this->dbCardsPropertiesTable, 'cp_bday', 'cp.cardid = cp_bday.cardid');
			$query2->andWhere($query2->expr()->eq('cp_bday.name', $query2->createNamedParameter('BDAY')));
			/**
			 * FIXME Find a way to match only 4 last digits
			 * BDAY can be --1018 without year or 20001019 with it
			 * $bDayOr = $query2->expr()->orX();
			 * if ($options['since'] instanceof DateTimeFilter) {
			 * $bDayOr->add(
			 * $query2->expr()->gte('SUBSTR(cp_bday.value, -4)',
			 * $query2->createNamedParameter($options['since']->get()->format('md')))
			 * );
			 * }
			 * if ($options['until'] instanceof DateTimeFilter) {
			 * $bDayOr->add(
			 * $query2->expr()->lte('SUBSTR(cp_bday.value, -4)',
			 * $query2->createNamedParameter($options['until']->get()->format('md')))
			 * );
			 * }
			 * $query2->andWhere($bDayOr);
			 */
		}

		$result = $query2->execute();
		$matches = $result->fetchAll();
		$result->closeCursor();
		$matches = array_map(function ($match) {
			return (int)$match['cardid'];
		}, $matches);

		$cards = [];
		$query = $this->db->getQueryBuilder();
		$query->select('c.addressbookid', 'c.carddata', 'c.uri')
			->from($this->dbCardsTable, 'c')
			->where($query->expr()->in('c.id', $query->createParameter('matches')));

		foreach (array_chunk($matches, 1000) as $matchesChunk) {
			$query->setParameter('matches', $matchesChunk, IQueryBuilder::PARAM_INT_ARRAY);
			$result = $query->executeQuery();
			$cards = array_merge($cards, $result->fetchAll());
			$result->closeCursor();
		}

		return array_map(function ($array) {
			$array['addressbookid'] = (int) $array['addressbookid'];
			$modified = false;
			$array['carddata'] = $this->readBlob($array['carddata'], $modified);
			if ($modified) {
				$array['size'] = strlen($array['carddata']);
			}
			return $array;
		}, $cards);
	}

	/**
	 * @param int $bookId
	 * @param string $name
	 * @return array
	 */
	public function collectCardProperties($bookId, $name) {
		$query = $this->db->getQueryBuilder();
		$result = $query->selectDistinct('value')
			->from($this->dbCardsPropertiesTable)
			->where($query->expr()->eq('name', $query->createNamedParameter($name)))
			->andWhere($query->expr()->eq('addressbookid', $query->createNamedParameter($bookId)))
			->execute();

		$all = $result->fetchAll(PDO::FETCH_COLUMN);
		$result->closeCursor();

		return $all;
	}

	/**
	 * get URI from a given contact
	 *
	 * @param int $id
	 * @return string
	 */
	public function getCardUri($id) {
		$query = $this->db->getQueryBuilder();
		$query->select('uri')->from($this->dbCardsTable)
			->where($query->expr()->eq('id', $query->createParameter('id')))
			->setParameter('id', $id);

		$result = $query->execute();
		$uri = $result->fetch();
		$result->closeCursor();

		if (!isset($uri['uri'])) {
			throw new \InvalidArgumentException('Card does not exists: ' . $id);
		}

		return $uri['uri'];
	}

	/**
	 * return contact with the given URI
	 *
	 * @param int $addressBookId
	 * @param string $uri
	 * @returns array
	 */
	public function getContact($addressBookId, $uri) {
		$result = [];
		$query = $this->db->getQueryBuilder();
		$query->select('*')->from($this->dbCardsTable)
			->where($query->expr()->eq('uri', $query->createNamedParameter($uri)))
			->andWhere($query->expr()->eq('addressbookid', $query->createNamedParameter($addressBookId)));
		$queryResult = $query->execute();
		$contact = $queryResult->fetch();
		$queryResult->closeCursor();

		if (is_array($contact)) {
			$modified = false;
			$contact['etag'] = '"' . $contact['etag'] . '"';
			$contact['carddata'] = $this->readBlob($contact['carddata'], $modified);
			if ($modified) {
				$contact['size'] = strlen($contact['carddata']);
			}

			$result = $contact;
		}

		return $result;
	}

	/**
	 * Returns the list of people whom this address book is shared with.
	 *
	 * Every element in this array should have the following properties:
	 *   * href - Often a mailto: address
	 *   * commonName - Optional, for example a first + last name
	 *   * status - See the Sabre\CalDAV\SharingPlugin::STATUS_ constants.
	 *   * readOnly - boolean
	 *
	 * @return list<array{href: string, commonName: string, status: int, readOnly: bool, '{http://owncloud.org/ns}principal': string, '{http://owncloud.org/ns}group-share': bool}>
	 */
	public function getShares(int $addressBookId): array {
		return $this->sharingBackend->getShares($addressBookId);
	}

	/**
	 * update properties table
	 *
	 * @param int $addressBookId
	 * @param string $cardUri
	 * @param string $vCardSerialized
	 */
	protected function updateProperties($addressBookId, $cardUri, $vCardSerialized) {
		$this->atomic(function () use ($addressBookId, $cardUri, $vCardSerialized) {
			$cardId = $this->getCardId($addressBookId, $cardUri);
			$vCard = $this->readCard($vCardSerialized);

			$this->purgeProperties($addressBookId, $cardId);

			$query = $this->db->getQueryBuilder();
			$query->insert($this->dbCardsPropertiesTable)
				->values(
					[
						'addressbookid' => $query->createNamedParameter($addressBookId),
						'cardid' => $query->createNamedParameter($cardId),
						'name' => $query->createParameter('name'),
						'value' => $query->createParameter('value'),
						'preferred' => $query->createParameter('preferred')
					]
				);

			foreach ($vCard->children() as $property) {
				if (!in_array($property->name, self::$indexProperties)) {
					continue;
				}
				$preferred = 0;
				foreach ($property->parameters as $parameter) {
					if ($parameter->name === 'TYPE' && strtoupper($parameter->getValue()) === 'PREF') {
						$preferred = 1;
						break;
					}
				}
				$query->setParameter('name', $property->name);
				$query->setParameter('value', mb_strcut($property->getValue(), 0, 254));
				$query->setParameter('preferred', $preferred);
				$query->execute();
			}
		}, $this->db);
	}

	/**
	 * read vCard data into a vCard object
	 *
	 * @param string $cardData
	 * @return VCard
	 */
	protected function readCard($cardData) {
		return Reader::read($cardData);
	}

	/**
	 * delete all properties from a given card
	 *
	 * @param int $addressBookId
	 * @param int $cardId
	 */
	protected function purgeProperties($addressBookId, $cardId) {
		$query = $this->db->getQueryBuilder();
		$query->delete($this->dbCardsPropertiesTable)
			->where($query->expr()->eq('cardid', $query->createNamedParameter($cardId)))
			->andWhere($query->expr()->eq('addressbookid', $query->createNamedParameter($addressBookId)));
		$query->execute();
	}

	/**
	 * Get ID from a given contact
	 */
	protected function getCardId(int $addressBookId, string $uri): int {
		$query = $this->db->getQueryBuilder();
		$query->select('id')->from($this->dbCardsTable)
			->where($query->expr()->eq('uri', $query->createNamedParameter($uri)))
			->andWhere($query->expr()->eq('addressbookid', $query->createNamedParameter($addressBookId)));

		$result = $query->execute();
		$cardIds = $result->fetch();
		$result->closeCursor();

		if (!isset($cardIds['id'])) {
			throw new \InvalidArgumentException('Card does not exists: ' . $uri);
		}

		return (int)$cardIds['id'];
	}

	/**
	 * For shared address books the sharee is set in the ACL of the address book
	 *
	 * @param int $addressBookId
	 * @param list<array{privilege: string, principal: string, protected: bool}> $acl
	 * @return list<array{privilege: string, principal: string, protected: bool}>
	 */
	public function applyShareAcl(int $addressBookId, array $acl): array {
		return $this->sharingBackend->applyShareAcl($addressBookId, $acl);
	}

	/**
	 * @throws \InvalidArgumentException
	 */
	public function pruneOutdatedSyncTokens(int $keep = 10_000): int {
		if ($keep < 0) {
			throw new \InvalidArgumentException();
		}

		$query = $this->db->getQueryBuilder();
		$query->select($query->func()->max('id'))
			->from('addressbookchanges');

		$result = $query->executeQuery();
		$maxId = (int) $result->fetchOne();
		$result->closeCursor();
		if (!$maxId || $maxId < $keep) {
			return 0;
		}

		$query = $this->db->getQueryBuilder();
		$query->delete('addressbookchanges')
			->where($query->expr()->lte('id', $query->createNamedParameter($maxId - $keep, IQueryBuilder::PARAM_INT), IQueryBuilder::PARAM_INT));
		return $query->executeStatement();
	}

	private function convertPrincipal(string $principalUri, bool $toV2): string {
		if ($this->principalBackend->getPrincipalPrefix() === 'principals') {
			[, $name] = \Sabre\Uri\split($principalUri);
			if ($toV2 === true) {
				return "principals/users/$name";
			}
			return "principals/$name";
		}
		return $principalUri;
	}

	private function addOwnerPrincipal(array &$addressbookInfo): void {
		$ownerPrincipalKey = '{' . \OCA\DAV\DAV\Sharing\Plugin::NS_OWNCLOUD . '}owner-principal';
		$displaynameKey = '{' . \OCA\DAV\DAV\Sharing\Plugin::NS_NEXTCLOUD . '}owner-displayname';
		if (isset($addressbookInfo[$ownerPrincipalKey])) {
			$uri = $addressbookInfo[$ownerPrincipalKey];
		} else {
			$uri = $addressbookInfo['principaluri'];
		}

		$principalInformation = $this->principalBackend->getPrincipalByPath($uri);
		if (isset($principalInformation['{DAV:}displayname'])) {
			$addressbookInfo[$displaynameKey] = $principalInformation['{DAV:}displayname'];
		}
	}

	/**
	 * Extract UID from vcard
	 *
	 * @param string $cardData the vcard raw data
	 * @return string the uid
	 * @throws BadRequest if no UID is available or vcard is empty
	 */
	private function getUID(string $cardData): string {
		if ($cardData !== '') {
			$vCard = Reader::read($cardData);
			if ($vCard->UID) {
				$uid = $vCard->UID->getValue();
				return $uid;
			}
			// should already be handled, but just in case
			throw new BadRequest('vCards on CardDAV servers MUST have a UID property');
		}
		// should already be handled, but just in case
		throw new BadRequest('vCard can not be empty');
	}
}
