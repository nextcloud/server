<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Core;

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
 *     resources: list<CoreResource>,
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
 *     search_providers_ids: ?list<string>,
 * }
 *
 * @psalm-type CoreUnifiedSearchProvider = array{
 *     id: string,
 *     appId: string,
 *     name: string,
 *     icon: string,
 *     order: int,
 *     triggers: list<string>,
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
 *     attributes: list<string>,
 * }
 *
 * @psalm-type CoreUnifiedSearchResult = array{
 *     name: string,
 *     isPaginated: bool,
 *     entries: list<CoreUnifiedSearchResultEntry>,
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
 *
 * @psalm-type CoreTeam = array{
 *      id: string,
 *      name: string,
 *      icon: string,
 * }
 *
 * @psalm-type CoreTeamResource = array{
 *       id: int,
 *       label: string,
 *       url: string,
 *       iconSvg: ?string,
 *       iconURL: ?string,
 *       iconEmoji: ?string,
 *   }
 *
 * @psalm-type CoreTaskProcessingShape = array{
 *     name: string,
 *     description: string,
 *     type: "Number"|"Text"|"Audio"|"Image"|"Video"|"File"|"Enum"|"ListOfNumbers"|"ListOfTexts"|"ListOfImages"|"ListOfAudios"|"ListOfVideos"|"ListOfFiles",
 * }
 *
 * @psalm-type CoreTaskProcessingTaskType = array{
 *     name: string,
 *     description: string,
 *     inputShape: array<string, CoreTaskProcessingShape>,
 *     inputShapeEnumValues: array<string, list<array{name: string, value: string}>>,
 *     inputShapeDefaults: array<string, numeric|string>,
 *     optionalInputShape: array<string, CoreTaskProcessingShape>,
 *     optionalInputShapeEnumValues: array<string, list<array{name: string, value: string}>>,
 *     optionalInputShapeDefaults: array<string, numeric|string>,
 *     outputShape: array<string, CoreTaskProcessingShape>,
 *     outputShapeEnumValues: array<string, list<array{name: string, value: string}>>,
 *     optionalOutputShape: array<string, CoreTaskProcessingShape>,
 *     optionalOutputShapeEnumValues: array<string, list<array{name: string, value: string}>>,
 * }
 *
 * @psalm-type CoreTaskProcessingIO = array<string, numeric|list<numeric>|string|list<string>>
 *
 * @psalm-type CoreTaskProcessingTask = array{
 *     id: int,
 *     lastUpdated: int,
 *     type: string,
 *     status: 'STATUS_CANCELLED'|'STATUS_FAILED'|'STATUS_SUCCESSFUL'|'STATUS_RUNNING'|'STATUS_SCHEDULED'|'STATUS_UNKNOWN',
 *     userId: ?string,
 *     appId: string,
 *     input: CoreTaskProcessingIO,
 *     output: null|CoreTaskProcessingIO,
 *     customId: ?string,
 *     completionExpectedAt: ?int,
 *     progress: ?float,
 *     scheduledAt: ?int,
 *     startedAt: ?int,
 *     endedAt: ?int,
 * }
 *
 */
class ResponseDefinitions {
}
