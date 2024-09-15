<?php
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace Test;

use OC\App\AppStore\Fetcher\AppFetcher;
use OC\App\AppStore\Fetcher\CategoryFetcher;
use OCP\Comments\ICommentsManager;

/**
 * Class Server
 *
 * @group DB
 *
 * @package Test
 */
class ServerTest extends \Test\TestCase {
	/** @var \OC\Server */
	protected $server;


	protected function setUp(): void {
		parent::setUp();
		$config = new \OC\Config(\OC::$configDir);
		$this->server = new \OC\Server('', $config);
	}

	public function dataTestQuery() {
		return [
			['ActivityManager', \OC\Activity\Manager::class],
			['ActivityManager', \OCP\Activity\IManager::class],
			['AllConfig', \OC\AllConfig::class],
			['AllConfig', \OCP\IConfig::class],
			['AppConfig', \OC\AppConfig::class],
			['AppConfig', \OCP\IAppConfig::class],
			['AppFetcher', AppFetcher::class],
			['AppManager', \OC\App\AppManager::class],
			['AppManager', \OCP\App\IAppManager::class],
			['AsyncCommandBus', \OC\Command\AsyncBus::class],
			['AsyncCommandBus', \OCP\Command\IBus::class],
			['AvatarManager', \OC\Avatar\AvatarManager::class],
			['AvatarManager', \OCP\IAvatarManager::class],

			['CategoryFetcher', CategoryFetcher::class],
			['CapabilitiesManager', \OC\CapabilitiesManager::class],
			['ContactsManager', \OC\ContactsManager::class],
			['ContactsManager', \OCP\Contacts\IManager::class],
			['ContentSecurityPolicyManager', \OC\Security\CSP\ContentSecurityPolicyManager::class],
			['CommentsManager', \OCP\Comments\ICommentsManager::class],
			['Crypto', \OC\Security\Crypto::class],
			['Crypto', \OCP\Security\ICrypto::class],
			['CryptoWrapper', \OC\Session\CryptoWrapper::class],
			['CsrfTokenManager', \OC\Security\CSRF\CsrfTokenManager::class],

			['DatabaseConnection', \OC\DB\ConnectionAdapter::class],
			['DatabaseConnection', \OCP\IDBConnection::class],
			['DateTimeFormatter', \OC\DateTimeFormatter::class],
			['DateTimeFormatter', \OCP\IDateTimeFormatter::class],
			['DateTimeZone', \OC\DateTimeZone::class],
			['DateTimeZone', \OCP\IDateTimeZone::class],

			['EncryptionFileHelper', \OC\Encryption\File::class],
			['EncryptionFileHelper', \OCP\Encryption\IFile::class],
			['EncryptionKeyStorage', \OC\Encryption\Keys\Storage::class],
			['EncryptionKeyStorage', \OCP\Encryption\Keys\IStorage::class],
			['EncryptionManager', \OC\Encryption\Manager::class],
			['EncryptionManager', \OCP\Encryption\IManager::class],
			['EventLogger', \OCP\Diagnostics\IEventLogger::class],

			['GroupManager', \OC\Group\Manager::class],
			['GroupManager', \OCP\IGroupManager::class],

			['Hasher', \OC\Security\Hasher::class],
			['Hasher', \OCP\Security\IHasher::class],
			['HttpClientService', \OC\Http\Client\ClientService::class],
			['HttpClientService', \OCP\Http\Client\IClientService::class],

			['IniWrapper', '\bantu\IniGetWrapper\IniGetWrapper'],
			['MimeTypeDetector', \OCP\Files\IMimeTypeDetector::class],
			['MimeTypeDetector', \OC\Files\Type\Detection::class],

			['JobList', \OC\BackgroundJob\JobList::class],
			['JobList', \OCP\BackgroundJob\IJobList::class],

			['L10NFactory', \OC\L10N\Factory::class],
			['L10NFactory', \OCP\L10N\IFactory::class],
			['LockingProvider', \OCP\Lock\ILockingProvider::class],
			['Logger', \OC\Log::class],
			['Logger', \OCP\ILogger::class],

			['Mailer', \OC\Mail\Mailer::class],
			['Mailer', \OCP\Mail\IMailer::class],
			['MemCacheFactory', \OC\Memcache\Factory::class],
			['MemCacheFactory', \OCP\ICacheFactory::class],
			['MountConfigManager', \OC\Files\Config\MountProviderCollection::class],
			['MountConfigManager', \OCP\Files\Config\IMountProviderCollection::class],

			['NavigationManager', \OC\NavigationManager::class],
			['NavigationManager', \OCP\INavigationManager::class],
			['NotificationManager', \OC\Notification\Manager::class],
			['NotificationManager', \OCP\Notification\IManager::class],
			['UserCache', \OC\Cache\File::class],
			['UserCache', \OCP\ICache::class],

			['PreviewManager', \OC\PreviewManager::class],
			['PreviewManager', \OCP\IPreview::class],

			['QueryLogger', \OCP\Diagnostics\IQueryLogger::class],

			['Request', \OC\AppFramework\Http\Request::class],
			['Request', \OCP\IRequest::class],
			['RootFolder', \OC\Files\Node\Root::class],
			['RootFolder', \OC\Files\Node\Folder::class],
			['RootFolder', \OCP\Files\IRootFolder::class],
			['RootFolder', \OCP\Files\Folder::class],
			['Router', \OCP\Route\IRouter::class],

			['SecureRandom', \OC\Security\SecureRandom::class],
			['SecureRandom', \OCP\Security\ISecureRandom::class],
			['ShareManager', \OC\Share20\Manager::class],
			['ShareManager', \OCP\Share\IManager::class],
			['SystemConfig', \OC\SystemConfig::class],

			['URLGenerator', \OC\URLGenerator::class],
			['URLGenerator', \OCP\IURLGenerator::class],
			['UserManager', \OC\User\Manager::class],
			['UserManager', \OCP\IUserManager::class],
			['UserSession', \OC\User\Session::class],
			['UserSession', \OCP\IUserSession::class],

			['TagMapper', \OC\Tagging\TagMapper::class],
			['TagMapper', \OCP\AppFramework\Db\QBMapper::class],
			['TagManager', \OC\TagManager::class],
			['TagManager', \OCP\ITagManager::class],
			['TempManager', \OC\TempManager::class],
			['TempManager', \OCP\ITempManager::class],
			['ThemingDefaults', \OCA\Theming\ThemingDefaults::class],
			['TrustedDomainHelper', \OC\Security\TrustedDomainHelper::class],

			['SystemTagManager', \OCP\SystemTag\ISystemTagManager::class],
			['SystemTagObjectMapper', \OCP\SystemTag\ISystemTagObjectMapper::class],
		];
	}

	/**
	 * @dataProvider dataTestQuery
	 *
	 * @param string $serviceName
	 * @param string $instanceOf
	 */
	public function testQuery($serviceName, $instanceOf) {
		$this->assertInstanceOf($instanceOf, $this->server->query($serviceName), 'Service "' . $serviceName . '"" did not return the right class');
	}

	public function testGetCertificateManager() {
		$this->assertInstanceOf(\OC\Security\CertificateManager::class, $this->server->getCertificateManager(), 'service returned by "getCertificateManager" did not return the right class');
		$this->assertInstanceOf(\OCP\ICertificateManager::class, $this->server->getCertificateManager(), 'service returned by "getCertificateManager" did not return the right class');
	}

	public function testOverwriteDefaultCommentsManager() {
		$config = $this->server->getConfig();
		$defaultManagerFactory = $config->getSystemValue('comments.managerFactory', \OC\Comments\ManagerFactory::class);

		$config->setSystemValue('comments.managerFactory', \Test\Comments\FakeFactory::class);

		$manager = $this->server->get(ICommentsManager::class);
		$this->assertInstanceOf(\OCP\Comments\ICommentsManager::class, $manager);

		$config->setSystemValue('comments.managerFactory', $defaultManagerFactory);
	}
}
