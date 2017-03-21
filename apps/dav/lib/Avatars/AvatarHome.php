<?php
/**
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
 * @copyright Copyright (c) 2016, ownCloud GmbH
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


namespace OCA\DAV\Avatars;


use OCP\IAvatarManager;
use Sabre\DAV\Exception\Forbidden;
use Sabre\DAV\Exception\MethodNotAllowed;
use Sabre\DAV\Exception\NotFound;
use Sabre\DAV\ICollection;
use Sabre\HTTP\URLUtil;

class AvatarHome implements ICollection {

	/** @var array */
	private $principalInfo;
	/** @var IAvatarManager */
	private $avatarManager;

	/**
	 * AvatarHome constructor.
	 *
	 * @param array $principalInfo
	 */
	public function __construct($principalInfo, IAvatarManager $avatarManager) {
		$this->principalInfo = $principalInfo;
		$this->avatarManager = $avatarManager;
	}

	function createFile($name, $data = null) {
		throw new Forbidden('Permission denied to create a file');
	}

	function createDirectory($name) {
		throw new Forbidden('Permission denied to create a folder');
	}

	function getChild($name) {
		$elements = pathinfo($name);
		$ext = isset($elements['extension']) ? $elements['extension'] : '';
		$size = intval(isset($elements['filename']) ? $elements['filename'] : '64');
		if (!in_array($ext, ['jpeg', 'png'])) {
			throw new MethodNotAllowed('File format not allowed');
		}
		if ($size <= 0 || $size > 1024) {
			throw new MethodNotAllowed('Invalid image size');
		}
		$avatar = $this->avatarManager->getAvatar($this->getName());
		if ($avatar === null || !$avatar->exists()) {
			throw new NotFound();
		}
		return new AvatarNode($size, $ext, $avatar);
	}

	function getChildren() {
		try {
			return [
				$this->getChild('96.jpeg')
			];
		} catch(NotFound $exception) {
			return [];
		}
	}

	function childExists($name) {
		try {
			$ret = $this->getChild($name);
			return !is_null($ret);
		} catch (NotFound $ex) {
			return false;
		} catch (MethodNotAllowed $ex) {
			return false;
		}
	}

	function delete() {
		throw new Forbidden('Permission denied to delete this folder');
	}

	function getName() {
		list(,$name) = URLUtil::splitPath($this->principalInfo['uri']);
		return $name;
	}

	function setName($name) {
		throw new Forbidden('Permission denied to rename this folder');
	}

	/**
	 * Returns the last modification time, as a unix timestamp
	 *
	 * @return int
	 */
	function getLastModified() {
		return null;
	}


}
