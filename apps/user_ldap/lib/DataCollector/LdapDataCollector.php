<?php

declare(strict_types = 1);
/**
 * @copyright 2022 Carl Schwan <carl@carlschwan.eu>
 *
 * @author Carl Schwan <carl@carlschwan.eu>
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

namespace OCA\User_LDAP\DataCollector;

use OC\AppFramework\Http\Request;
use OCP\AppFramework\Http\Response;
use OCP\DataCollector\AbstractDataCollector;

class LdapDataCollector extends AbstractDataCollector {
	public function startLdapRequest(string $query, array $args, array $backtrace): void {
		$this->data[] = [
			'start' => microtime(true),
			'query' => $query,
			'args' => $args,
			'end' => microtime(true),
			'backtrace' => $backtrace,
		];
	}

	public function stopLastLdapRequest(): void {
		$this->data[count($this->data) - 1]['end'] = microtime(true);
	}

	public function getName(): string {
		return 'ldap';
	}

	public function collect(Request $request, Response $response, \Throwable $exception = null): void {
	}
}
