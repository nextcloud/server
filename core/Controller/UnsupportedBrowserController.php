<?php

declare(strict_types=1);

/**
 * @copyright 2021 John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @author John Molakvoæ <skjnldsv@protonmail.com>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OC\Core\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\OpenAPI;
use OCP\AppFramework\Http\Response;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IRequest;
use OCP\Util;

#[OpenAPI(scope: OpenAPI::SCOPE_IGNORE)]
class UnsupportedBrowserController extends Controller {
	public function __construct(IRequest $request) {
		parent::__construct('core', $request);
	}

	/**
	 * @PublicPage
	 * @NoCSRFRequired
	 *
	 * @return Response
	 */
	public function index(): Response {
		Util::addScript('core', 'unsupported-browser');
		Util::addStyle('core', 'icons');

		// not using RENDER_AS_ERROR as we need the JSConfigHelper for url generation
		return new TemplateResponse('core', 'unsupportedbrowser', [], TemplateResponse::RENDER_AS_GUEST);
	}
}
