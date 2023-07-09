<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Julius Härtl <jus@bitgrid.net>
 * @author Lukas Reschke <lukas@statuscode.ch>
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
 * A generic 404 response showing an 404 error page as well to the end-user
 * @since 8.1.0
 * @template S of int
 * @template H of array<string, mixed>
 * @template-extends TemplateResponse<int, array<string, mixed>>
 */
class NotFoundResponse extends TemplateResponse {
	/**
	 * @param S $status
	 * @param H $headers
	 * @since 8.1.0
	 */
	public function __construct(int $status = Http::STATUS_NOT_FOUND, array $headers = []) {
		parent::__construct('core', '404', [], 'guest', $status, $headers);

		$this->setContentSecurityPolicy(new ContentSecurityPolicy());
	}
}
