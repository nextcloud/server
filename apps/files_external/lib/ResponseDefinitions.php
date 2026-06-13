<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files_External;

/**
 * @psalm-type Files_ExternalStorageConfig = array{
 *     applicableGroups?: list<string>,
 *     applicableUsers?: list<string>,
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
