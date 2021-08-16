<?php


declare(strict_types=1);


/**
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
 * @copyright 2021
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


namespace OCP\Circles\Exceptions;

use Exception;
use JsonSerializable;
use OCP\AppFramework\Http;
use Throwable;

/**
 * Class FederatedItemException
 *
 * @package OCP\Circles\Exceptions
 */
class FederatedItemException extends Exception implements JsonSerializable {
	public static $CHILDREN = [
		FederatedItemBadRequestException::class,
		FederatedItemConflictException::class,
		FederatedItemForbiddenException::class,
		FederatedItemNotFoundException::class,
		FederatedItemRemoteException::class,
		FederatedItemServerException::class,
		FederatedItemUnauthorizedException::class
	];


	/** @var int */
	private $status = Http::STATUS_BAD_REQUEST;


	/**
	 * FederatedItemException constructor.
	 *
	 * @param string $message
	 * @param int $code
	 * @param Throwable|null $previous
	 */
	public function __construct(string $message = '', int $code = 0, ?Throwable $previous = null) {
		parent::__construct($message, ($code > 0) ? $code : $this->status, $previous);
	}


	/**
	 * @param int $status
	 */
	protected function setStatus(int $status): void {
		$this->status = $status;
	}

	/**
	 * @return int
	 */
	public function getStatus(): int {
		return $this->status;
	}


	/**
	 * @return array
	 */
	public function jsonSerialize(): array {
		return [
			'class' => get_class($this),
			'status' => $this->getStatus(),
			'code' => $this->getCode(),
			'message' => $this->getMessage()
		];
	}
}
