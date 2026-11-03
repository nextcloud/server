<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Settings;

/**
 * @psalm-type SettingsDeclarativeFormField = array{
 *   id: string,
 *   title: string,
 *   description?: string,
 *   type: 'text'|'password'|'email'|'tel'|'url'|'number'|'checkbox'|'multi-checkbox'|'radio'|'select'|'multi-select',
 *   placeholder?: string,
 *   label?: string,
 *   default: mixed,
 *   options?: list<string|array{name: string, value: mixed}>,
 *   value: string|int|float|bool|list<string>,
 *   sensitive?: boolean,
 * }
 *
 * @psalm-type SettingsDeclarativeForm = array{
 *   id: string,
 *   priority: int,
 *   section_type: 'admin'|'personal',
 *   section_id: string,
 *   storage_type: 'internal'|'external',
 *   title: string,
 *   description?: string,
 *   doc_url?: string,
 *   app: string,
 *   fields: list<SettingsDeclarativeFormField>,
 * }
 *
 * @psalm-type SettingsPreviewProvider = array{
 *   class: string,
 *   name: string,
 *   mimetypes: string,
 *   requirement: 'none'|'imagick'|'office'|'ffmpeg'|'imaginary',
 *   available: bool,
 *   enabled: bool,
 * }
 *
 * @psalm-type SettingsPreviewSettings = array{
 *   configIsReadOnly: bool,
 *   enabled: bool,
 *   maxX: ?int,
 *   maxY: ?int,
 *   maxMemory: ?int,
 *   maxFilesizeImage: ?int,
 *   jpegQuality: ?int,
 *   webpQuality: ?int,
 *   concurrencyNew: ?int,
 *   concurrencyAll: ?int,
 *   expirationDays: ?int,
 *   providersConfigured: bool,
 *   providers: list<SettingsPreviewProvider>,
 *   dependencies: array{
 *     imagick: bool,
 *     ffmpeg: ?string,
 *     office: ?string,
 *     imaginary: bool,
 *   },
 * }
 */
class ResponseDefinitions {
}
