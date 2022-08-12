<?php

declare(strict_types=1);
/**
 * @copyright Copyright (c) 2022 Julius Härtl <jus@bitgrid.net>
 *
 * @author Julius Härtl <jus@bitgrid.net>
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
 */

namespace OC\Core\Controller;

use OCP\AppFramework\Http\DataResponse;
use OC\Collaboration\Reference\ReferenceManager;
use OCP\IRequest;

class ReferenceApiController extends \OCP\AppFramework\OCSController {
	private ReferenceManager $referenceManager;

	public function __construct($appName, IRequest $request, ReferenceManager $referenceManager) {
		parent::__construct($appName, $request);
		$this->referenceManager = $referenceManager;
	}

	/**
	 * @NoAdminRequired
	 *
	 * @param string $text
	 * @param bool $resolve
	 * @return DataResponse
	 */
	public function extract(string $text, bool $resolve = false, int $limit = 1): DataResponse {
		$references = $this->referenceManager->extractReferences($text);

		$result = [];
		$index = 0;
		foreach ($references as $reference) {
			if ($index++ < $limit) {
				$result[$reference] = $resolve ? $this->referenceManager->resolveReference($reference) : null;
			}
		}

		return new DataResponse([
			'references' => $result
		]);
	}


	/**
	 * @NoAdminRequired
	 *
	 * @param array $references
	 * @return DataResponse
	 */
	public function resolve(array $references, int $limit = 1): DataResponse {
		$result = [];
		$index = 0;
		foreach ($references as $reference) {
			if ($index++ < $limit) {
				$result[$reference] = $this->referenceManager->resolveReference($reference);
			}
		}

		return new DataResponse([
			'references' => array_filter($result)
		]);
	}
}
