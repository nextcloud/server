/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { translate as t } from '@nextcloud/l10n'

/** Enum of verification constants, according to Apps */
export const APPS_SECTION_ENUM = Object.freeze({
	discover: t('settings', 'Discover'),
	installed: t('settings', 'Your apps'),
	enabled: t('settings', 'Active apps'),
	disabled: t('settings', 'Disabled apps'),
	updates: t('settings', 'Updates'),
	'app-bundles': t('settings', 'App bundles'),
	featured: t('settings', 'Featured apps'),
	supported: t('settings', 'Supported apps'), // From subscription
})
