<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2019, Roeland Jago Douma <roeland@famdouma.nl>
 *
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
namespace OCP\AppFramework\Http;

/**
 * A template response that does not emit the loadAdditionalScripts events.
 *
 * This is useful for pages that are authenticated but do not yet show the
 * full nextcloud UI. Like the 2FA page, or the grant page in the login flow.
 *
 * @since 16.0.0
 * @template S of int
 * @template H of array<string, mixed>
 * @template-extends TemplateResponse<int, array<string, mixed>>
 */
class StandaloneTemplateResponse extends TemplateResponse {
}
