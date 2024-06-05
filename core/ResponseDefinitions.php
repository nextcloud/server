<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2023 Kate Döen <kate.doeen@nextcloud.com>
 *
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

namespace OCA\Core;

/**
 * @psalm-type CoreLoginFlowV2Credentials = array{
 *     server: string,
 *     loginName: string,
 *     appPassword: string,
 * }
 *
 * @psalm-type CoreLoginFlowV2 = array{
 *     poll: array{
 *         token: string,
 *         endpoint: string,
 *       },
 *     login: string,
 * }
 *
 * @psalm-type CoreNavigationEntry = array{
 *     id: string,
 *     order?: int,
 *     href: string,
 *     icon: string,
 *     type: string,
 *     name: string,
 *     app?: string,
 *     default?: bool,
 *     active: bool,
 *     classes: string,
 *     unread: int,
 * }
 *
 * @psalm-type CoreContactsAction = array{
 *     title: string,
 *     icon: string,
 *     hyperlink: string,
 *     appId: string,
 * }
 *
 * @psalm-type CoreOpenGraphObject = array{
 *     id: string,
 *     name: string,
 *     description: ?string,
 *     thumb: ?string,
 *     link: string,
 * }
 *
 * @psalm-type CoreResource = array{
 *     richObjectType: string,
 *     richObject: array<string, ?mixed>,
 *     openGraphObject: CoreOpenGraphObject,
 *     accessible: bool,
 * }
 *
 * @psalm-type CoreCollection = array{
 *     id: int,
 *     name: string,
 *     resources: CoreResource[],
 * }
 *
 * @psalm-type CoreReference = array{
 *     richObjectType: string,
 *     richObject: array<string, ?mixed>,
 *     openGraphObject: CoreOpenGraphObject,
 *     accessible: bool,
 * }
 *
 * @psalm-type CoreReferenceProvider = array{
 *     id: string,
 *     title: string,
 *     icon_url: string,
 *     order: int,
 *     search_providers_ids: ?string[]
 * }
 *
 * @psalm-type CoreUnifiedSearchProvider = array{
 *     id: string,
 *     appId: string,
 *     name: string,
 *     icon: string,
 *     order: int,
 *     triggers: string[],
 *     filters: array<string, string>,
 *     inAppSearch: bool,
 * }
 *
 * @psalm-type CoreUnifiedSearchResultEntry = array{
 *     thumbnailUrl: string,
 *     title: string,
 *     subline: string,
 *     resourceUrl: string,
 *     icon: string,
 *     rounded: bool,
 *     attributes: string[],
 * }
 *
 * @psalm-type CoreUnifiedSearchResult = array{
 *     name: string,
 *     isPaginated: bool,
 *     entries: CoreUnifiedSearchResultEntry[],
 *     cursor: int|string|null,
 * }
 *
 * @psalm-type CoreAutocompleteResult = array{
 *     id: string,
 *     label: string,
 *     icon: string,
 *     source: string,
 *     status: array{
 *       status: string,
 *       message: ?string,
 *       icon: ?string,
 *       clearAt: ?int,
 *     }|string,
 *     subline: string,
 *     shareWithDisplayNameUnique: string,
 * }
 *
 * @psalm-type CoreTextProcessingTask = array{
 *     id: ?int,
 *     type: string,
 *     status: 0|1|2|3|4,
 *     userId: ?string,
 *     appId: string,
 *     input: string,
 *     output: ?string,
 *     identifier: string,
 *     completionExpectedAt: ?int
 * }
 *
 * @psalm-type CoreTextToImageTask = array{
 *      id: ?int,
 *      status: 0|1|2|3|4,
 *      userId: ?string,
 *      appId: string,
 *      input: string,
 *      identifier: ?string,
 *      numberOfImages: int,
 *      completionExpectedAt: ?int,
 *  }
 */
class ResponseDefinitions {
}
