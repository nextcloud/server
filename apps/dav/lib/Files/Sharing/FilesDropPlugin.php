<?php
/**
 * @copyright Copyright (c) 2016, Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @author Roeland Jago Douma <roeland@famdouma.nl>
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
namespace OCA\DAV\Files\Sharing;

use OC\Files\View;
use Sabre\DAV\ServerPlugin;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;

/**
 * Make sure that the destination is writable
 */
class FilesDropPlugin extends ServerPlugin {

	/**
	 * @var View
	 */
	private $view;

	/**
	 * @param View $view
	 */
	public function setView($view) {
		$this->view = $view;
	}

	/**
	 * This initializes the plugin.
	 *
	 * @param \Sabre\DAV\Server $server Sabre server
	 *
	 * @return void
	 */
	public function initialize(\Sabre\DAV\Server $server) {
		$server->on('beforeMethod', [$this, 'beforeMethod']);
	}

	public function beforeMethod(RequestInterface $request, ResponseInterface $response){
		$path = $request->getPath();

		if ($this->view->file_exists($path)) {
			$newName = \OC_Helper::buildNotExistingFileNameForView('/', $path, $this->view);

			$url = $request->getBaseUrl() . $newName . '?';
			$parms = $request->getQueryParameters();
			$first = true;
			foreach ($parms as $k => $v) {
				if ($first) {
					$url .= '?';
					$first = false;
				} else {
					$url .= '&';
				}
				$url .= $k . '=' . $v;
			}

			$request->setUrl($url);
		}


	}
}
