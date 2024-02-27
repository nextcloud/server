<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Bernhard Posselt <dev@bernhard-posselt.com>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Thomas Tanghus <thomas@tanghus.net>
 * @author Kate Döen <kate.doeen@nextcloud.com>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OCP\AppFramework\Http;

use OCP\AppFramework\Http;

/**
 * A renderer for JSON calls
 * @since 6.0.0
 * @template S of int
 * @template-covariant T of array|object|\stdClass|\JsonSerializable
 * @template H of array<string, mixed>
 * @template-extends Response<int, array<string, mixed>>
 */
class JSONResponse extends Response {
	/**
	 * response data
	 * @var T
	 */
	protected $data;


	/**
	 * constructor of JSONResponse
	 * @param T $data the object or array that should be transformed
	 * @param S $statusCode the Http status code, defaults to 200
	 * @param H $headers
	 * @since 6.0.0
	 */
	public function __construct(mixed $data = [], int $statusCode = Http::STATUS_OK, array $headers = []) {
		parent::__construct($statusCode, $headers);

		$this->data = $data;
		$this->addHeader('Content-Type', 'application/json; charset=utf-8');
	}


	/**
	 * Returns the rendered json
	 * @return string the rendered json
	 * @since 6.0.0
	 * @throws \Exception If data could not get encoded
	 */
	public function render() {
		return json_encode($this->data, JSON_HEX_TAG | JSON_THROW_ON_ERROR);
	}

	/**
	 * Sets values in the data json array
	 * @psalm-suppress InvalidTemplateParam
	 * @param T $data an array or object which will be transformed
	 *                             to JSON
	 * @return JSONResponse Reference to this object
	 * @since 6.0.0 - return value was added in 7.0.0
	 */
	public function setData($data) {
		$this->data = $data;

		return $this;
	}


	/**
	 * @return T the data
	 * @since 6.0.0
	 */
	public function getData() {
		return $this->data;
	}
}
