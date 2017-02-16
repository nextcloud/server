<?php
/**
 * @copyright Copyright (c) 2017 Bjoern Schiessle <bjoern@schiessle.org>
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


namespace OC\Core\Controller;


use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use OCP\Share\IManager;

/**
 * Class LegacyShareController
 *
 * Implements a ajax call to send a link share by mail by creating a dedicated mail
 * share
 *
 * This class is used to stay compatible with the ownCloud sync client.
 * Can be removed as soon as compatibility is no longer required
 *
 * @package OC\Core\Controller
 */
class LegacyShareController extends Controller {

	/** @var IManager */
	private $shareManager;

	/**
	 * LegacyShareController constructor.
	 *
	 * @param string $appName
	 * @param IRequest $request
	 * @param IManager $shareManager
	 */
	public function __construct($appName, IRequest $request, IManager $shareManager) {
		parent::__construct($appName, $request);
		$this->shareManager = $shareManager;
	}

	/**
	 * @param string $action
	 * @param string $toaddress
	 * @param string $link
	 * @return JSONResponse
	 */
	public function link2Mail($action, $toaddress, $link) {
		if ($action !== 'email') {
			return new JSONResponse([], Http::STATUS_GONE);
		}

		$recipients = $this->getEmailAddresses($toaddress);

		try {
			$token = $this->getTokenFromUrl($link);
			$share = $this->shareManager->getShareByToken($token);
			$share->setShareType(\OCP\Share::SHARE_TYPE_EMAIL);
			foreach ($recipients as $to) {
				$share->setSharedWith($to);
				$this->shareManager->createShare($share);
			}
		} catch (\Exception $e) {
			return new JSONResponse(['data' => ['message' => 'Failed to send link by mail']], Http::STATUS_BAD_REQUEST);
		}
		return new JSONResponse();
	}

	/**
	 * exctract token from url
	 *
	 * @param string $url
	 * @return string
	 */
	protected function getTokenFromUrl($url) {
		$start = strrpos($url, '/') + 1;
		$token = substr($url, $start);
		return $token;
	}

	/**
	 * seperate multiple email addresses by ','
	 *
	 * @param string $addresses
	 * @return array
	 */
	protected function getEmailAddresses($addresses) {
		return explode(',' , $addresses);
	}

}
