<?php

declare(strict_types=1);

/*
 * @copyright 2022 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2022 Christoph Wurst <christoph@winzerhof-wurst.at>
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

namespace OC\Core\Middleware;

use Exception;
use OC\AppFramework\Exceptions\ParameterMissingException;
use OC\Http\ApiResponse;
use OCP\AppFramework\Http;
use OCP\AppFramework\Middleware;
use OCP\IRequest;

class ApiErrorMiddleware extends Middleware {
	private IRequest $request;

	public function __construct(IRequest $request) {
		$this->request = $request;
	}

	public function afterException($controller, $methodName, Exception $exception) {
		$sendsJson = $this->request->getHeader('Content-Type') === 'application/json';
		$acceptsJson = $this->request->getHeader('Accept') === 'application/json';
		if (($sendsJson || $acceptsJson) && $exception instanceof ParameterMissingException) {
			return ApiResponse::fail(
				['error' => 'Required parameter ' . $exception->getParameterName() . ' missing'],
				Http::STATUS_UNPROCESSABLE_ENTITY,
			);
		}
		return parent::afterException($controller, $methodName, $exception);
	}
}
