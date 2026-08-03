<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\DAV\DAV;

use Sabre\DAVACL\PrincipalBackend\AbstractBackend;

class SystemPrincipalBackend extends AbstractBackend {
	#[\Override]
	public function getPrincipalsByPrefix($prefixPath): array {
		$principals = [];

		if ($prefixPath === 'principals/system') {
			$principals[] = [
				'uri' => 'principals/system/system',
				'{DAV:}displayname' => 'system',
			];
			$principals[] = [
				'uri' => 'principals/system/public',
				'{DAV:}displayname' => 'public',
			];
		}

		return $principals;
	}

	#[\Override]
	public function getPrincipalByPath($path): array {
		if ($path === 'principals/system/system') {
			$principal = [
				'uri' => 'principals/system/system',
				'{DAV:}displayname' => 'system',
			];
			return $principal;
		}
		if ($path === 'principals/system/public') {
			$principal = [
				'uri' => 'principals/system/public',
				'{DAV:}displayname' => 'public',
			];
			return $principal;
		}

		return [];
	}

	#[\Override]
	public function updatePrincipal($path, \Sabre\DAV\PropPatch $propPatch): void {
	}

	#[\Override]
	public function searchPrincipals($prefixPath, array $searchProperties, $test = 'allof'): array {
		return [];
	}

	#[\Override]
	public function getGroupMemberSet($principal): array {
		// TODO: for now the group principal has only one member, the user itself
		$principal = $this->getPrincipalByPath($principal);
		if ($principal !== []) {
			throw new \Sabre\DAV\Exception('Principal not found');
		}

		return [$principal['uri']];
	}

	#[\Override]
	public function getGroupMembership($principal): array {
		[$prefix, ] = \Sabre\Uri\split($principal);

		if ($prefix === 'principals/system') {
			$principal = $this->getPrincipalByPath($principal);
			if (!$principal) {
				throw new \Sabre\DAV\Exception('Principal not found');
			}

			return [];
		}
		return [];
	}

	#[\Override]
	public function setGroupMemberSet($principal, array $members): void {
		throw new \Sabre\DAV\Exception('Setting members of the group is not supported yet');
	}
}
