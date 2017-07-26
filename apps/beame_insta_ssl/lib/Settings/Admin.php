<?php
/**
 * @copyright Copyright (c) 2017 Beame.io LTD <support@beame.io>
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

namespace OCA\BeameInstaSsl\Settings;

use OCP\AppFramework\Http\TemplateResponse;
use OCP\Settings\ISettings;
use OCP\IRequest;

require_once(__DIR__ . '/../BeameInstaSsl.php');
// use OCA\BeameInstaSsl; // How this should work?

class Admin implements ISettings {

	/** @var IRequest */
	var $request;

	public function __construct(IRequest $request) {
		$this->request = $request;
	}


	/**
	 * @return TemplateResponse
	 */
	public function getForm() {

        $params = \OCA\BeameInstaSsl\checkTunnelRunning();
		$params['error'] = $this->request->getParam('beame_insta_ssl_error');
		$params['got_creds'] = $this->request->getParam('beame_insta_ssl_got_creds');

		// p("CREDS $creds");
		// https://github.com/nextcloud/server/blob/master/lib/public/AppFramework/Http/TemplateResponse.php
		return new TemplateResponse('beame_insta_ssl', 'admin', $params, '');
	}

	/**
	 * {@inheritdoc}
	 */
	public function getSection() {
		return 'additional';
	}

	/**
	 * {@inheritdoc}
	 */
	public function getPriority() {
		return 0;
	}
}
