<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\User_LDAP\PagedResults;

trait TLinkId {
	public function getLinkId($link) {
		if (is_object($link)) {
			return spl_object_id($link);
		} elseif (is_resource($link)) {
			return (int)$link;
		} elseif (is_array($link) && isset($link[0])) {
			if (is_object($link[0])) {
				return spl_object_id($link[0]);
			} elseif (is_resource($link[0])) {
				return (int)$link[0];
			}
		}
		throw new \RuntimeException('No resource provided');
	}
}
