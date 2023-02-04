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

namespace OCA\Files_Sharing;

/**
 * @psalm-type UserDetails = array{
 *     additional_mail: string[],
 *     additional_mailScope: string[]|null,
 *     address: string,
 *     addressScope: string|null,
 *     avatarScope: string|null,
 *     backend: string,
 *     backendCapabilities: array{
 *         setDisplayName: bool,
 *         setPassword: bool
 *     },
 *     biography: string,
 *     biographyScope: string|null,
 *     displayname: string|null,
 *     display-name: string|null,
 *     displaynameScope: string|null,
 *     email: string|null,
 *     emailScope: string|null,
 *     enabled: bool|null,
 *     fediverse: string|null,
 *     fediverseScope: string|null,
 *     groups: string[],
 *     headline: string,
 *     headlineScope: string|null,
 *     id: string,
 *     language: string,
 *     lastLogin: int,
 *     locale: string,
 *     notify_email: string|null,
 *     organisation: string,
 *     organisationScope: string|null,
 *     phone: string,
 *     phoneScope: string|null,
 *     profile_enabled: string,
 *     profile_enabledScope: string|null,
 *     quota: array{
 *         free: int|null,
 *         quota: string|int|bool,
 *         relative: float|null,
 *         total: int|null,
 *         used: int,
 *	   },
 *     role: string,
 *     roleScope: string|null,
 *     storageLocation: string|null,
 *     subadmin: string[],
 *     twitter: string,
 *     twitterScope: string|null,
 *     website: string,
 *     websiteScope: string|null,
 * }
 *
 * @psalm-type AppInfoValue = string|array{}|array
 *
 * @psalm-type AppInfo = array{
 *     active: bool|null,
 *     activity: AppInfoValue|null,
 *     author: AppInfoValue|null,
 *     background-jobs: AppInfoValue|null,
 *     bugs: AppInfoValue|null,
 *     category: AppInfoValue|null,
 *     collaboration: AppInfoValue|null,
 *     commands: AppInfoValue|null,
 *     default_enable: AppInfoValue|null,
 *     dependencies: AppInfoValue|null,
 *     description: string,
 *     discussion: AppInfoValue|null,
 *     documentation: AppInfoValue|null,
 *     groups: AppInfoValue|null,
 *     id: string,
 *     info: AppInfoValue|null,
 *     internal: bool|null,
 *     level: int|null,
 *     licence: AppInfoValue|null,
 *     name: string,
 *     namespace: AppInfoValue|null,
 *     navigations: AppInfoValue|null,
 *     preview: AppInfoValue|null,
 *     previewAsIcon: bool|null,
 *     public: AppInfoValue|null,
 *     remote: AppInfoValue|null,
 *     removable: bool|null,
 *     repair-steps: AppInfoValue|null,
 *     repository: AppInfoValue|null,
 *     sabre: AppInfoValue|null,
 *     screenshot: AppInfoValue|null,
 *     settings: AppInfoValue|null,
 *     summary: string,
 *     trash: AppInfoValue|null,
 *     two-factor-providers: AppInfoValue|null,
 *     types: AppInfoValue|null,
 *     version: string,
 *     versions: AppInfoValue|null,
 *     website: AppInfoValue|null,
 * }
 *
 * @psalm-type GroupDetails = array{
 *     id: string,
 *     displayname: string,
 *     usercount: bool|int,
 *     disabled: bool|int,
 *     canAdd: bool,
 *     canRemove: bool,
 * }
 */
class ResponseDefinitions {
}
