<?php
/**
 * @copyright 2016 Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Kate Döen <kate.doeen@nextcloud.com>
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
namespace OC\AppFramework\OCS;

use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCSController;

/**
 * @psalm-import-type DataResponseType from DataResponse
 * @template S of int
 * @template-covariant T of DataResponseType
 * @template H of array<string, mixed>
 * @template-extends BaseResponse<int, DataResponseType, array<string, mixed>>
 */
class V1Response extends BaseResponse {
	/**
	 * The V1 endpoint has very limited http status codes basically everything
	 * is status 200 except 401
	 *
	 * @return int
	 */
	public function getStatus() {
		$status = parent::getStatus();
		if ($status === OCSController::RESPOND_UNAUTHORISED) {
			return Http::STATUS_UNAUTHORIZED;
		}

		return Http::STATUS_OK;
	}

	/**
	 * In v1 all OK is 100
	 *
	 * @return int
	 */
	public function getOCSStatus() {
		$status = parent::getOCSStatus();

		if ($status === Http::STATUS_OK) {
			return 100;
		}

		return $status;
	}

	/**
	 * Construct the meta part of the response
	 * And then late the base class render
	 *
	 * @return string
	 */
	public function render() {
		$meta = [
			'status' => $this->getOCSStatus() === 100 ? 'ok' : 'failure',
			'statuscode' => $this->getOCSStatus(),
			'message' => $this->getOCSStatus() === 100 ? 'OK' : $this->statusMessage ?? '',
			'totalitems' => (string)($this->itemsCount ?? ''),
			'itemsperpage' => (string)($this->itemsPerPage ?? ''),
		];

		return $this->renderResult($meta);
	}
}
