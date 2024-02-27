<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2020 Joas Schilling <coding@schilljs.com>
 *
 * @author Joas Schilling <coding@schilljs.com>
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
namespace OCP\AppFramework\Http;

use OCP\AppFramework\Http;
use OCP\Template;

/**
 * A generic 429 response showing an 404 error page as well to the end-user
 * @since 19.0.0
 * @template S of int
 * @template H of array<string, mixed>
 * @template-extends Response<int, array<string, mixed>>
 */
class TooManyRequestsResponse extends Response {
	/**
	 * @param S $status
	 * @param H $headers
	 * @since 19.0.0
	 */
	public function __construct(int $status = Http::STATUS_TOO_MANY_REQUESTS, array $headers = []) {
		parent::__construct($status, $headers);

		$this->setContentSecurityPolicy(new ContentSecurityPolicy());
	}

	/**
	 * @return string
	 * @since 19.0.0
	 */
	public function render() {
		$template = new Template('core', '429', 'blank');
		return $template->fetchPage();
	}
}
