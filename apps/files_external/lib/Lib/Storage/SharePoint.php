<?php
/**
 * @copyright Copyright (c) 2016 Arthur Schiwon <blizzz@arthur-schiwon.de>
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Files_External\Lib\Storage;

use OC\Files\Storage\Common;
use OCA\Files_External\Lib\SharePoint\ContextsFactory;
use Office365\PHP\Client\SharePoint\ClientContext;

class SharePoint extends Common {

	protected $server;

	protected $documentLibrary;

	protected $authUser;

	protected $authPwd;

	/** @var  ClientContext */
	protected $context;

	/** @var  ContextsFactory */
	protected $contextsFactory;

	public function __construct($parameters) {
		$this->server = $parameters['host'];
		$this->documentLibrary = $parameters['root'];

		// FIXME: perhaps not set
		$this->authUser = $parameters['user'];
		$this->authPwd = $parameters['password'];

		$this->fixDI($parameters);
		$this->initConnection($this->authUser, $this->authPwd);

		$web  = $this->context->getWeb();
		$list = $web->getLists()->getByTitle($this->documentLibrary);
		$s = 'asdf';

		# TODO: test this ^
	}

	/**
	 * Get the identifier for the storage,
	 * the returned id should be the same for every storage object that is created with the same parameters
	 * and two storage objects with the same id should refer to two storages that display the same files.
	 *
	 * @return string
	 * @since 6.0.0
	 */
	public function getId() {
		return 'SharePoint::' . $this->server . '::' . $this->documentLibrary . '::' . $this->authUser;
	}

	/**
	 * see http://php.net/manual/en/function.mkdir.php
	 * implementations need to implement a recursive mkdir
	 *
	 * @param string $path
	 * @return bool
	 * @since 6.0.0
	 */
	public function mkdir($path) {
		// TODO: Implement mkdir() method.
		return true;
	}

	/**
	 * see http://php.net/manual/en/function.rmdir.php
	 *
	 * @param string $path
	 * @return bool
	 * @since 6.0.0
	 */
	public function rmdir($path) {
		// TODO: Implement rmdir() method.
		return true;
	}

	/**
	 * see http://php.net/manual/en/function.opendir.php
	 *
	 * @param string $path
	 * @return resource|false
	 * @since 6.0.0
	 */
	public function opendir($path) {
		// TODO: Implement opendir() method.
		return false;
	}

	/**
	 * see http://php.net/manual/en/function.stat.php
	 * only the following keys are required in the result: size and mtime
	 *
	 * @param string $path
	 * @return array|false
	 * @since 6.0.0
	 */
	public function stat($path) {
		// TODO: Implement stat() method.
		$path = trim($path, '/');

		return false;
	}

	/**
	 * see http://php.net/manual/en/function.filetype.php
	 *
	 * @param string $path
	 * @return string|false
	 * @since 6.0.0
	 */
	public function filetype($path) {
		// TODO: Implement filetype() method.
		return false;
	}

	/**
	 * see http://php.net/manual/en/function.file_exists.php
	 *
	 * @param string $path
	 * @return bool
	 * @since 6.0.0
	 */
	public function file_exists($path) {
		// TODO: Implement file_exists() method.
		return true;
	}

	/**
	 * see http://php.net/manual/en/function.unlink.php
	 *
	 * @param string $path
	 * @return bool
	 * @since 6.0.0
	 */
	public function unlink($path) {
		// TODO: Implement unlink() method.
		return true;
	}

	/**
	 * see http://php.net/manual/en/function.fopen.php
	 *
	 * @param string $path
	 * @param string $mode
	 * @return resource|false
	 * @since 6.0.0
	 */
	public function fopen($path, $mode) {
		// TODO: Implement fopen() method.
		return false;
	}

	/**
	 * see http://php.net/manual/en/function.touch.php
	 * If the backend does not support the operation, false should be returned
	 *
	 * @param string $path
	 * @param int $mtime
	 * @return bool
	 * @since 6.0.0
	 */
	public function touch($path, $mtime = null) {
		// TODO: Implement touch() method.
		return true;
	}

	/**
	 * work around dependency injection issues so we can test this class properly
	 *
	 * @param array $parameters
	 */
	private function fixDI(array $parameters) {
		if(isset($parameters['authContextFactory'])
			&& $parameters['authContextFactory'] instanceof ContextsFactory)
		{
			$this->contextsFactory = $parameters['authContextFactory'];
		} else {
			$this->contextsFactory = new ContextsFactory();
		}
	}

	/**
	 * Set up necessary contexts for authentication and access to SharePoint
	 *
	 * @param string $user
	 * @param string $password
	 * @throws \InvalidArgumentException
	 */
	private function initConnection($user, $password) {
		if(!is_string($user) || empty($user)) {
			throw new \InvalidArgumentException('No user given');
		}
		if(!is_string($password) || empty($password)) {
			throw new \InvalidArgumentException('No password given');
		}
		$authContext = $this->contextsFactory ->getAuthContext($user, $password);
		$this->context = $this->contextsFactory->getClientContext($this->server, $authContext);
	}
}
