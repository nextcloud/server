<?php
/**
 * @copyright Copyright (c) 2016 Joas Schilling <coding@schilljs.com>
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

namespace OCA\WorkflowEngine\Check;


use OCP\Files\IMimeTypeDetector;
use OCP\IRequest;

class FileMimeType extends AbstractStringCheck {

	/** @var string */
	protected $mimeType;

	/** @var IRequest */
	protected $request;

	/** @var IMimeTypeDetector */
	protected $mimeTypeDetector;

	/**
	 * @param IRequest $request
	 * @param IMimeTypeDetector $mimeTypeDetector
	 */
	public function __construct(IRequest $request, IMimeTypeDetector $mimeTypeDetector) {
		$this->request = $request;
		$this->mimeTypeDetector = $mimeTypeDetector;
	}

	/**
	 * @return string
	 */
	protected function getActualValue() {
		if ($this->mimeType !== null) {
			return $this->mimeType;
		}

		$this->mimeType = '';
		if ($this->isWebDAVRequest()) {
			if ($this->request->getMethod() === 'PUT') {
				$path = $this->request->getPathInfo();
				$this->mimeType = $this->mimeTypeDetector->detectPath($path);
			}
		} else if (in_array($this->request->getMethod(), ['POST', 'PUT'])) {
			$files = $this->request->getUploadedFile('files');
			if (isset($files['type'][0])) {
				$this->mimeType = $files['type'][0];
			}
		}
		return $this->mimeType;
	}

	/**
	 * @return bool
	 */
	protected function isWebDAVRequest() {
		return substr($this->request->getScriptName(), 0 - strlen('/remote.php')) === '/remote.php' && (
			$this->request->getPathInfo() === '/webdav' ||
			strpos($this->request->getPathInfo(), '/webdav/') === 0 ||
			$this->request->getPathInfo() === '/dav/files' ||
			strpos($this->request->getPathInfo(), '/dav/files/') === 0
		);
	}
}
