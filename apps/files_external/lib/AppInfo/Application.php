<?php
/**
 * @author Joas Schilling <nickvergessen@owncloud.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <icewind@owncloud.com>
 * @author Robin McCorkell <robin@mccorkell.me.uk>
 * @author Roeland Jago Douma <rullzer@owncloud.com>
 * @author Ross Nicoll <jrn@jrn.me.uk>
 * @author Vincent Petry <pvince81@owncloud.com>
 *
 * @copyright Copyright (c) 2016, ownCloud, Inc.
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

namespace OCA\Files_External\AppInfo;

use \OCP\AppFramework\App;
use OCP\AppFramework\IAppContainer;
use \OCP\IContainer;
use \OCA\Files_External\Service\BackendService;
use \OCA\Files_External\Lib\Config\IBackendProvider;
use \OCA\Files_External\Lib\Config\IAuthMechanismProvider;

/**
 * @package OCA\Files_External\AppInfo
 */
class Application extends App implements IBackendProvider, IAuthMechanismProvider {

	public function __construct(array $urlParams = array()) {
		parent::__construct('files_external', $urlParams);

		$container = $this->getContainer();

		$container->registerService('OCP\Files\Config\IUserMountCache', function (IAppContainer $c) {
			return $c->getServer()->query('UserMountCache');
		});

		$backendService = $container->query('OCA\\Files_External\\Service\\BackendService');
		$backendService->registerBackendProvider($this);
		$backendService->registerAuthMechanismProvider($this);

		// force-load auth mechanisms since some will register hooks
		// TODO: obsolete these and use the TokenProvider to get the user's password from the session
		$this->getAuthMechanisms();

		// app developers: do NOT depend on this! it will disappear with oC 9.0!
		\OC::$server->getEventDispatcher()->dispatch(
			'OCA\\Files_External::loadAdditionalBackends'
		);
	}

	/**
	 * Register settings templates
	 */
	public function registerSettings() {
		$container = $this->getContainer();
		$backendService = $container->query('OCA\\Files_External\\Service\\BackendService');

		\OCP\App::registerAdmin('files_external', 'settings');
		\OCP\App::registerPersonal('files_external', 'personal');
	}

	/**
	 * @{inheritdoc}
	 */
	public function getBackends() {
		$container = $this->getContainer();

		$backends = [
			$container->query('OCA\Files_External\Lib\Backend\Local'),
			$container->query('OCA\Files_External\Lib\Backend\FTP'),
			$container->query('OCA\Files_External\Lib\Backend\DAV'),
			$container->query('OCA\Files_External\Lib\Backend\OwnCloud'),
			$container->query('OCA\Files_External\Lib\Backend\SFTP'),
			$container->query('OCA\Files_External\Lib\Backend\AmazonS3'),
			$container->query('OCA\Files_External\Lib\Backend\Dropbox'),
			$container->query('OCA\Files_External\Lib\Backend\Google'),
			$container->query('OCA\Files_External\Lib\Backend\Swift'),
			$container->query('OCA\Files_External\Lib\Backend\SFTP_Key'),
		];

		if (!\OC_Util::runningOnWindows()) {
			$backends[] = $container->query('OCA\Files_External\Lib\Backend\SMB');
			$backends[] = $container->query('OCA\Files_External\Lib\Backend\SMB_OC');
		}

		return $backends;
	}

	/**
	 * @{inheritdoc}
	 */
	public function getAuthMechanisms() {
		$container = $this->getContainer();

		return [
			// AuthMechanism::SCHEME_NULL mechanism
			$container->query('OCA\Files_External\Lib\Auth\NullMechanism'),

			// AuthMechanism::SCHEME_BUILTIN mechanism
			$container->query('OCA\Files_External\Lib\Auth\Builtin'),

			// AuthMechanism::SCHEME_PASSWORD mechanisms
			$container->query('OCA\Files_External\Lib\Auth\Password\Password'),
			$container->query('OCA\Files_External\Lib\Auth\Password\SessionCredentials'),

			// AuthMechanism::SCHEME_OAUTH1 mechanisms
			$container->query('OCA\Files_External\Lib\Auth\OAuth1\OAuth1'),

			// AuthMechanism::SCHEME_OAUTH2 mechanisms
			$container->query('OCA\Files_External\Lib\Auth\OAuth2\OAuth2'),

			// AuthMechanism::SCHEME_PUBLICKEY mechanisms
			$container->query('OCA\Files_External\Lib\Auth\PublicKey\RSA'),

			// AuthMechanism::SCHEME_OPENSTACK mechanisms
			$container->query('OCA\Files_External\Lib\Auth\OpenStack\OpenStack'),
			$container->query('OCA\Files_External\Lib\Auth\OpenStack\Rackspace'),

			// Specialized mechanisms
			$container->query('OCA\Files_External\Lib\Auth\AmazonS3\AccessKey'),
		];
	}

}
