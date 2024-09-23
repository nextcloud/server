<?php
/**
 * @copyright 2023, Thomas Citharel <nextcloud@tcit.fr>
 *
 * @author Thomas Citharel <nextcloud@tcit.fr>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
namespace OCA\ContactsInteraction\DAV;

use OCA\ContactsInteraction\AddressBook;
use OCA\ContactsInteraction\AppInfo\Application;
use OCA\ContactsInteraction\Db\RecentContactMapper;
use OCP\IConfig;
use Sabre\DAV\Server;
use Sabre\DAV\ServerPlugin;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;

/**
 * Allows users to disable the feature by deleting the addressbook
 *
 * @package OCA\DAV\CalDAV\BirthdayCalendar
 */
class Plugin extends ServerPlugin {

	protected Server $server;

	public function __construct(protected IConfig $config, protected RecentContactMapper $recentContactMapper) {
	}

	/**
	 * This method should return a list of server-features.
	 *
	 * This is for example 'versioning' and is added to the DAV: header
	 * in an OPTIONS response.
	 *
	 * @return string[]
	 */
	public function getFeatures() {
		return ['nc-disable-recently-contacted'];
	}

	/**
	 * Returns a plugin name.
	 *
	 * Using this name other plugins will be able to access other plugins
	 * using Sabre\DAV\Server::getPlugin
	 *
	 * @return string
	 */
	public function getPluginName() {
		return 'nc-disable-recently-contacted';
	}

	/**
	 * This initializes the plugin.
	 *
	 * This function is called by Sabre\DAV\Server, after
	 * addPlugin is called.
	 *
	 * This method should set up the required event subscriptions.
	 *
	 * @param Server $server
	 */
	public function initialize(Server $server) {
		$this->server = $server;

		$this->server->on('method:DELETE', [$this, 'httpDelete']);
	}

	/**
	 * We intercept this to handle POST requests on contacts homes.
	 *
	 * @param RequestInterface $request
	 * @param ResponseInterface $response
	 *
	 * @return bool|void
	 */
	public function httpDelete(RequestInterface $request, ResponseInterface $response) {
		$node = $this->server->tree->getNodeForPath($this->server->getRequestUri());
		if (!($node instanceof AddressBook)) {
			return;
		}

		$principalUri = $node->getOwner();
		$userId = substr($principalUri, 17);

		$this->config->setUserValue($userId, Application::APP_ID, 'disableContactsInteractionAddressBook', 'yes');
		$this->recentContactMapper->cleanForUser($userId);

		$this->server->httpResponse->setStatus(204);

		return false;
	}
}
