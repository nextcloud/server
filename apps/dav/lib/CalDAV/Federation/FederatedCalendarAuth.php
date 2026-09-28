<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\CalDAV\Federation;

use OCA\DAV\DAV\RemoteUserPrincipalBackend;
use OCA\DAV\DAV\Sharing\SharingMapper;
use OCP\Defaults;
use Sabre\DAV\Auth\Backend\BackendInterface;
use Sabre\HTTP\Auth\Basic as BasicAuth;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;

class FederatedCalendarAuth implements BackendInterface {
	private readonly string $realm;

	public function __construct(
		private readonly SharingMapper $sharingMapper,
	) {
		$defaults = new Defaults();
		$this->realm = $defaults->getName();
	}

	/**
	 * @return string|null A principal uri if the given combination of user and pass is valid and null otherwise.
	 */
	private function validateUserPass(
		string $requestPath,
		string $username,
		string $password,
	): ?string {
		$remoteUserPrincipalUri = RemoteUserPrincipalBackend::PRINCIPAL_PREFIX . "/$username";
		[, $remoteUserPrincipalId] = \Sabre\Uri\split($remoteUserPrincipalUri);

		$rows = $this->sharingMapper->getSharedCalendarsForRemoteUser(
			$remoteUserPrincipalUri,
			$password,
		);

		// Is the requested calendar actually shared with the remote user?
		foreach ($rows as $row) {
			$ownerPrincipalUri = $row['principaluri'];
			[, $ownerUserId] = \Sabre\Uri\split($ownerPrincipalUri);
			$shareUri = $row['uri'] . '_shared_by_' . $ownerUserId;
			if (str_starts_with($requestPath, "remote-calendars/$remoteUserPrincipalId/$shareUri")) {
				// Yes? -> return early
				return $remoteUserPrincipalUri;
			}
		}

		return null;
	}

	public function check(RequestInterface $request, ResponseInterface $response): array {
		if (!str_starts_with($request->getPath(), 'remote-calendars/')) {
			return [false, 'This request is not for a federated calendar'];
		}

		$auth = new BasicAuth($this->realm, $request, $response);
		$userpass = $auth->getCredentials();
		if ($userpass === null || count($userpass) !== 2) {
			return [false, "No 'Authorization: Basic' header found. Either the client didn't send one, or the server is misconfigured"];
		}
		$principal = $this->validateUserPass($request->getPath(), $userpass[0], $userpass[1]);
		if ($principal === null) {
			return [false, 'Username or password was incorrect'];
		}

		return [true, $principal];
	}

	public function challenge(RequestInterface $request, ResponseInterface $response): void {
		// No special challenge is needed here
	}
}
