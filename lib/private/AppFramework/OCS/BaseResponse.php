<?php
/**
 * @copyright 2016 Roeland Jago Douma <roeland@famdouma.nl>
 *
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */
namespace OC\AppFramework\OCS;

use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\EmptyContentSecurityPolicy;
use OCP\AppFramework\Http\Response;

abstract class BaseResponse extends Response   {
	/** @var array */
	protected $data;

	/** @var string */
	protected $format;

	/** @var string */
	protected $statusMessage;

	/** @var int */
	protected $itemsCount;

	/** @var int */
	protected $itemsPerPage;

	/**
	 * BaseResponse constructor.
	 *
	 * @param DataResponse|null $dataResponse
	 * @param string $format
	 * @param string|null $statusMessage
	 * @param int|null $itemsCount
	 * @param int|null $itemsPerPage
	 */
	public function __construct(DataResponse $dataResponse,
								$format = 'xml',
								$statusMessage = null,
								$itemsCount = null,
								$itemsPerPage = null) {
		$this->format = $format;
		$this->statusMessage = $statusMessage;
		$this->itemsCount = $itemsCount;
		$this->itemsPerPage = $itemsPerPage;

		$this->data = $dataResponse->getData();

		$this->setHeaders($dataResponse->getHeaders());
		$this->setStatus($dataResponse->getStatus());
		$this->setETag($dataResponse->getETag());
		$this->setLastModified($dataResponse->getLastModified());
		$this->setCookies($dataResponse->getCookies());
		$this->setContentSecurityPolicy(new EmptyContentSecurityPolicy());

		if ($format === 'json') {
			$this->addHeader(
				'Content-Type', 'application/json; charset=utf-8'
			);
		} else {
			$this->addHeader(
				'Content-Type', 'application/xml; charset=utf-8'
			);
		}
	}

	/**
	 * @param string[] $meta
	 * @return string
	 */
	protected function renderResult($meta) {
		// TODO rewrite functions
		return \OC_API::renderResult($this->format, $meta, $this->data);
	}

	public function getOCSStatus() {
		return parent::getStatus();
	}
}
