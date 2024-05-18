<?php

declare(strict_types=1);

/**
 * @copyright Bjoern Schiessle <bjoern@schiessle.org>
 *
 * @author Bjoern Schiessle <bjoern@schiessle.org>
 * @author Joas Schilling <coding@schilljs.com>
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
namespace OCA\AdminAudit\Actions;

class Versions extends Action {
	public function rollback(array $params): void {
		$this->log('Version "%s" of "%s" was restored.',
			[
				'version' => $params['revision'],
				'path' => $params['path']
			],
			['version', 'path']
		);
	}

	public function delete(array $params): void {
		$this->log('Version "%s" was deleted.',
			['path' => $params['path']],
			['path']
		);
	}
}
