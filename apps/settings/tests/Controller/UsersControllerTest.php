<?php
/**
 * SPDX-FileCopyrightText: 2019-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2014-2015 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Settings\Tests\Controller;

use OC\Accounts\AccountManager;
use OC\Encryption\Exceptions\ModuleDoesNotExistsException;
use OC\ForbiddenException;
use OC\Group\Manager;
use OC\KnownUser\KnownUserService;
use OC\User\Manager as UserManager;
use OCA\Settings\Controller\UsersController;
use OCP\Accounts\IAccount;
use OCP\Accounts\IAccountManager;
use OCP\Accounts\IAccountProperty;
use OCP\Accounts\PropertyDoesNotExistException;
use OCP\App\IAppManager;
use OCP\AppFramework\Http;
use OCP\AppFramework\Services\IInitialState;
use OCP\BackgroundJob\IJobList;
use OCP\Encryption\IEncryptionModule;
use OCP\Encryption\IManager;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IConfig;
use OCP\IGroupManager;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;
use OCP\L10N\IFactory;
use OCP\Mail\IMailer;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @group DB
 *
 * @package Tests\Settings\Controller
 */
class UsersControllerTest extends \Test\TestCase {
	/** @var IGroupManager|\PHPUnit\Framework\MockObject\MockObject */
	private $groupManager;
	/** @var UserManager|\PHPUnit\Framework\MockObject\MockObject */
	private $userManager;
	/** @var IUserSession|\PHPUnit\Framework\MockObject\MockObject */
	private $userSession;
	/** @var IConfig|\PHPUnit\Framework\MockObject\MockObject */
	private $config;
	/** @var IMailer|\PHPUnit\Framework\MockObject\MockObject */
	private $mailer;
	/** @var IFactory|\PHPUnit\Framework\MockObject\MockObject */
	private $l10nFactory;
	/** @var IAppManager|\PHPUnit\Framework\MockObject\MockObject */
	private $appManager;
	/** @var IL10N|\PHPUnit\Framework\MockObject\MockObject */
	private $l;
	/** @var AccountManager|\PHPUnit\Framework\MockObject\MockObject */
	private $accountManager;
	/** @var IJobList | \PHPUnit\Framework\MockObject\MockObject */
	private $jobList;
	/** @var \OC\Security\IdentityProof\Manager|\PHPUnit\Framework\MockObject\MockObject */
	private $securityManager;
	/** @var IManager|\PHPUnit\Framework\MockObject\MockObject */
	private $encryptionManager;
	/** @var KnownUserService|\PHPUnit\Framework\MockObject\MockObject */
	private $knownUserService;
	/** @var IEncryptionModule|\PHPUnit\Framework\MockObject\MockObject */
	private $encryptionModule;
	/** @var IEventDispatcher|\PHPUnit\Framework\MockObject\MockObject */
	private $dispatcher;
	/** @var IInitialState|\PHPUnit\Framework\MockObject\MockObject */
	private $initialState;

	protected function setUp(): void {
		parent::setUp();

		$this->userManager = $this->createMock(UserManager::class);
		$this->groupManager = $this->createMock(Manager::class);
		$this->userSession = $this->createMock(IUserSession::class);
		$this->config = $this->createMock(IConfig::class);
		$this->l = $this->createMock(IL10N::class);
		$this->mailer = $this->createMock(IMailer::class);
		$this->l10nFactory = $this->createMock(IFactory::class);
		$this->appManager = $this->createMock(IAppManager::class);
		$this->accountManager = $this->createMock(AccountManager::class);
		$this->securityManager = $this->getMockBuilder(\OC\Security\IdentityProof\Manager::class)->disableOriginalConstructor()->getMock();
		$this->jobList = $this->createMock(IJobList::class);
		$this->encryptionManager = $this->createMock(IManager::class);
		$this->knownUserService = $this->createMock(KnownUserService::class);
		$this->dispatcher = $this->createMock(IEventDispatcher::class);
		$this->initialState = $this->createMock(IInitialState::class);

		$this->l->method('t')
			->willReturnCallback(function ($text, $parameters = []) {
				return vsprintf($text, $parameters);
			});

		$this->encryptionModule = $this->createMock(IEncryptionModule::class);
		$this->encryptionManager->expects($this->any())->method('getEncryptionModules')
			->willReturn(['encryptionModule' => ['callback' => function () {
				return $this->encryptionModule;
			}]]);
	}

	/**
	 * @param bool $isAdmin
	 * @return UsersController | \PHPUnit\Framework\MockObject\MockObject
	 */
	protected function getController($isAdmin = false, $mockedMethods = []) {
		$this->groupManager->expects($this->any())
			->method('isAdmin')
			->willReturn($isAdmin);

		if (empty($mockedMethods)) {
			return new UsersController(
				'settings',
				$this->createMock(IRequest::class),
				$this->userManager,
				$this->groupManager,
				$this->userSession,
				$this->config,
				$this->l,
				$this->mailer,
				$this->l10nFactory,
				$this->appManager,
				$this->accountManager,
				$this->securityManager,
				$this->jobList,
				$this->encryptionManager,
				$this->knownUserService,
				$this->dispatcher,
				$this->initialState,
			);
		} else {
			return $this->getMockBuilder(UsersController::class)
				->setConstructorArgs(
					[
						'settings',
						$this->createMock(IRequest::class),
						$this->userManager,
						$this->groupManager,
						$this->userSession,
						$this->config,
						$this->l,
						$this->mailer,
						$this->l10nFactory,
						$this->appManager,
						$this->accountManager,
						$this->securityManager,
						$this->jobList,
						$this->encryptionManager,
						$this->knownUserService,
						$this->dispatcher,
						$this->initialState,
					]
				)->onlyMethods($mockedMethods)->getMock();
		}
	}

	protected function buildPropertyMock(string $name, string $value, string $scope, string $verified = IAccountManager::VERIFIED): MockObject {
		$property = $this->createMock(IAccountProperty::class);
		$property->expects($this->any())
			->method('getName')
			->willReturn($name);
		$property->expects($this->any())
			->method('getValue')
			->willReturn($value);
		$property->expects($this->any())
			->method('getScope')
			->willReturn($scope);
		$property->expects($this->any())
			->method('getVerified')
			->willReturn($verified);

		return $property;
	}

	protected function getDefaultAccountMock(bool $useDefaultValues = true): MockObject {
		$propertyMocks = [
			IAccountManager::PROPERTY_DISPLAYNAME => $this->buildPropertyMock(
				IAccountManager::PROPERTY_DISPLAYNAME,
				'Default display name',
				IAccountManager::SCOPE_FEDERATED,
			),
			IAccountManager::PROPERTY_ADDRESS => $this->buildPropertyMock(
				IAccountManager::PROPERTY_ADDRESS,
				'Default address',
				IAccountManager::SCOPE_LOCAL,
			),
			IAccountManager::PROPERTY_WEBSITE => $this->buildPropertyMock(
				IAccountManager::PROPERTY_WEBSITE,
				'Default website',
				IAccountManager::SCOPE_LOCAL,
			),
			IAccountManager::PROPERTY_EMAIL => $this->buildPropertyMock(
				IAccountManager::PROPERTY_EMAIL,
				'Default email',
				IAccountManager::SCOPE_FEDERATED,
			),
			IAccountManager::PROPERTY_AVATAR => $this->buildPropertyMock(
				IAccountManager::PROPERTY_AVATAR,
				'',
				IAccountManager::SCOPE_FEDERATED,
			),
			IAccountManager::PROPERTY_PHONE => $this->buildPropertyMock(
				IAccountManager::PROPERTY_PHONE,
				'Default phone',
				IAccountManager::SCOPE_LOCAL,
			),
			IAccountManager::PROPERTY_TWITTER => $this->buildPropertyMock(
				IAccountManager::PROPERTY_TWITTER,
				'Default twitter',
				IAccountManager::SCOPE_LOCAL,
			),
			IAccountManager::PROPERTY_FEDIVERSE => $this->buildPropertyMock(
				IAccountManager::PROPERTY_FEDIVERSE,
				'Default fediverse',
				IAccountManager::SCOPE_LOCAL,
			),
			IAccountManager::PROPERTY_BIRTHDATE => $this->buildPropertyMock(
				IAccountManager::PROPERTY_BIRTHDATE,
				'Default birthdate',
				IAccountManager::SCOPE_LOCAL,
			),
			IAccountManager::PROPERTY_PRONOUNS => $this->buildPropertyMock(
				IAccountManager::PROPERTY_PRONOUNS,
				'Default pronouns',
				IAccountManager::SCOPE_LOCAL,
			),
		];

		$account = $this->createMock(IAccount::class);
		$account->expects($this->any())
			->method('getProperty')
			->willReturnCallback(function (string $propertyName) use ($propertyMocks) {
				if (isset($propertyMocks[$propertyName])) {
					return $propertyMocks[$propertyName];
				}
				throw new PropertyDoesNotExistException($propertyName);
			});
		$account->expects($this->any())
			->method('getProperties')
			->willReturn($propertyMocks);

		return $account;
	}

	/**
	 * @dataProvider dataTestSetUserSettings
	 *
	 * @param string $email
	 * @param bool $validEmail
	 * @param $expectedStatus
	 */
	public function testSetUserSettings($email, $validEmail, $expectedStatus): void {
		$controller = $this->getController(false, ['saveUserSettings']);
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('johndoe');

		$this->userSession->method('getUser')->willReturn($user);

		if (!empty($email) && $validEmail) {
			$this->mailer->expects($this->once())->method('validateMailAddress')
				->willReturn($validEmail);
		}

		$saveData = (!empty($email) && $validEmail) || empty($email);

		if ($saveData) {
			$this->accountManager->expects($this->once())
				->method('getAccount')
				->with($user)
				->willReturn($this->getDefaultAccountMock());

			$controller->expects($this->once())
				->method('saveUserSettings');
		} else {
			$controller->expects($this->never())->method('saveUserSettings');
		}

		$result = $controller->setUserSettings(
			AccountManager::SCOPE_FEDERATED,
			'displayName',
			AccountManager::SCOPE_FEDERATED,
			'47658468',
			AccountManager::SCOPE_FEDERATED,
			$email,
			AccountManager::SCOPE_FEDERATED,
			'nextcloud.com',
			AccountManager::SCOPE_FEDERATED,
			'street and city',
			AccountManager::SCOPE_FEDERATED,
			'@nextclouders',
			AccountManager::SCOPE_FEDERATED,
			'@nextclouders',
			AccountManager::SCOPE_FEDERATED,
			'2020-01-01',
			AccountManager::SCOPE_FEDERATED,
			'they/them',
			AccountManager::SCOPE_FEDERATED,
		);

		$this->assertSame($expectedStatus, $result->getStatus());
	}

	public function dataTestSetUserSettings() {
		return [
			['', true, Http::STATUS_OK],
			['', false, Http::STATUS_OK],
			['example.com', false, Http::STATUS_UNPROCESSABLE_ENTITY],
			['john@example.com', true, Http::STATUS_OK],
		];
	}

	public function testSetUserSettingsWhenUserDisplayNameChangeNotAllowed(): void {
		$controller = $this->getController(false, ['saveUserSettings']);

		$avatarScope = IAccountManager::SCOPE_PUBLISHED;
		$displayName = 'Display name';
		$displayNameScope = IAccountManager::SCOPE_PUBLISHED;
		$phone = '47658468';
		$phoneScope = IAccountManager::SCOPE_PUBLISHED;
		$email = 'john@example.com';
		$emailScope = IAccountManager::SCOPE_PUBLISHED;
		$website = 'nextcloud.com';
		$websiteScope = IAccountManager::SCOPE_PUBLISHED;
		$address = 'street and city';
		$addressScope = IAccountManager::SCOPE_PUBLISHED;
		$twitter = '@nextclouders';
		$twitterScope = IAccountManager::SCOPE_PUBLISHED;
		$fediverse = '@nextclouders@floss.social';
		$fediverseScope = IAccountManager::SCOPE_PUBLISHED;
		$birtdate = '2020-01-01';
		$birthdateScope = IAccountManager::SCOPE_PUBLISHED;
		$pronouns = 'she/her';
		$pronounsScope = IAccountManager::SCOPE_PUBLISHED;

		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('johndoe');

		$this->userSession->method('getUser')->willReturn($user);

		/** @var MockObject|IAccount $userAccount */
		$userAccount = $this->getDefaultAccountMock();
		$this->accountManager->expects($this->once())
			->method('getAccount')
			->with($user)
			->willReturn($userAccount);

		/** @var MockObject|IAccountProperty $avatarProperty */
		$avatarProperty = $userAccount->getProperty(IAccountManager::PROPERTY_AVATAR);
		$avatarProperty->expects($this->atLeastOnce())
			->method('setScope')
			->with($avatarScope)
			->willReturnSelf();

		/** @var MockObject|IAccountProperty $avatarProperty */
		$avatarProperty = $userAccount->getProperty(IAccountManager::PROPERTY_ADDRESS);
		$avatarProperty->expects($this->atLeastOnce())
			->method('setScope')
			->with($addressScope)
			->willReturnSelf();
		$avatarProperty->expects($this->atLeastOnce())
			->method('setValue')
			->with($address)
			->willReturnSelf();

		/** @var MockObject|IAccountProperty $emailProperty */
		$emailProperty = $userAccount->getProperty(IAccountManager::PROPERTY_EMAIL);
		$emailProperty->expects($this->never())
			->method('setValue');
		$emailProperty->expects($this->never())
			->method('setScope');

		/** @var MockObject|IAccountProperty $emailProperty */
		$emailProperty = $userAccount->getProperty(IAccountManager::PROPERTY_DISPLAYNAME);
		$emailProperty->expects($this->never())
			->method('setValue');
		$emailProperty->expects($this->never())
			->method('setScope');

		$this->config->expects($this->once())
			->method('getSystemValueBool')
			->with('allow_user_to_change_display_name')
			->willReturn(false);

		$this->appManager->expects($this->any())
			->method('isEnabledForUser')
			->with('federatedfilesharing')
			->willReturn(true);

		$this->mailer->expects($this->once())->method('validateMailAddress')
			->willReturn(true);

		$controller->expects($this->once())
			->method('saveUserSettings');

		$controller->setUserSettings(
			$avatarScope,
			$displayName,
			$displayNameScope,
			$phone,
			$phoneScope,
			$email,
			$emailScope,
			$website,
			$websiteScope,
			$address,
			$addressScope,
			$twitter,
			$twitterScope,
			$fediverse,
			$fediverseScope,
			$birtdate,
			$birthdateScope,
			$pronouns,
			$pronounsScope,
		);
	}

	public function testSetUserSettingsWhenFederatedFilesharingNotEnabled(): void {
		$controller = $this->getController(false, ['saveUserSettings']);
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('johndoe');

		$this->userSession->method('getUser')->willReturn($user);

		$defaultProperties = []; //$this->getDefaultAccountMock();

		$userAccount = $this->getDefaultAccountMock();
		$this->accountManager->expects($this->once())
			->method('getAccount')
			->with($user)
			->willReturn($userAccount);

		$this->appManager->expects($this->any())
			->method('isEnabledForUser')
			->with('federatedfilesharing')
			->willReturn(false);

		$avatarScope = IAccountManager::SCOPE_PUBLISHED;
		$displayName = 'Display name';
		$displayNameScope = IAccountManager::SCOPE_PUBLISHED;
		$phone = '47658468';
		$phoneScope = IAccountManager::SCOPE_PUBLISHED;
		$email = 'john@example.com';
		$emailScope = IAccountManager::SCOPE_PUBLISHED;
		$website = 'nextcloud.com';
		$websiteScope = IAccountManager::SCOPE_PUBLISHED;
		$address = 'street and city';
		$addressScope = IAccountManager::SCOPE_PUBLISHED;
		$twitter = '@nextclouders';
		$twitterScope = IAccountManager::SCOPE_PUBLISHED;
		$fediverse = '@nextclouders@floss.social';
		$fediverseScope = IAccountManager::SCOPE_PUBLISHED;
		$birthdate = '2020-01-01';
		$birthdateScope = IAccountManager::SCOPE_PUBLISHED;
		$pronouns = 'she/her';
		$pronounsScope = IAccountManager::SCOPE_PUBLISHED;

		// All settings are changed (in the past phone, website, address and
		// twitter were not changed).
		$expectedProperties = $defaultProperties;
		$expectedProperties[IAccountManager::PROPERTY_AVATAR]['scope'] = $avatarScope;
		$expectedProperties[IAccountManager::PROPERTY_DISPLAYNAME]['value'] = $displayName;
		$expectedProperties[IAccountManager::PROPERTY_DISPLAYNAME]['scope'] = $displayNameScope;
		$expectedProperties[IAccountManager::PROPERTY_EMAIL]['value'] = $email;
		$expectedProperties[IAccountManager::PROPERTY_EMAIL]['scope'] = $emailScope;
		$expectedProperties[IAccountManager::PROPERTY_PHONE]['value'] = $phone;
		$expectedProperties[IAccountManager::PROPERTY_PHONE]['scope'] = $phoneScope;
		$expectedProperties[IAccountManager::PROPERTY_WEBSITE]['value'] = $website;
		$expectedProperties[IAccountManager::PROPERTY_WEBSITE]['scope'] = $websiteScope;
		$expectedProperties[IAccountManager::PROPERTY_ADDRESS]['value'] = $address;
		$expectedProperties[IAccountManager::PROPERTY_ADDRESS]['scope'] = $addressScope;
		$expectedProperties[IAccountManager::PROPERTY_TWITTER]['value'] = $twitter;
		$expectedProperties[IAccountManager::PROPERTY_TWITTER]['scope'] = $twitterScope;
		$expectedProperties[IAccountManager::PROPERTY_FEDIVERSE]['value'] = $fediverse;
		$expectedProperties[IAccountManager::PROPERTY_FEDIVERSE]['scope'] = $fediverseScope;
		$expectedProperties[IAccountManager::PROPERTY_BIRTHDATE]['value'] = $birthdate;
		$expectedProperties[IAccountManager::PROPERTY_BIRTHDATE]['scope'] = $birthdateScope;
		$expectedProperties[IAccountManager::PROPERTY_PRONOUNS]['value'] = $pronouns;
		$expectedProperties[IAccountManager::PROPERTY_PRONOUNS]['scope'] = $pronounsScope;

		$this->mailer->expects($this->once())->method('validateMailAddress')
			->willReturn(true);

		$controller->expects($this->once())
			->method('saveUserSettings')
			->with($userAccount);

		$controller->setUserSettings(
			$avatarScope,
			$displayName,
			$displayNameScope,
			$phone,
			$phoneScope,
			$email,
			$emailScope,
			$website,
			$websiteScope,
			$address,
			$addressScope,
			$twitter,
			$twitterScope,
			$fediverse,
			$fediverseScope,
			$birthdate,
			$birthdateScope,
			$pronouns,
			$pronounsScope,
		);
	}

	/**
	 * @dataProvider dataTestSetUserSettingsSubset
	 *
	 * @param string $property
	 * @param string $propertyValue
	 */
	public function testSetUserSettingsSubset($property, $propertyValue): void {
		$controller = $this->getController(false, ['saveUserSettings']);
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('johndoe');

		$this->userSession->method('getUser')->willReturn($user);

		/** @var IAccount|MockObject $userAccount */
		$userAccount = $this->getDefaultAccountMock();

		$this->accountManager->expects($this->once())
			->method('getAccount')
			->with($user)
			->willReturn($userAccount);

		$avatarScope = ($property === 'avatarScope') ? $propertyValue : null;
		$displayName = ($property === 'displayName') ? $propertyValue : null;
		$displayNameScope = ($property === 'displayNameScope') ? $propertyValue : null;
		$phone = ($property === 'phone') ? $propertyValue : null;
		$phoneScope = ($property === 'phoneScope') ? $propertyValue : null;
		$email = ($property === 'email') ? $propertyValue : null;
		$emailScope = ($property === 'emailScope') ? $propertyValue : null;
		$website = ($property === 'website') ? $propertyValue : null;
		$websiteScope = ($property === 'websiteScope') ? $propertyValue : null;
		$address = ($property === 'address') ? $propertyValue : null;
		$addressScope = ($property === 'addressScope') ? $propertyValue : null;
		$twitter = ($property === 'twitter') ? $propertyValue : null;
		$twitterScope = ($property === 'twitterScope') ? $propertyValue : null;
		$fediverse = ($property === 'fediverse') ? $propertyValue : null;
		$fediverseScope = ($property === 'fediverseScope') ? $propertyValue : null;
		$birthdate = ($property === 'birthdate') ? $propertyValue : null;
		$birthdateScope = ($property === 'birthdateScope') ? $propertyValue : null;
		$pronouns = ($property === 'pronouns') ? $propertyValue : null;
		$pronounsScope = ($property === 'pronounsScope') ? $propertyValue : null;

		/** @var IAccountProperty[]|MockObject[] $expectedProperties */
		$expectedProperties = $userAccount->getProperties();
		$isScope = strrpos($property, 'Scope') === strlen($property) - strlen(5);
		switch ($property) {
			case 'avatarScope':
				$propertyId = IAccountManager::PROPERTY_AVATAR;
				break;
			case 'displayName':
			case 'displayNameScope':
				$propertyId = IAccountManager::PROPERTY_DISPLAYNAME;
				break;
			case 'phone':
			case 'phoneScope':
				$propertyId = IAccountManager::PROPERTY_PHONE;
				break;
			case 'email':
			case 'emailScope':
				$propertyId = IAccountManager::PROPERTY_EMAIL;
				break;
			case 'website':
			case 'websiteScope':
				$propertyId = IAccountManager::PROPERTY_WEBSITE;
				break;
			case 'address':
			case 'addressScope':
				$propertyId = IAccountManager::PROPERTY_ADDRESS;
				break;
			case 'twitter':
			case 'twitterScope':
				$propertyId = IAccountManager::PROPERTY_TWITTER;
				break;
			case 'fediverse':
			case 'fediverseScope':
				$propertyId = IAccountManager::PROPERTY_FEDIVERSE;
				break;
			case 'birthdate':
			case 'birthdateScope':
				$propertyId = IAccountManager::PROPERTY_BIRTHDATE;
				break;
			case 'pronouns':
			case 'pronounsScope':
				$propertyId = IAccountManager::PROPERTY_PRONOUNS;
				break;
			default:
				$propertyId = '404';
		}
		$expectedProperties[$propertyId]->expects($this->any())
			->method($isScope ? 'getScope' : 'getValue')
			->willReturn($propertyValue);

		if (!empty($email)) {
			$this->mailer->expects($this->once())->method('validateMailAddress')
				->willReturn(true);
		}

		$controller->expects($this->once())
			->method('saveUserSettings')
			->with($userAccount);

		$controller->setUserSettings(
			$avatarScope,
			$displayName,
			$displayNameScope,
			$phone,
			$phoneScope,
			$email,
			$emailScope,
			$website,
			$websiteScope,
			$address,
			$addressScope,
			$twitter,
			$twitterScope,
			$fediverse,
			$fediverseScope,
			$birthdate,
			$birthdateScope,
			$pronouns,
			$pronounsScope,
		);
	}

	public function dataTestSetUserSettingsSubset() {
		return [
			['avatarScope', IAccountManager::SCOPE_PUBLISHED],
			['displayName', 'Display name'],
			['displayNameScope', IAccountManager::SCOPE_PUBLISHED],
			['phone', '47658468'],
			['phoneScope', IAccountManager::SCOPE_PUBLISHED],
			['email', 'john@example.com'],
			['emailScope', IAccountManager::SCOPE_PUBLISHED],
			['website', 'nextcloud.com'],
			['websiteScope', IAccountManager::SCOPE_PUBLISHED],
			['address', 'street and city'],
			['addressScope', IAccountManager::SCOPE_PUBLISHED],
			['twitter', '@nextclouders'],
			['twitterScope', IAccountManager::SCOPE_PUBLISHED],
			['fediverse', '@nextclouders@floss.social'],
			['fediverseScope', IAccountManager::SCOPE_PUBLISHED],
			['birthdate', '2020-01-01'],
			['birthdateScope', IAccountManager::SCOPE_PUBLISHED],
			['pronouns', 'he/him'],
			['pronounsScope', IAccountManager::SCOPE_PUBLISHED],
		];
	}

	/**
	 * @dataProvider dataTestSaveUserSettings
	 *
	 * @param array $data
	 * @param ?string $oldEmailAddress
	 * @param ?string $oldDisplayName
	 */
	public function testSaveUserSettings($data,
		$oldEmailAddress,
		$oldDisplayName,
	): void {
		$controller = $this->getController();
		$user = $this->createMock(IUser::class);

		$user->method('getDisplayName')->willReturn($oldDisplayName);
		$user->method('getSystemEMailAddress')->willReturn($oldEmailAddress);
		$user->method('canChangeDisplayName')->willReturn(true);

		if (strtolower($data[IAccountManager::PROPERTY_EMAIL]['value']) === strtolower($oldEmailAddress ?? '')) {
			$user->expects($this->never())->method('setSystemEMailAddress');
		} else {
			$user->expects($this->once())->method('setSystemEMailAddress')
				->with($data[IAccountManager::PROPERTY_EMAIL]['value']);
		}

		if ($data[IAccountManager::PROPERTY_DISPLAYNAME]['value'] === $oldDisplayName ?? '') {
			$user->expects($this->never())->method('setDisplayName');
		} else {
			$user->expects($this->once())->method('setDisplayName')
				->with($data[IAccountManager::PROPERTY_DISPLAYNAME]['value'])
				->willReturn(true);
		}

		$properties = [];
		foreach ($data as $propertyName => $propertyData) {
			$properties[$propertyName] = $this->createMock(IAccountProperty::class);
			$properties[$propertyName]->expects($this->any())
				->method('getValue')
				->willReturn($propertyData['value']);
		}

		$account = $this->createMock(IAccount::class);
		$account->expects($this->any())
			->method('getUser')
			->willReturn($user);
		$account->expects($this->any())
			->method('getProperty')
			->willReturnCallback(function (string $propertyName) use ($properties) {
				return $properties[$propertyName];
			});

		$this->accountManager->expects($this->any())
			->method('getAccount')
			->willReturn($account);

		$this->accountManager->expects($this->once())
			->method('updateAccount')
			->with($account);

		$this->invokePrivate($controller, 'saveUserSettings', [$account]);
	}

	public function dataTestSaveUserSettings() {
		return [
			[
				[
					IAccountManager::PROPERTY_EMAIL => ['value' => 'john@example.com'],
					IAccountManager::PROPERTY_DISPLAYNAME => ['value' => 'john doe'],
				],
				'john@example.com',
				'john doe'
			],
			[
				[
					IAccountManager::PROPERTY_EMAIL => ['value' => 'john@example.com'],
					IAccountManager::PROPERTY_DISPLAYNAME => ['value' => 'john doe'],
				],
				'johnNew@example.com',
				'john New doe'
			],
			[
				[
					IAccountManager::PROPERTY_EMAIL => ['value' => 'john@example.com'],
					IAccountManager::PROPERTY_DISPLAYNAME => ['value' => 'john doe'],
				],
				'johnNew@example.com',
				'john doe'
			],
			[
				[
					IAccountManager::PROPERTY_EMAIL => ['value' => 'john@example.com'],
					IAccountManager::PROPERTY_DISPLAYNAME => ['value' => 'john doe'],
				],
				'john@example.com',
				'john New doe'
			],
			[
				[
					IAccountManager::PROPERTY_EMAIL => ['value' => ''],
					IAccountManager::PROPERTY_DISPLAYNAME => ['value' => 'john doe'],
				],
				null,
				'john New doe'
			],
			[
				[
					IAccountManager::PROPERTY_EMAIL => ['value' => 'john@example.com'],
					IAccountManager::PROPERTY_DISPLAYNAME => ['value' => 'john doe'],
				],
				'john@example.com',
				null
			],
			[
				[
					IAccountManager::PROPERTY_EMAIL => ['value' => 'john@example.com'],
					IAccountManager::PROPERTY_DISPLAYNAME => ['value' => 'john doe'],
				],
				'JOHN@example.com',
				null
			],

		];
	}

	/**
	 * @dataProvider dataTestSaveUserSettingsException
	 */
	public function testSaveUserSettingsException(
		array $data,
		string $oldEmailAddress,
		string $oldDisplayName,
		bool $setDisplayNameResult,
		bool $canChangeEmail,
	): void {
		$this->expectException(ForbiddenException::class);

		$controller = $this->getController();
		$user = $this->createMock(IUser::class);

		$user->method('getDisplayName')->willReturn($oldDisplayName);
		$user->method('getEMailAddress')->willReturn($oldEmailAddress);

		/** @var MockObject|IAccount $userAccount */
		$userAccount = $this->createMock(IAccount::class);
		$userAccount->expects($this->any())
			->method('getUser')
			->willReturn($user);
		$propertyMocks = [];
		foreach ($data as $propertyName => $propertyData) {
			/** @var MockObject|IAccountProperty $property */
			$propertyMocks[$propertyName] = $this->buildPropertyMock($propertyName, $propertyData['value'], '');
		}
		$userAccount->expects($this->any())
			->method('getProperty')
			->willReturnCallback(function (string $propertyName) use ($propertyMocks) {
				return $propertyMocks[$propertyName];
			});

		if ($data[IAccountManager::PROPERTY_EMAIL]['value'] !== $oldEmailAddress) {
			$user->method('canChangeDisplayName')
				->willReturn($canChangeEmail);
		}

		if ($data[IAccountManager::PROPERTY_DISPLAYNAME]['value'] !== $oldDisplayName) {
			$user->method('setDisplayName')
				->with($data[IAccountManager::PROPERTY_DISPLAYNAME]['value'])
				->willReturn($setDisplayNameResult);
		}

		$this->invokePrivate($controller, 'saveUserSettings', [$userAccount]);
	}


	public function dataTestSaveUserSettingsException() {
		return [
			[
				[
					IAccountManager::PROPERTY_EMAIL => ['value' => 'john@example.com'],
					IAccountManager::PROPERTY_DISPLAYNAME => ['value' => 'john doe'],
				],
				'johnNew@example.com',
				'john New doe',
				true,
				false
			],
			[
				[
					IAccountManager::PROPERTY_EMAIL => ['value' => 'john@example.com'],
					IAccountManager::PROPERTY_DISPLAYNAME => ['value' => 'john doe'],
				],
				'johnNew@example.com',
				'john New doe',
				false,
				true
			],
			[
				[
					IAccountManager::PROPERTY_EMAIL => ['value' => 'john@example.com'],
					IAccountManager::PROPERTY_DISPLAYNAME => ['value' => 'john doe'],
				],
				'johnNew@example.com',
				'john New doe',
				false,
				false
			],

		];
	}

	/**
	 * @param string $account
	 * @param string $type
	 * @param array $dataBefore
	 * @param array $expectedData
	 *
	 * @dataProvider dataTestGetVerificationCode
	 */
	public function testGetVerificationCode($account, $type, $dataBefore, $expectedData, $onlyVerificationCode): void {
		$message = 'Use my Federated Cloud ID to share with me: user@nextcloud.com';
		$signature = 'theSignature';

		$code = $message . ' ' . $signature;
		if ($type === IAccountManager::PROPERTY_TWITTER || $type === IAccountManager::PROPERTY_FEDIVERSE) {
			$code = $message . ' ' . md5($signature);
		}

		$controller = $this->getController(false, ['signMessage', 'getCurrentTime']);

		$user = $this->createMock(IUser::class);

		$property = $this->buildPropertyMock($type, $dataBefore[$type]['value'], '', IAccountManager::NOT_VERIFIED);
		$property->expects($this->atLeastOnce())
			->method('setVerified')
			->with(IAccountManager::VERIFICATION_IN_PROGRESS)
			->willReturnSelf();
		$property->expects($this->atLeastOnce())
			->method('setVerificationData')
			->with($signature)
			->willReturnSelf();

		$userAccount = $this->createMock(IAccount::class);
		$userAccount->expects($this->any())
			->method('getUser')
			->willReturn($user);
		$userAccount->expects($this->any())
			->method('getProperty')
			->willReturn($property);

		$this->userSession->expects($this->once())->method('getUser')->willReturn($user);
		$this->accountManager->expects($this->once())->method('getAccount')->with($user)->willReturn($userAccount);
		$user->expects($this->any())->method('getCloudId')->willReturn('user@nextcloud.com');
		$user->expects($this->any())->method('getUID')->willReturn('uid');
		$controller->expects($this->once())->method('signMessage')->with($user, $message)->willReturn($signature);
		$controller->expects($this->any())->method('getCurrentTime')->willReturn(1234567);

		if ($onlyVerificationCode === false) {
			$this->accountManager->expects($this->once())->method('updateAccount')->with($userAccount)->willReturnArgument(1);
			$this->jobList->expects($this->once())->method('add')
				->with('OCA\Settings\BackgroundJobs\VerifyUserData',
					[
						'verificationCode' => $code,
						'data' => $dataBefore[$type]['value'],
						'type' => $type,
						'uid' => 'uid',
						'try' => 0,
						'lastRun' => 1234567
					]);
		}

		$result = $controller->getVerificationCode($account, $onlyVerificationCode);

		$data = $result->getData();
		$this->assertSame(Http::STATUS_OK, $result->getStatus());
		$this->assertSame($code, $data['code']);
	}

	public function dataTestGetVerificationCode() {
		$accountDataBefore = [
			IAccountManager::PROPERTY_WEBSITE => ['value' => 'https://nextcloud.com', 'verified' => IAccountManager::NOT_VERIFIED],
			IAccountManager::PROPERTY_TWITTER => ['value' => '@nextclouders', 'verified' => IAccountManager::NOT_VERIFIED, 'signature' => 'theSignature'],
		];

		$accountDataAfterWebsite = [
			IAccountManager::PROPERTY_WEBSITE => ['value' => 'https://nextcloud.com', 'verified' => IAccountManager::VERIFICATION_IN_PROGRESS, 'signature' => 'theSignature'],
			IAccountManager::PROPERTY_TWITTER => ['value' => '@nextclouders', 'verified' => IAccountManager::NOT_VERIFIED, 'signature' => 'theSignature'],
		];

		$accountDataAfterTwitter = [
			IAccountManager::PROPERTY_WEBSITE => ['value' => 'https://nextcloud.com', 'verified' => IAccountManager::NOT_VERIFIED],
			IAccountManager::PROPERTY_TWITTER => ['value' => '@nextclouders', 'verified' => IAccountManager::VERIFICATION_IN_PROGRESS, 'signature' => 'theSignature'],
		];

		return [
			['verify-twitter', IAccountManager::PROPERTY_TWITTER, $accountDataBefore, $accountDataAfterTwitter, false],
			['verify-website', IAccountManager::PROPERTY_WEBSITE, $accountDataBefore, $accountDataAfterWebsite, false],
			['verify-twitter', IAccountManager::PROPERTY_TWITTER, $accountDataBefore, $accountDataAfterTwitter, true],
			['verify-website', IAccountManager::PROPERTY_WEBSITE, $accountDataBefore, $accountDataAfterWebsite, true],
		];
	}

	/**
	 * test get verification code in case no valid user was given
	 */
	public function testGetVerificationCodeInvalidUser(): void {
		$controller = $this->getController();
		$this->userSession->expects($this->once())->method('getUser')->willReturn(null);
		$result = $controller->getVerificationCode('account', false);

		$this->assertSame(Http::STATUS_BAD_REQUEST, $result->getStatus());
	}

	/**
	 * @dataProvider dataTestCanAdminChangeUserPasswords
	 *
	 * @param bool $encryptionEnabled
	 * @param bool $encryptionModuleLoaded
	 * @param bool $masterKeyEnabled
	 * @param bool $expected
	 */
	public function testCanAdminChangeUserPasswords($encryptionEnabled,
		$encryptionModuleLoaded,
		$masterKeyEnabled,
		$expected): void {
		$controller = $this->getController();

		$this->encryptionManager->expects($this->any())
			->method('isEnabled')
			->willReturn($encryptionEnabled);
		$this->encryptionManager->expects($this->any())
			->method('getEncryptionModule')
			->willReturnCallback(function () use ($encryptionModuleLoaded) {
				if ($encryptionModuleLoaded) {
					return $this->encryptionModule;
				} else {
					throw new ModuleDoesNotExistsException();
				}
			});
		$this->encryptionModule->expects($this->any())
			->method('needDetailedAccessList')
			->willReturn(!$masterKeyEnabled);

		$result = $this->invokePrivate($controller, 'canAdminChangeUserPasswords', []);
		$this->assertSame($expected, $result);
	}

	public function dataTestCanAdminChangeUserPasswords() {
		return [
			// encryptionEnabled, encryptionModuleLoaded, masterKeyEnabled, expectedResult
			[true, true, true, true],
			[false, true, true, true],
			[true, false, true, false],
			[false, false, true, true],
			[true, true, false, false],
			[false, true, false, false],
			[true, false, false, false],
			[false, false, false, true],
		];
	}
}
