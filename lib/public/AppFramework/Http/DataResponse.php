<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Bernhard Posselt <dev@bernhard-posselt.com>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
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
 * A generic DataResponse class that is used to return generic data responses
 * for responders to transform
 * @since 8.0.0
 * @psalm-type DataResponseType = array|int|float|string|bool|object|null|\stdClass|\JsonSerializable
 * @template S of int
 * @template-covariant T of DataResponseType
 * @template H of array<string, mixed>
 * @template-extends Response<int, array<string, mixed>>
 */
class DataResponse extends Response {
	/**
	 * response data
	 * @var T
	 */
	protected $data;


	/**
	 * @param T $data the object or array that should be transformed
	 * @param S $statusCode the Http status code, defaults to 200
	 * @param H $headers additional key value based headers
	 * @since 8.0.0
	 */
	public function __construct(mixed $data = [], int $statusCode = Http::STATUS_OK, array $headers = []) {
		parent::__construct($statusCode, $headers);

		$this->data = $data;
	}


	/**
	 * Sets values in the data json array
	 * @psalm-suppress InvalidTemplateParam
	 * @param T $data an array or object which will be transformed
	 * @return DataResponse Reference to this object
	 * @since 8.0.0
	 */
	public function setData($data) {
		$this->data = $data;

		return $this;
	}


	/**
	 * Used to get the set parameters
	 * @return T the data
	 * @since 8.0.0
	 */
	public function getData() {
		return $this->data;
	}
}
