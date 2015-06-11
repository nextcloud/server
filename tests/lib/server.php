<?php
/**
 * @author Joas Schilling <nickvergessen@owncloud.com>
 *
 * @copyright Copyright (c) 2015, ownCloud, Inc.
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace Test;

class Server extends \Test\TestCase {
	/** @var \OC\Server */
	protected $server;


	public function setUp() {
		parent::setUp();
		$this->server = new \OC\Server('');
	}

	public function dataTestQuery() {
		return [
			['ActivityManager', '\OC\ActivityManager'],
			['ActivityManager', '\OCP\Activity\IManager'],
			['AllConfig', '\OC\AllConfig'],
			['AllConfig', '\OCP\IConfig'],
			['AppConfig', '\OC\AppConfig'],
			['AppConfig', '\OCP\IAppConfig'],
			['AppHelper', '\OC\AppHelper'],
			['AppHelper', '\OCP\IHelper'],
			['AppManager', '\OC\App\AppManager'],
			['AppManager', '\OCP\App\IAppManager'],
			['AsyncCommandBus', '\OC\Command\AsyncBus'],
			['AsyncCommandBus', '\OCP\Command\IBus'],
			['AvatarManager', '\OC\AvatarManager'],
			['AvatarManager', '\OCP\IAvatarManager'],

			['ContactsManager', '\OC\ContactsManager'],
			['ContactsManager', '\OCP\Contacts\IManager'],
			['Crypto', '\OC\Security\Crypto'],
			['Crypto', '\OCP\Security\ICrypto'],

			['DatabaseConnection', '\OC\DB\Connection'],
			['DatabaseConnection', '\OCP\IDBConnection'],
			['DateTimeFormatter', '\OC\DateTimeFormatter'],
			['DateTimeFormatter', '\OCP\IDateTimeFormatter'],
			['DateTimeZone', '\OC\DateTimeZone'],
			['DateTimeZone', '\OCP\IDateTimeZone'],
			['Db', '\OC\AppFramework\Db\Db'],
			['Db', '\OCP\IDb'],

			['EncryptionFileHelper', '\OC\Encryption\File'],
			['EncryptionFileHelper', '\OCP\Encryption\IFile'],
			['EncryptionKeyStorage', '\OC\Encryption\Keys\Storage'],
			['EncryptionKeyStorage', '\OCP\Encryption\Keys\IStorage'],
			['EncryptionManager', '\OC\Encryption\Manager'],
			['EncryptionManager', '\OCP\Encryption\IManager'],
			['EventLogger', '\OCP\Diagnostics\IEventLogger'],

			['GroupManager', '\OC\Group\Manager'],
			['GroupManager', '\OCP\IGroupManager'],

			['Hasher', '\OC\Security\Hasher'],
			['Hasher', '\OCP\Security\IHasher'],
			['HTTPHelper', '\OC\HTTPHelper'],
			['HttpClientService', '\OC\Http\Client\ClientService'],
			['HttpClientService', '\OCP\Http\Client\IClientService'],

			['IniWrapper', '\bantu\IniGetWrapper\IniGetWrapper'],

			['JobList', '\OC\BackgroundJob\JobList'],
			['JobList', '\OCP\BackgroundJob\IJobList'],

			['L10NFactory', '\OC\L10N\Factory'],
			['LockingProvider', '\OCP\Lock\ILockingProvider'],
			['Logger', '\OC\Log'],
			['Logger', '\OCP\ILogger'],

			['Mailer', '\OC\Mail\Mailer'],
			['Mailer', '\OCP\Mail\IMailer'],
			['MemCacheFactory', '\OC\Memcache\Factory'],
			['MemCacheFactory', '\OCP\ICacheFactory'],
			['MountConfigManager', '\OC\Files\Config\MountProviderCollection'],
			['MountConfigManager', '\OCP\Files\Config\IMountProviderCollection'],

			['NavigationManager', '\OC\NavigationManager'],
			['NavigationManager', '\OCP\INavigationManager'],
			['NullCache', '\OC\Memcache\NullCache'],
			['NullCache', '\OC\Memcache\Cache'],
			['NullCache', '\OCP\IMemcache'],

			['OcsClient', '\OC\OCSClient'],

			['PreviewManager', '\OC\PreviewManager'],
			['PreviewManager', '\OCP\IPreview'],

			['QueryLogger', '\OCP\Diagnostics\IQueryLogger'],

			['Request', '\OC\AppFramework\Http\Request'],
			['Request', '\OCP\IRequest'],
			['RootFolder', '\OC\Files\Node\Root'],
			['RootFolder', '\OC\Files\Node\Folder'],
			['RootFolder', '\OCP\Files\IRootFolder'],
			['RootFolder', '\OCP\Files\Folder'],
			['Router', '\OCP\Route\IRouter'],

			['Search', '\OC\Search'],
			['Search', '\OCP\ISearch'],
			['SecureRandom', '\OC\Security\SecureRandom'],
			['SecureRandom', '\OCP\Security\ISecureRandom'],
			['SystemConfig', '\OC\SystemConfig'],

			['URLGenerator', '\OC\URLGenerator'],
			['URLGenerator', '\OCP\IURLGenerator'],
			['UserManager', '\OC\User\Manager'],
			['UserManager', '\OCP\IUserManager'],
			['UserSession', '\OC\User\Session'],
			['UserSession', '\OCP\IUserSession'],

			['TagMapper', '\OC\Tagging\TagMapper'],
			['TagMapper', '\OCP\AppFramework\Db\Mapper'],
			['TagManager', '\OC\TagManager'],
			['TagManager', '\OCP\ITagManager'],
			['TempManager', '\OC\TempManager'],
			['TempManager', '\OCP\ITempManager'],
			['TrustedDomainHelper', '\OC\Security\TrustedDomainHelper'],
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
		$this->assertInstanceOf('\OC\Security\CertificateManager', $this->server->getCertificateManager('test'), 'service returned by "getCertificateManager" did not return the right class');
		$this->assertInstanceOf('\OCP\ICertificateManager', $this->server->getCertificateManager('test'), 'service returned by "getCertificateManager" did not return the right class');
	}

	public function testCreateEventSource() {
		$this->assertInstanceOf('\OC_EventSource', $this->server->createEventSource(), 'service returned by "createEventSource" did not return the right class');
		$this->assertInstanceOf('\OCP\IEventSource', $this->server->createEventSource(), 'service returned by "createEventSource" did not return the right class');
	}
}
