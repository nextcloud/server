<?php
/**
 * @copyright Copyright (c) 2016 Joas Schilling <coding@schilljs.com>
 *
 * @author Joas Schilling <coding@schilljs.com>
 * @author Morris Jobke <hey@morrisjobke.de>
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
namespace OCA\WorkflowEngine\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;

class RequestTime extends Controller {

	/**
	 * @NoAdminRequired
	 *
	 * @param string $search
	 * @return JSONResponse
	 */
	public function getTimezones($search = '') {
		$timezones = \DateTimeZone::listIdentifiers();

		if ($search !== '') {
			$timezones = array_filter($timezones, function ($timezone) use ($search) {
				return stripos($timezone, $search) !== false;
			});
		}

		$timezones = array_slice($timezones, 0, 10);

		$response = [];
		foreach ($timezones as $timezone) {
			$response[$timezone] = $timezone;
		}
		return new JSONResponse($response);
	}
}
