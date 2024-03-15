<?php

/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 * @copyright Copyright (c) 2016, Björn Schießle
 *
 * @author Bjoern Schiessle <bjoern@schiessle.org>
 * @author Björn Schießle <bjoern@schiessle.org>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Daniel Calviño Sánchez <danxuliu@gmail.com>
 * @author Daniel Kesselberg <mail@danielkesselberg.de>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Julius Härtl <jus@bitgrid.net>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Thomas Citharel <nextcloud@tcit.fr>
 * @author Vincent Petry <vincent@nextcloud.com>
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

namespace OC\Accounts;

use Exception;
use InvalidArgumentException;
use OC\Profile\TProfileHelper;
use OCA\Settings\BackgroundJobs\VerifyUserData;
use OCP\Accounts\IAccount;
use OCP\Accounts\IAccountManager;
use OCP\Accounts\IAccountProperty;
use OCP\Accounts\IAccountPropertyCollection;
use OCP\Accounts\PropertyDoesNotExistException;
use OCP\Accounts\UserUpdatedEvent;
use OCP\BackgroundJob\IJobList;
use OCP\Cache\CappedMemoryCache;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\Defaults;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\IL10N;
use OCP\IPhoneNumberUtil;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\L10N\IFactory;
use OCP\Mail\IMailer;
use OCP\Security\ICrypto;
use OCP\Security\VerificationToken\IVerificationToken;
use OCP\User\Backend\IGetDisplayNameBackend;
use OCP\Util;
use Psr\Log\LoggerInterface;
use function array_flip;
use function iterator_to_array;
use function json_decode;
use function json_encode;
use function json_last_error;

/**
 * Class AccountManager
 *
 * Manage system accounts table
 *
 * @group DB
 * @package OC\Accounts
 */
class AccountManager implements IAccountManager {
	use TAccountsHelper;

	use TProfileHelper;

	private string $table = 'accounts';
	private string $dataTable = 'accounts_data';
	private ?IL10N $l10n = null;
	private CappedMemoryCache $internalCache;

	/**
	 * The list of default scopes for each property.
	 */
	public const DEFAULT_SCOPES = [
		self::PROPERTY_DISPLAYNAME => self::SCOPE_FEDERATED,
		self::PROPERTY_ADDRESS => self::SCOPE_LOCAL,
		self::PROPERTY_WEBSITE => self::SCOPE_LOCAL,
		self::PROPERTY_EMAIL => self::SCOPE_FEDERATED,
		self::PROPERTY_AVATAR => self::SCOPE_FEDERATED,
		self::PROPERTY_PHONE => self::SCOPE_LOCAL,
		self::PROPERTY_TWITTER => self::SCOPE_LOCAL,
		self::PROPERTY_FEDIVERSE => self::SCOPE_LOCAL,
		self::PROPERTY_ORGANISATION => self::SCOPE_LOCAL,
		self::PROPERTY_ROLE => self::SCOPE_LOCAL,
		self::PROPERTY_HEADLINE => self::SCOPE_LOCAL,
		self::PROPERTY_BIOGRAPHY => self::SCOPE_LOCAL,
	];

	public function __construct(
		private IDBConnection $connection,
		private IConfig $config,
		private IEventDispatcher $dispatcher,
		private IJobList $jobList,
		private LoggerInterface $logger,
		private IVerificationToken $verificationToken,
		private IMailer $mailer,
		private Defaults $defaults,
		private IFactory $l10nFactory,
		private IURLGenerator $urlGenerator,
		private ICrypto $crypto,
		private IPhoneNumberUtil $phoneNumberUtil,
	) {
		$this->internalCache = new CappedMemoryCache();
	}

	/**
	 * @return string Provided phone number in E.164 format when it was a valid number
	 * @throws InvalidArgumentException When the phone number was invalid or no default region is set and the number doesn't start with a country code
	 */
	protected function parsePhoneNumber(string $input): string {
		$defaultRegion = $this->config->getSystemValueString('default_phone_region', '');

		if ($defaultRegion === '') {
			// When no default region is set, only +49… numbers are valid
			if (!str_starts_with($input, '+')) {
				throw new InvalidArgumentException(self::PROPERTY_PHONE);
			}

			$defaultRegion = 'EN';
		}

		$phoneNumber = $this->phoneNumberUtil->convertToStandardFormat($input, $defaultRegion);
		if ($phoneNumber !== null) {
			return $phoneNumber;
		}

		throw new InvalidArgumentException(self::PROPERTY_PHONE);
	}

	/**
	 * @throws InvalidArgumentException When the website did not have http(s) as protocol or the host name was empty
	 */
	protected function parseWebsite(string $input): string {
		$parts = parse_url($input);
		if (!isset($parts['scheme']) || ($parts['scheme'] !== 'https' && $parts['scheme'] !== 'http')) {
			throw new InvalidArgumentException(self::PROPERTY_WEBSITE);
		}

		if (!isset($parts['host']) || $parts['host'] === '') {
			throw new InvalidArgumentException(self::PROPERTY_WEBSITE);
		}

		return $input;
	}

	/**
	 * @param IAccountProperty[] $properties
	 */
	protected function testValueLengths(array $properties, bool $throwOnData = false): void {
		foreach ($properties as $property) {
			if (strlen($property->getValue()) > 2048) {
				if ($throwOnData) {
					throw new InvalidArgumentException($property->getName());
				} else {
					$property->setValue('');
				}
			}
		}
	}

	protected function testPropertyScope(IAccountProperty $property, array $allowedScopes, bool $throwOnData): void {
		if ($throwOnData && !in_array($property->getScope(), $allowedScopes, true)) {
			throw new InvalidArgumentException('scope');
		}

		if (
			$property->getScope() === self::SCOPE_PRIVATE
			&& in_array($property->getName(), [self::PROPERTY_DISPLAYNAME, self::PROPERTY_EMAIL])
		) {
			if ($throwOnData) {
				// v2-private is not available for these fields
				throw new InvalidArgumentException('scope');
			} else {
				// default to local
				$property->setScope(self::SCOPE_LOCAL);
			}
		} else {
			// migrate scope values to the new format
			// invalid scopes are mapped to a default value
			$property->setScope(AccountProperty::mapScopeToV2($property->getScope()));
		}
	}

	protected function sanitizePhoneNumberValue(IAccountProperty $property, bool $throwOnData = false): void {
		if ($property->getName() !== self::PROPERTY_PHONE) {
			if ($throwOnData) {
				throw new InvalidArgumentException(sprintf('sanitizePhoneNumberValue can only sanitize phone numbers, %s given', $property->getName()));
			}
			return;
		}
		if ($property->getValue() === '') {
			return;
		}
		try {
			$property->setValue($this->parsePhoneNumber($property->getValue()));
		} catch (InvalidArgumentException $e) {
			if ($throwOnData) {
				throw $e;
			}
			$property->setValue('');
		}
	}

	protected function sanitizeWebsite(IAccountProperty $property, bool $throwOnData = false): void {
		if ($property->getName() !== self::PROPERTY_WEBSITE) {
			if ($throwOnData) {
				throw new InvalidArgumentException(sprintf('sanitizeWebsite can only sanitize web domains, %s given', $property->getName()));
			}
		}
		try {
			$property->setValue($this->parseWebsite($property->getValue()));
		} catch (InvalidArgumentException $e) {
			if ($throwOnData) {
				throw $e;
			}
			$property->setValue('');
		}
	}

	protected function updateUser(IUser $user, array $data, ?array $oldUserData, bool $throwOnData = false): array {
		if ($oldUserData === null) {
			$oldUserData = $this->getUser($user, false);
		}

		$updated = true;

		if ($oldUserData !== $data) {
			$this->updateExistingUser($user, $data, $oldUserData);
		} else {
			// nothing needs to be done if new and old data set are the same
			$updated = false;
		}

		if ($updated) {
			$this->dispatcher->dispatchTyped(new UserUpdatedEvent(
				$user,
				$data,
			));
		}

		return $data;
	}

	/**
	 * delete user from accounts table
	 */
	public function deleteUser(IUser $user): void {
		$uid = $user->getUID();
		$query = $this->connection->getQueryBuilder();
		$query->delete($this->table)
			->where($query->expr()->eq('uid', $query->createNamedParameter($uid)))
			->execute();

		$this->deleteUserData($user);
	}

	/**
	 * delete user from accounts table
	 */
	public function deleteUserData(IUser $user): void {
		$uid = $user->getUID();
		$query = $this->connection->getQueryBuilder();
		$query->delete($this->dataTable)
			->where($query->expr()->eq('uid', $query->createNamedParameter($uid)))
			->execute();
	}

	/**
	 * get stored data from a given user
	 */
	protected function getUser(IUser $user, bool $insertIfNotExists = true): array {
		$uid = $user->getUID();
		$query = $this->connection->getQueryBuilder();
		$query->select('data')
			->from($this->table)
			->where($query->expr()->eq('uid', $query->createParameter('uid')))
			->setParameter('uid', $uid);
		$result = $query->executeQuery();
		$accountData = $result->fetchAll();
		$result->closeCursor();

		if (empty($accountData)) {
			$userData = $this->buildDefaultUserRecord($user);
			if ($insertIfNotExists) {
				$this->insertNewUser($user, $userData);
			}
			return $userData;
		}

		$userDataArray = $this->importFromJson($accountData[0]['data'], $uid);
		if ($userDataArray === null || $userDataArray === []) {
			return $this->buildDefaultUserRecord($user);
		}

		return $this->addMissingDefaultValues($userDataArray, $this->buildDefaultUserRecord($user));
	}

	public function searchUsers(string $property, array $values): array {
		// the value col is limited to 255 bytes. It is used for searches only.
		$values = array_map(function (string $value) {
			return Util::shortenMultibyteString($value, 255);
		}, $values);
		$chunks = array_chunk($values, 500);
		$query = $this->connection->getQueryBuilder();
		$query->select('*')
			->from($this->dataTable)
			->where($query->expr()->eq('name', $query->createNamedParameter($property)))
			->andWhere($query->expr()->in('value', $query->createParameter('values')));

		$matches = [];
		foreach ($chunks as $chunk) {
			$query->setParameter('values', $chunk, IQueryBuilder::PARAM_STR_ARRAY);
			$result = $query->executeQuery();

			while ($row = $result->fetch()) {
				$matches[$row['uid']] = $row['value'];
			}
			$result->closeCursor();
		}

		$result = array_merge($matches, $this->searchUsersForRelatedCollection($property, $values));

		return array_flip($result);
	}

	protected function searchUsersForRelatedCollection(string $property, array $values): array {
		return match ($property) {
			IAccountManager::PROPERTY_EMAIL => array_flip($this->searchUsers(IAccountManager::COLLECTION_EMAIL, $values)),
			default => [],
		};
	}

	/**
	 * check if we need to ask the server for email verification, if yes we create a cronjob
	 */
	protected function checkEmailVerification(IAccount $updatedAccount, array $oldData): void {
		try {
			$property = $updatedAccount->getProperty(self::PROPERTY_EMAIL);
		} catch (PropertyDoesNotExistException $e) {
			return;
		}

		$oldMailIndex = array_search(self::PROPERTY_EMAIL, array_column($oldData, 'name'), true);
		$oldMail = $oldMailIndex !== false ? $oldData[$oldMailIndex]['value'] : '';

		if ($oldMail !== $property->getValue()) {
			$this->jobList->add(
				VerifyUserData::class,
				[
					'verificationCode' => '',
					'data' => $property->getValue(),
					'type' => self::PROPERTY_EMAIL,
					'uid' => $updatedAccount->getUser()->getUID(),
					'try' => 0,
					'lastRun' => time()
				]
			);

			$property->setVerified(self::VERIFICATION_IN_PROGRESS);
		}
	}

	protected function checkLocalEmailVerification(IAccount $updatedAccount, array $oldData): void {
		$mailCollection = $updatedAccount->getPropertyCollection(self::COLLECTION_EMAIL);
		foreach ($mailCollection->getProperties() as $property) {
			if ($property->getLocallyVerified() !== self::NOT_VERIFIED) {
				continue;
			}
			if ($this->sendEmailVerificationEmail($updatedAccount->getUser(), $property->getValue())) {
				$property->setLocallyVerified(self::VERIFICATION_IN_PROGRESS);
			}
		}
	}

	protected function sendEmailVerificationEmail(IUser $user, string $email): bool {
		$ref = \substr(hash('sha256', $email), 0, 8);
		$key = $this->crypto->encrypt($email);
		$token = $this->verificationToken->create($user, 'verifyMail' . $ref, $email);

		$link = $this->urlGenerator->linkToRouteAbsolute(
			'provisioning_api.Verification.verifyMail',
			[
				'userId' => $user->getUID(),
				'token' => $token,
				'key' => $key
			]
		);

		$emailTemplate = $this->mailer->createEMailTemplate('core.EmailVerification', [
			'link' => $link,
		]);

		if (!$this->l10n) {
			$this->l10n = $this->l10nFactory->get('core');
		}

		$emailTemplate->setSubject($this->l10n->t('%s email verification', [$this->defaults->getName()]));
		$emailTemplate->addHeader();
		$emailTemplate->addHeading($this->l10n->t('Email verification'));

		$emailTemplate->addBodyText(
			htmlspecialchars($this->l10n->t('Click the following button to confirm your email.')),
			$this->l10n->t('Click the following link to confirm your email.')
		);

		$emailTemplate->addBodyButton(
			htmlspecialchars($this->l10n->t('Confirm your email')),
			$link,
			false
		);
		$emailTemplate->addFooter();

		try {
			$message = $this->mailer->createMessage();
			$message->setTo([$email => $user->getDisplayName()]);
			$message->setFrom([Util::getDefaultEmailAddress('verification-noreply') => $this->defaults->getName()]);
			$message->useTemplate($emailTemplate);
			$this->mailer->send($message);
		} catch (Exception $e) {
			// Log the exception and continue
			$this->logger->info('Failed to send verification mail', [
				'app' => 'core',
				'exception' => $e
			]);
			return false;
		}
		return true;
	}

	/**
	 * Make sure that all expected data are set
	 */
	protected function addMissingDefaultValues(array $userData, array $defaultUserData): array {
		foreach ($defaultUserData as $defaultDataItem) {
			// If property does not exist, initialize it
			$userDataIndex = array_search($defaultDataItem['name'], array_column($userData, 'name'));
			if ($userDataIndex === false) {
				$userData[] = $defaultDataItem;
				continue;
			}

			// Merge and extend default missing values
			$userData[$userDataIndex] = array_merge($defaultDataItem, $userData[$userDataIndex]);
		}

		return $userData;
	}

	protected function updateVerificationStatus(IAccount $updatedAccount, array $oldData): void {
		static $propertiesVerifiableByLookupServer = [
			self::PROPERTY_TWITTER,
			self::PROPERTY_FEDIVERSE,
			self::PROPERTY_WEBSITE,
			self::PROPERTY_EMAIL,
		];

		foreach ($propertiesVerifiableByLookupServer as $propertyName) {
			try {
				$property = $updatedAccount->getProperty($propertyName);
			} catch (PropertyDoesNotExistException $e) {
				continue;
			}
			$wasVerified = isset($oldData[$propertyName])
				&& isset($oldData[$propertyName]['verified'])
				&& $oldData[$propertyName]['verified'] === self::VERIFIED;
			if ((!isset($oldData[$propertyName])
					|| !isset($oldData[$propertyName]['value'])
					|| $property->getValue() !== $oldData[$propertyName]['value'])
				&& ($property->getVerified() !== self::NOT_VERIFIED
					|| $wasVerified)
			) {
				$property->setVerified(self::NOT_VERIFIED);
			}
		}
	}

	/**
	 * add new user to accounts table
	 */
	protected function insertNewUser(IUser $user, array $data): void {
		$uid = $user->getUID();
		$jsonEncodedData = $this->prepareJson($data);
		$query = $this->connection->getQueryBuilder();
		$query->insert($this->table)
			->values(
				[
					'uid' => $query->createNamedParameter($uid),
					'data' => $query->createNamedParameter($jsonEncodedData),
				]
			)
			->executeStatement();

		$this->deleteUserData($user);
		$this->writeUserData($user, $data);
	}

	protected function prepareJson(array $data): string {
		$preparedData = [];
		foreach ($data as $dataRow) {
			$propertyName = $dataRow['name'];
			unset($dataRow['name']);

			if (isset($dataRow['locallyVerified']) && $dataRow['locallyVerified'] === self::NOT_VERIFIED) {
				// do not write default value, save DB space
				unset($dataRow['locallyVerified']);
			}

			if (!$this->isCollection($propertyName)) {
				$preparedData[$propertyName] = $dataRow;
				continue;
			}
			if (!isset($preparedData[$propertyName])) {
				$preparedData[$propertyName] = [];
			}
			$preparedData[$propertyName][] = $dataRow;
		}
		return json_encode($preparedData);
	}

	protected function importFromJson(string $json, string $userId): ?array {
		$result = [];
		$jsonArray = json_decode($json, true);
		$jsonError = json_last_error();
		if ($jsonError !== JSON_ERROR_NONE) {
			$this->logger->critical(
				'User data of {uid} contained invalid JSON (error {json_error}), hence falling back to a default user record',
				[
					'uid' => $userId,
					'json_error' => $jsonError
				]
			);
			return null;
		}
		foreach ($jsonArray as $propertyName => $row) {
			if (!$this->isCollection($propertyName)) {
				$result[] = array_merge($row, ['name' => $propertyName]);
				continue;
			}
			foreach ($row as $singleRow) {
				$result[] = array_merge($singleRow, ['name' => $propertyName]);
			}
		}
		return $result;
	}

	/**
	 * Update existing user in accounts table
	 */
	protected function updateExistingUser(IUser $user, array $data, array $oldData): void {
		$uid = $user->getUID();
		$jsonEncodedData = $this->prepareJson($data);
		$query = $this->connection->getQueryBuilder();
		$query->update($this->table)
			->set('data', $query->createNamedParameter($jsonEncodedData))
			->where($query->expr()->eq('uid', $query->createNamedParameter($uid)))
			->executeStatement();

		$this->deleteUserData($user);
		$this->writeUserData($user, $data);
	}

	protected function writeUserData(IUser $user, array $data): void {
		$query = $this->connection->getQueryBuilder();
		$query->insert($this->dataTable)
			->values(
				[
					'uid' => $query->createNamedParameter($user->getUID()),
					'name' => $query->createParameter('name'),
					'value' => $query->createParameter('value'),
				]
			);
		$this->writeUserDataProperties($query, $data);
	}

	protected function writeUserDataProperties(IQueryBuilder $query, array $data): void {
		foreach ($data as $property) {
			if ($property['name'] === self::PROPERTY_AVATAR) {
				continue;
			}

			// the value col is limited to 255 bytes. It is used for searches only.
			$value = $property['value'] ? Util::shortenMultibyteString($property['value'], 255) : '';

			$query->setParameter('name', $property['name'])
				->setParameter('value', $value);
			$query->executeStatement();
		}
	}

	/**
	 * build default user record in case not data set exists yet
	 */
	protected function buildDefaultUserRecord(IUser $user): array {
		$scopes = array_merge(self::DEFAULT_SCOPES, array_filter($this->config->getSystemValue('account_manager.default_property_scope', []), static function (string $scope, string $property) {
			return in_array($property, self::ALLOWED_PROPERTIES, true) && in_array($scope, self::ALLOWED_SCOPES, true);
		}, ARRAY_FILTER_USE_BOTH));

		return [
			[
				'name' => self::PROPERTY_DISPLAYNAME,
				'value' => $user->getDisplayName(),
				// Display name must be at least SCOPE_LOCAL
				'scope' => $scopes[self::PROPERTY_DISPLAYNAME] === self::SCOPE_PRIVATE ? self::SCOPE_LOCAL : $scopes[self::PROPERTY_DISPLAYNAME],
				'verified' => self::NOT_VERIFIED,
			],

			[
				'name' => self::PROPERTY_ADDRESS,
				'value' => '',
				'scope' => $scopes[self::PROPERTY_ADDRESS],
				'verified' => self::NOT_VERIFIED,
			],

			[
				'name' => self::PROPERTY_WEBSITE,
				'value' => '',
				'scope' => $scopes[self::PROPERTY_WEBSITE],
				'verified' => self::NOT_VERIFIED,
			],

			[
				'name' => self::PROPERTY_EMAIL,
				'value' => $user->getEMailAddress(),
				// Email must be at least SCOPE_LOCAL
				'scope' => $scopes[self::PROPERTY_EMAIL] === self::SCOPE_PRIVATE ? self::SCOPE_LOCAL : $scopes[self::PROPERTY_EMAIL],
				'verified' => self::NOT_VERIFIED,
			],

			[
				'name' => self::PROPERTY_AVATAR,
				'scope' => $scopes[self::PROPERTY_AVATAR],
			],

			[
				'name' => self::PROPERTY_PHONE,
				'value' => '',
				'scope' => $scopes[self::PROPERTY_PHONE],
				'verified' => self::NOT_VERIFIED,
			],

			[
				'name' => self::PROPERTY_TWITTER,
				'value' => '',
				'scope' => $scopes[self::PROPERTY_TWITTER],
				'verified' => self::NOT_VERIFIED,
			],

			[
				'name' => self::PROPERTY_FEDIVERSE,
				'value' => '',
				'scope' => $scopes[self::PROPERTY_FEDIVERSE],
				'verified' => self::NOT_VERIFIED,
			],

			[
				'name' => self::PROPERTY_ORGANISATION,
				'value' => '',
				'scope' => $scopes[self::PROPERTY_ORGANISATION],
			],

			[
				'name' => self::PROPERTY_ROLE,
				'value' => '',
				'scope' => $scopes[self::PROPERTY_ROLE],
			],

			[
				'name' => self::PROPERTY_HEADLINE,
				'value' => '',
				'scope' => $scopes[self::PROPERTY_HEADLINE],
			],

			[
				'name' => self::PROPERTY_BIOGRAPHY,
				'value' => '',
				'scope' => $scopes[self::PROPERTY_BIOGRAPHY],
			],

			[
				'name' => self::PROPERTY_PROFILE_ENABLED,
				'value' => $this->isProfileEnabledByDefault($this->config) ? '1' : '0',
			],
		];
	}

	private function arrayDataToCollection(IAccount $account, array $data): IAccountPropertyCollection {
		$collection = $account->getPropertyCollection($data['name']);

		$p = new AccountProperty(
			$data['name'],
			$data['value'] ?? '',
			$data['scope'] ?? self::SCOPE_LOCAL,
			$data['verified'] ?? self::NOT_VERIFIED,
			''
		);
		$p->setLocallyVerified($data['locallyVerified'] ?? self::NOT_VERIFIED);
		$collection->addProperty($p);

		return $collection;
	}

	private function parseAccountData(IUser $user, $data): Account {
		$account = new Account($user);
		foreach ($data as $accountData) {
			if ($this->isCollection($accountData['name'])) {
				$account->setPropertyCollection($this->arrayDataToCollection($account, $accountData));
			} else {
				$account->setProperty($accountData['name'], $accountData['value'] ?? '', $accountData['scope'] ?? self::SCOPE_LOCAL, $accountData['verified'] ?? self::NOT_VERIFIED);
				if (isset($accountData['locallyVerified'])) {
					$property = $account->getProperty($accountData['name']);
					$property->setLocallyVerified($accountData['locallyVerified']);
				}
			}
		}
		return $account;
	}

	public function getAccount(IUser $user): IAccount {
		$cached = $this->internalCache->get($user->getUID());
		if ($cached !== null) {
			return $cached;
		}
		$account = $this->parseAccountData($user, $this->getUser($user));
		if ($user->getBackend() instanceof IGetDisplayNameBackend) {
			$property = $account->getProperty(self::PROPERTY_DISPLAYNAME);
			$account->setProperty(self::PROPERTY_DISPLAYNAME, $user->getDisplayName(), $property->getScope(), $property->getVerified());
		}
		$this->internalCache->set($user->getUID(), $account);
		return $account;
	}

	public function updateAccount(IAccount $account): void {
		$this->testValueLengths(iterator_to_array($account->getAllProperties()), true);
		try {
			$property = $account->getProperty(self::PROPERTY_PHONE);
			$this->sanitizePhoneNumberValue($property);
		} catch (PropertyDoesNotExistException $e) {
			//  valid case, nothing to do
		}

		try {
			$property = $account->getProperty(self::PROPERTY_WEBSITE);
			$this->sanitizeWebsite($property);
		} catch (PropertyDoesNotExistException $e) {
			//  valid case, nothing to do
		}

		foreach ($account->getAllProperties() as $property) {
			$this->testPropertyScope($property, self::ALLOWED_SCOPES, true);
		}

		$oldData = $this->getUser($account->getUser(), false);
		$this->updateVerificationStatus($account, $oldData);
		$this->checkEmailVerification($account, $oldData);
		$this->checkLocalEmailVerification($account, $oldData);

		$data = [];
		foreach ($account->getAllProperties() as $property) {
			/** @var IAccountProperty $property */
			$data[] = [
				'name' => $property->getName(),
				'value' => $property->getValue(),
				'scope' => $property->getScope(),
				'verified' => $property->getVerified(),
				'locallyVerified' => $property->getLocallyVerified(),
			];
		}

		$this->updateUser($account->getUser(), $data, $oldData, true);
		$this->internalCache->set($account->getUser()->getUID(), $account);
	}
}
