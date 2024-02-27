<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2020 Arthur Schiwon <blizzz@arthur-schiwon.de>
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
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
