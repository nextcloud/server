<?php

declare(strict_types=1);

/**
 * @copyright 2021 Lukas Reschke <lukas@statuscode.ch>
 *
 * @author 2021 Lukas Reschke <lukas@statuscode.ch>
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
 */

namespace OCP\AppFramework\Http;

use OCP\AppFramework\Http;

/**
 * A renderer for text responses
 * @since 22.0.0
 */
class TextPlainResponse extends Response {
	/** @var string */
	private $text = '';

	/**
	 * constructor of TextPlainResponse
	 * @param string $text The text body
	 * @param int $statusCode the Http status code, defaults to 200
	 * @since 22.0.0
	 */
	public function __construct(string $text = '', int $statusCode = Http::STATUS_OK) {
		parent::__construct();

		$this->text = $text;
		$this->setStatus($statusCode);
		$this->addHeader('Content-Type', 'text/plain');
	}


	/**
	 * Returns the text
	 * @return string
	 * @since 22.0.0
	 * @throws \Exception If data could not get encoded
	 */
	public function render() : string {
		return $this->text;
	}
}
