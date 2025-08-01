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
 */
class ResponseDefinitions {
}
