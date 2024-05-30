<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2024 Kate Döen <kate.doeen@nextcloud.com>
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
