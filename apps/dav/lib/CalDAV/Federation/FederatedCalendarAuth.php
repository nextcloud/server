<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\CalDAV\Federation;

use OCA\DAV\DAV\RemoteUserPrincipalBackend;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\Defaults;
use OCP\IDBConnection;
use Sabre\DAV\Auth\Backend\BackendInterface;
use Sabre\HTTP\Auth\Basic as BasicAuth;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;

final class FederatedCalendarAuth implements BackendInterface {
	private readonly string $realm;

	public function __construct(
		private readonly IDBConnection $db,
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
		$remoteUserPrincipalUri = RemoteUserPrincipalBackend::PRINCIPAL_PREFIX . '/' . base64_encode($username);
		[, $remoteUserPrincipalId] = \Sabre\Uri\split($remoteUserPrincipalUri);

		$qb = $this->db->getQueryBuilder();
		$qb->select('c.uri', 'c.principaluri')
			->from('dav_shares', 'ds')
			->join('ds', 'calendars', 'c', $qb->expr()->eq(
				'ds.resourceid',
				'c.id',
				IQueryBuilder::PARAM_INT,
			))
			->where($qb->expr()->eq(
				'ds.type',
				$qb->createNamedParameter('calendar', IQueryBuilder::PARAM_STR),
				IQueryBuilder::PARAM_STR,
			))
			->andWhere($qb->expr()->eq(
				'ds.principaluri',
				$qb->createNamedParameter($remoteUserPrincipalUri, IQueryBuilder::PARAM_STR),
				IQueryBuilder::PARAM_STR,
			))
			->andWhere($qb->expr()->eq(
				'ds.token',
				$qb->createNamedParameter($password, IQueryBuilder::PARAM_STR),
				IQueryBuilder::PARAM_STR,
			));
		$result = $qb->executeQuery();
		$rows = $result->fetchAll();
		$result->closeCursor();

		// Is the requested calendar actually shared with the remote user?
		foreach ($rows as $row) {
			$ownerPrincipalUri = $row['principaluri'];
			[, $ownerUserId] = \Sabre\Uri\split($ownerPrincipalUri);
			$shareUri = $row['uri'] . "_shared_by_" . $ownerUserId;
			if (str_starts_with($requestPath, "remote-calendars/$remoteUserPrincipalId/$shareUri")) {
				// Yes? -> return early
				return $remoteUserPrincipalUri;
			}
		}

		return null;
	}

	public function check(RequestInterface $request, ResponseInterface $response): array {
		$auth = new BasicAuth($this->realm, $request, $response);

		$userpass = $auth->getCredentials();
		if (!$userpass) {
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
