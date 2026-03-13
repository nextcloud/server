<?php

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OC\Accounts;

use InvalidArgumentException;
use JsonException;
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
use OCP\Http\Client\IClientService;
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
use Throwable;

use function array_flip;
use function iterator_to_array;
use function json_decode;
use function json_encode;

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

	private readonly string $table;
	private readonly string $dataTable;
	private ?IL10N $l10n = null;
	private readonly CappedMemoryCache $internalCache;

	public const DEFAULT_SCOPES = [
		self::PROPERTY_ADDRESS => self::SCOPE_LOCAL,
		self::PROPERTY_AVATAR => self::SCOPE_FEDERATED,
		self::PROPERTY_BIOGRAPHY => self::SCOPE_LOCAL,
		self::PROPERTY_BIRTHDATE => self::SCOPE_LOCAL,
		self::PROPERTY_DISPLAYNAME => self::SCOPE_FEDERATED,
		self::PROPERTY_EMAIL => self::SCOPE_FEDERATED,
		self::PROPERTY_FEDIVERSE => self::SCOPE_LOCAL,
		self::PROPERTY_HEADLINE => self::SCOPE_LOCAL,
		self::PROPERTY_ORGANISATION => self::SCOPE_LOCAL,
		self::PROPERTY_PHONE => self::SCOPE_LOCAL,
		self::PROPERTY_PRONOUNS => self::SCOPE_FEDERATED,
		self::PROPERTY_ROLE => self::SCOPE_LOCAL,
		self::PROPERTY_TWITTER => self::SCOPE_LOCAL,
		self::PROPERTY_BLUESKY => self::SCOPE_LOCAL,
		self::PROPERTY_WEBSITE => self::SCOPE_LOCAL,
	];

	public function __construct(
		private readonly IDBConnection $connection,
		private readonly IConfig $config,
		private readonly IEventDispatcher $dispatcher,
		private readonly IJobList $jobList,
		private readonly LoggerInterface $logger,
		private readonly IVerificationToken $verificationToken,
		private readonly IMailer $mailer,
		private readonly Defaults $defaults,
		private readonly IFactory $l10nFactory,
		private readonly IURLGenerator $urlGenerator,
		private readonly ICrypto $crypto,
		private readonly IPhoneNumberUtil $phoneNumberUtil,
		private readonly IClientService $clientService,
	) {
		$this->table = 'accounts';
		$this->dataTable = 'accounts_data';
		$this->internalCache = new CappedMemoryCache();
	}

	/**
	 * @param IAccountProperty[] $properties
	 */
	protected function testValueLengths(array $properties, bool $throwOnData = false): void {
		foreach ($properties as $property) {
			if (strlen($property->getValue()) > 2048) {
				if ($throwOnData) {
					throw new InvalidArgumentException($property->getName());
				}
				$property->setValue('');
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
			}
			// default to local
			$property->setScope(self::SCOPE_LOCAL);
		} else {
			// migrate scope values to the new format
			// invalid scopes are mapped to a default value
			$property->setScope(AccountProperty::mapScopeToV2($property->getScope()));
		}
	}

	protected function updateUser(IUser $user, array $data, ?array $oldUserData, bool $throwOnData = false): array {
		$oldUserData ??= $this->getUser($user, false);
		$updated = true;

		if ($oldUserData !== $data) {
			$this->updateExistingUser($user, $data, $oldUserData);
		} else {
			// nothing needs to be done if new and old data set are the same
			$updated = false;
		}

		if ($updated) {
			$this->dispatcher->dispatchTyped(new UserUpdatedEvent($user, $data));
		}

		return $data;
	}

	public function deleteUser(IUser $user): void {
		$uid = $user->getUID();
		$query = $this->connection->getQueryBuilder();
		$query->delete($this->table)
			->where($query->expr()->eq('uid', $query->createNamedParameter($uid)))
			->executeStatement();

		$this->deleteUserData($user);
	}

	public function deleteUserData(IUser $user): void {
		$uid = $user->getUID();
		$query = $this->connection->getQueryBuilder();
		$query->delete($this->dataTable)
			->where($query->expr()->eq('uid', $query->createNamedParameter($uid)))
			->executeStatement();
	}

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
		if (empty($userDataArray)) {
			return $this->buildDefaultUserRecord($user);
		}

		return $this->addMissingDefaultValues($userDataArray, $this->buildDefaultUserRecord($user));
	}

	public function searchUsers(string $property, array $values): array {
		// the value col is limited to 255 bytes. It is used for searches only.
		$values = array_map(fn (string $value) => Util::shortenMultibyteString($value, 255), $values);
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

	protected function checkEmailVerification(IAccount $updatedAccount, array $oldData): void {
		try {
			$property = $updatedAccount->getProperty(self::PROPERTY_EMAIL);
		} catch (PropertyDoesNotExistException) {
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

		$this->l10n ??= $this->l10nFactory->get('core');

		$emailTemplate = $this->mailer->createEMailTemplate('core.EmailVerification', [
			'link' => $link,
		]);

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
		} catch (Throwable $e) {
			$this->logger->info('Failed to send verification mail', [
				'app' => 'core',
				'exception' => $e
			]);
			return false;
		}
		return true;
	}

	protected function addMissingDefaultValues(array $userData, array $defaultUserData): array {
		foreach ($defaultUserData as $defaultDataItem) {
			// If property does not exist, initialize it
			$userDataIndex = array_search($defaultDataItem['name'], array_column($userData, 'name'), true);
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
			} catch (PropertyDoesNotExistException) {
				continue;
			}

			$wasVerified = isset($oldData[$propertyName]['verified']) && $oldData[$propertyName]['verified'] === self::VERIFIED;

			if ((!isset($oldData[$propertyName]['value']) || $property->getValue() !== $oldData[$propertyName]['value'])
				&& ($property->getVerified() !== self::NOT_VERIFIED || $wasVerified)
			) {
				$property->setVerified(self::NOT_VERIFIED);
			}
		}
	}

	protected function insertNewUser(IUser $user, array $data): void {
		$uid = $user->getUID();
		$jsonEncodedData = $this->prepareJson($data);
		$query = $this->connection->getQueryBuilder();
		$query->insert($this->table)
			->values([
				'uid' => $query->createNamedParameter($uid),
				'data' => $query->createNamedParameter($jsonEncodedData),
			])
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

			$preparedData[$propertyName] ??= [];
			$preparedData[$propertyName][] = $dataRow;
		}
		return json_encode($preparedData, JSON_THROW_ON_ERROR);
	}

	protected function importFromJson(string $json, string $userId): ?array {
		$result = [];
		try {
			$jsonArray = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
		} catch (JsonException $e) {
			$this->logger->critical(
				'User data of {uid} contained invalid JSON (error {json_error}), hence falling back to a default user record',
				[
					'uid' => $userId,
					'json_error' => $e->getMessage()
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
			->values([
				'uid' => $query->createNamedParameter($user->getUID()),
				'name' => $query->createParameter('name'),
				'value' => $query->createParameter('value'),
			]);

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
				->setParameter('value', $value)
				->executeStatement();
		}
	}

	protected function buildDefaultUserRecord(IUser $user): array {
		$scopes = array_merge(self::DEFAULT_SCOPES, array_filter(
			$this->config->getSystemValue('account_manager.default_property_scope', []),
			static fn (string $scope, string $property) => in_array($property, self::ALLOWED_PROPERTIES, true) && in_array($scope, self::ALLOWED_SCOPES, true),
			ARRAY_FILTER_USE_BOTH
		));

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
				'verified' => self::NOT_VERIFIED
			],
			[
				'name' => self::PROPERTY_WEBSITE,
				'value' => '',
				'scope' => $scopes[self::PROPERTY_WEBSITE],
				'verified' => self::NOT_VERIFIED
			],
			[
				'name' => self::PROPERTY_EMAIL,
				'value' => $user->getEMailAddress(),
				'scope' => $scopes[self::PROPERTY_EMAIL] === self::SCOPE_PRIVATE ? self::SCOPE_LOCAL : $scopes[self::PROPERTY_EMAIL],
				'verified' => self::NOT_VERIFIED
			],
			[
				'name' => self::PROPERTY_AVATAR,
				'scope' => $scopes[self::PROPERTY_AVATAR]
			],
			[
				'name' => self::PROPERTY_PHONE,
				'value' => '',
				'scope' => $scopes[self::PROPERTY_PHONE],
				'verified' => self::NOT_VERIFIED
			],
			[
				'name' => self::PROPERTY_TWITTER,
				'value' => '',
				'scope' => $scopes[self::PROPERTY_TWITTER],
				'verified' => self::NOT_VERIFIED
			],
			[
				'name' => self::PROPERTY_BLUESKY,
				'value' => '',
				'scope' => $scopes[self::PROPERTY_BLUESKY],
				'verified' => self::NOT_VERIFIED
			],
			[
				'name' => self::PROPERTY_FEDIVERSE,
				'value' => '',
				'scope' => $scopes[self::PROPERTY_FEDIVERSE],
				'verified' => self::NOT_VERIFIED
			],
			[
				'name' => self::PROPERTY_ORGANISATION,
				'value' => '',
				'scope' => $scopes[self::PROPERTY_ORGANISATION]
			],
			[
				'name' => self::PROPERTY_ROLE,
				'value' => '',
				'scope' => $scopes[self::PROPERTY_ROLE]
			],
			[
				'name' => self::PROPERTY_HEADLINE,
				'value' => '',
				'scope' => $scopes[self::PROPERTY_HEADLINE]
			],
			[
				'name' => self::PROPERTY_BIOGRAPHY,
				'value' => '',
				'scope' => $scopes[self::PROPERTY_BIOGRAPHY]
			],
			[
				'name' => self::PROPERTY_BIRTHDATE,
				'value' => '',
				'scope' => $scopes[self::PROPERTY_BIRTHDATE]
			],
			[
				'name' => self::PROPERTY_PROFILE_ENABLED,
				'value' => $this->isProfileEnabledByDefault($this->config) ? '1' : '0'
			],
			[
				'name' => self::PROPERTY_PRONOUNS,
				'value' => '',
				'scope' => $scopes[self::PROPERTY_PRONOUNS]
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

	protected function sanitizePropertyPhoneNumber(IAccountProperty $property): void {
		$defaultRegion = $this->config->getSystemValueString('default_phone_region', '');

		if ($defaultRegion === '') {
			if (!str_starts_with($property->getValue(), '+')) {
				throw new InvalidArgumentException(self::PROPERTY_PHONE);
			}
			$defaultRegion = 'EN';
		}

		$phoneNumber = $this->phoneNumberUtil->convertToStandardFormat($property->getValue(), $defaultRegion);
		if ($phoneNumber === null) {
			throw new InvalidArgumentException(self::PROPERTY_PHONE);
		}
		$property->setValue($phoneNumber);
	}

	private function sanitizePropertyWebsite(IAccountProperty $property): void {
		$parts = parse_url($property->getValue());
		if (!isset($parts['scheme']) || !in_array($parts['scheme'], ['https', 'http'], true)) {
			throw new InvalidArgumentException(self::PROPERTY_WEBSITE);
		}

		if (empty($parts['host'])) {
			throw new InvalidArgumentException(self::PROPERTY_WEBSITE);
		}
	}

	private function sanitizePropertyTwitter(IAccountProperty $property): void {
		if ($property->getName() === self::PROPERTY_TWITTER) {
			if (preg_match('/^@?([a-zA-Z0-9_]{2,15})$/', $property->getValue(), $matches) !== 1) {
				throw new InvalidArgumentException(self::PROPERTY_TWITTER);
			}
			$property->setValue($matches[1]);
		}
	}

	private function validateBlueSkyHandle(string $text): bool {
		if ($text === '') {
			return true;
		}

		$lowerText = strtolower($text);

		if ($lowerText === 'bsky.social') {
			return false;
		}

		if (str_ends_with($lowerText, '.bsky.social')) {
			$parts = explode('.', $lowerText);
			if (count($parts) !== 3 || $parts[1] !== 'bsky' || $parts[2] !== 'social') {
				return false;
			}
			return preg_match('/^[a-z0-9][a-z0-9-]{2,17}$/', $parts[0]) === 1;
		}

		return filter_var($text, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) !== false;
	}

	private function sanitizePropertyBluesky(IAccountProperty $property): void {
		if ($property->getName() === self::PROPERTY_BLUESKY) {
			$cleanValue = ltrim(trim($property->getValue()), '@');
			if (!$this->validateBlueSkyHandle($cleanValue)) {
				throw new InvalidArgumentException(self::PROPERTY_BLUESKY);
			}
			$property->setValue($cleanValue);
		}
	}

	private function sanitizePropertyFediverse(IAccountProperty $property): void {
		if ($property->getName() === self::PROPERTY_FEDIVERSE) {
			if (preg_match('/^@?([^@\s\/\\\\]+)@([^\s\/\\\\]+)$/', trim($property->getValue()), $matches) !== 1) {
				throw new InvalidArgumentException(self::PROPERTY_FEDIVERSE);
			}

			[, $username, $instance] = $matches;
			if (filter_var($instance, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) !== $instance) {
				throw new InvalidArgumentException(self::PROPERTY_FEDIVERSE);
			}

			if ($this->config->getSystemValueBool('has_internet_connection', true)) {
				$client = $this->clientService->newClient();

				try {
					$response = $client->get("https://{$instance}/.well-known/webfinger?resource=acct:{$username}@{$instance}");
					$data = $response->getBody();
					if (is_resource($data)) {
						$data = stream_get_contents($data);
					}

					$decoded = json_decode($data, true, 512, JSON_THROW_ON_ERROR);

					if (!is_array($decoded) || ($decoded['subject'] ?? '') !== "acct:{$username}@{$instance}") {
						throw new InvalidArgumentException();
					}

					if (isset($decoded['links']) && is_array($decoded['links'])) {
						$found = false;
						foreach ($decoded['links'] as $link) {
							if (isset($link['type']) && in_array($link['type'], [
								'application/activity+json',
								'application/ld+json; profile="https://www.w3.org/ns/activitystreams"'
							], true)) {
								$found = true;
								break;
							}
						}
						if (!$found) {
							throw new InvalidArgumentException();
						}
					}
				} catch (InvalidArgumentException) {
					throw new InvalidArgumentException(self::PROPERTY_FEDIVERSE);
				} catch (Throwable $error) {
					$this->logger->error('Could not verify fediverse account', ['exception' => $error, 'instance' => $instance]);
					throw new InvalidArgumentException(self::PROPERTY_FEDIVERSE);
				}
			}

			$property->setValue("$username@$instance");
		}
	}

	public function updateAccount(IAccount $account): void {
		$this->testValueLengths(iterator_to_array($account->getAllProperties()), true);

		$sanitizers = [
			self::PROPERTY_PHONE => $this->sanitizePropertyPhoneNumber(...),
			self::PROPERTY_WEBSITE => $this->sanitizePropertyWebsite(...),
			self::PROPERTY_TWITTER => $this->sanitizePropertyTwitter(...),
			self::PROPERTY_BLUESKY => $this->sanitizePropertyBluesky(...),
			self::PROPERTY_FEDIVERSE => $this->sanitizePropertyFediverse(...),
		];

		foreach ($sanitizers as $propertyName => $sanitizer) {
			try {
				$property = $account->getProperty($propertyName);
				if ($property->getValue() !== '') {
					$sanitizer($property);
				}
			} catch (PropertyDoesNotExistException) {
			}
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
