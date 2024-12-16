<?php
/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Provisioning\Apple;

use OCP\AppFramework\Http;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\IUserSession;
use Sabre\DAV\Server;
use Sabre\DAV\ServerPlugin;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;

class AppleProvisioningPlugin extends ServerPlugin {
	/**
	 * @var Server
	 */
	protected $server;

	/**
	 * @var \OC_Defaults
	 */
	protected $themingDefaults;

	/**
	 * AppleProvisioningPlugin constructor.
	 */
	public function __construct(
		protected IUserSession $userSession,
		protected IURLGenerator $urlGenerator,
		\OC_Defaults $themingDefaults,
		protected IRequest $request,
		protected IL10N $l10n,
		protected \Closure $uuidClosure,
	) {
		$this->themingDefaults = $themingDefaults;
	}

	/**
	 * @param Server $server
	 */
	public function initialize(Server $server) {
		$this->server = $server;
		$this->server->on('method:GET', [$this, 'httpGet'], 90);
	}

	/**
	 * @param RequestInterface $request
	 * @param ResponseInterface $response
	 * @return boolean
	 */
	public function httpGet(RequestInterface $request, ResponseInterface $response):bool {
		if ($request->getPath() !== 'provisioning/' . AppleProvisioningNode::FILENAME) {
			return true;
		}

		$user = $this->userSession->getUser();
		if (!$user) {
			return true;
		}

		$serverProtocol = $this->request->getServerProtocol();
		$useSSL = ($serverProtocol === 'https');

		if (!$useSSL) {
			$response->setStatus(Http::STATUS_OK);
			$response->setHeader('Content-Type', 'text/plain; charset=utf-8');
			$response->setBody($this->l10n->t('Your %s needs to be configured to use HTTPS in order to use CalDAV and CardDAV with iOS/macOS.', [$this->themingDefaults->getName()]));

			return false;
		}

		$absoluteURL = $this->urlGenerator->getBaseUrl();
		$parsedUrl = parse_url($absoluteURL);
		$serverPort = $parsedUrl['port'] ?? 443;
		$server_url = $parsedUrl['host'];

		$description = $this->themingDefaults->getName();
		$userId = $user->getUID();

		$reverseDomain = implode('.', array_reverse(explode('.', $parsedUrl['host'])));

		$caldavUUID = call_user_func($this->uuidClosure);
		$carddavUUID = call_user_func($this->uuidClosure);
		$profileUUID = call_user_func($this->uuidClosure);

		$caldavIdentifier = $reverseDomain . '.' . $caldavUUID;
		$carddavIdentifier = $reverseDomain . '.' . $carddavUUID;
		$profileIdentifier = $reverseDomain . '.' . $profileUUID;

		$caldavDescription = $this->l10n->t('Configures a CalDAV account');
		$caldavDisplayname = $description . ' CalDAV';
		$carddavDescription = $this->l10n->t('Configures a CardDAV account');
		$carddavDisplayname = $description . ' CardDAV';

		$filename = $userId . '-' . AppleProvisioningNode::FILENAME;

		$xmlSkeleton = $this->getTemplate();
		$body = vsprintf($xmlSkeleton, array_map(function (string $v) {
			return \htmlspecialchars($v, ENT_XML1, 'UTF-8');
		}, [
			$description,
			$server_url,
			$userId,
			$serverPort,
			$caldavDescription,
			$caldavDisplayname,
			$caldavIdentifier,
			$caldavUUID,
			$description,
			$server_url,
			$userId,
			$serverPort,
			$carddavDescription,
			$carddavDisplayname,
			$carddavIdentifier,
			$carddavUUID,
			$description,
			$profileIdentifier,
			$profileUUID
		]
		));

		$response->setStatus(Http::STATUS_OK);
		$response->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"');
		$response->setHeader('Content-Type', 'application/xml; charset=utf-8');
		$response->setBody($body);

		return false;
	}

	/**
	 * @return string
	 */
	private function getTemplate():string {
		return <<<EOF
<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE plist PUBLIC "-//Apple//DTD PLIST 1.0//EN" "http://www.apple.com/DTDs/PropertyList-1.0.dtd">
<plist version="1.0">
<dict>
	<key>PayloadContent</key>
	<array>
		<dict>
			<key>CalDAVAccountDescription</key>
			<string>%s</string>
			<key>CalDAVHostName</key>
			<string>%s</string>
			<key>CalDAVUsername</key>
			<string>%s</string>
			<key>CalDAVUseSSL</key>
			<true/>
			<key>CalDAVPort</key>
			<integer>%s</integer>
			<key>PayloadDescription</key>
			<string>%s</string>
			<key>PayloadDisplayName</key>
			<string>%s</string>
			<key>PayloadIdentifier</key>
			<string>%s</string>
			<key>PayloadType</key>
			<string>com.apple.caldav.account</string>
			<key>PayloadUUID</key>
			<string>%s</string>
			<key>PayloadVersion</key>
			<integer>1</integer>
		</dict>
		<dict>
			<key>CardDAVAccountDescription</key>
			<string>%s</string>
			<key>CardDAVHostName</key>
			<string>%s</string>
			<key>CardDAVUsername</key>
			<string>%s</string>
			<key>CardDAVUseSSL</key>
			<true/>
			<key>CardDAVPort</key>
			<integer>%s</integer>
			<key>PayloadDescription</key>
			<string>%s</string>
			<key>PayloadDisplayName</key>
			<string>%s</string>
			<key>PayloadIdentifier</key>
			<string>%s</string>
			<key>PayloadType</key>
			<string>com.apple.carddav.account</string>
			<key>PayloadUUID</key>
			<string>%s</string>
			<key>PayloadVersion</key>
			<integer>1</integer>
		</dict>
	</array>
	<key>PayloadDisplayName</key>
	<string>%s</string>
	<key>PayloadIdentifier</key>
	<string>%s</string>
	<key>PayloadRemovalDisallowed</key>
	<false/>
	<key>PayloadType</key>
	<string>Configuration</string>
	<key>PayloadUUID</key>
	<string>%s</string>
	<key>PayloadVersion</key>
	<integer>1</integer>
</dict>
</plist>

EOF;
	}
}
