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

namespace OCA\Files_External;

/**
 * @psalm-type Files_ExternalStorageConfig = array{
 *     applicableGroups?: string[],
 *     applicableUsers?: string[],
 *     authMechanism: string,
 *     backend: string,
 *     backendOptions: array<string, mixed>,
 *     id?: int,
 *     mountOptions?: array<string, mixed>,
 *     mountPoint: string,
 *     priority?: int,
 *     status?: int,
 *     statusMessage?: string,
 *     type: 'personal'|'system',
 *     userProvided: bool,
 * }
 *
 * @psalm-type Files_ExternalMount = array{
 *     name: string,
 *     path: string,
 *     type: 'dir',
 *     backend: string,
 *     scope: 'system'|'personal',
 *     permissions: int,
 *     id: int,
 *     class: string,
 *     config: Files_ExternalStorageConfig,
 * }
 */
class ResponseDefinitions {
}
