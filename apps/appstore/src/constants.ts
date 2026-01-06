/**
 * SPDX-FileCopyrightText: Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import {
	mdiAccountMultipleOutline,
	mdiAccountOutline,
	mdiArchiveOutline,
	mdiCheck,
	mdiClipboardFlowOutline,
	mdiClose,
	mdiCogOutline,
	mdiControllerClassicOutline,
	mdiCreationOutline,
	mdiDownload,
	mdiFileDocumentEdit,
	mdiFolder,
	mdiKeyOutline,
	mdiMagnify,
	mdiMonitorEye,
	mdiMultimedia,
	mdiOfficeBuildingOutline,
	mdiOpenInApp,
	mdiSecurity,
	mdiStar,
	mdiStarCircleOutline,
	mdiStarShootingOutline,
	mdiTools,
	mdiViewColumnOutline,
} from '@mdi/js'
import { t } from '@nextcloud/l10n'

/**
 * The names of the special appstore sections
 */
export const APPSTORE_CATEGORY_NAMES = Object.freeze({
	discover: t('settings', 'Discover'),
	installed: t('settings', 'Your apps'),
	enabled: t('settings', 'Active apps'),
	disabled: t('settings', 'Disabled apps'),
	updates: t('settings', 'Updates'),
	'app-bundles': t('settings', 'App bundles'),
	featured: t('settings', 'Featured apps'),
	supported: t('settings', 'Supported apps'), // From subscription
})

/**
 * SVG paths used for appstore category icons
 */
export const APPSTORE_CATEGORY_ICONS = Object.freeze({
	// system special categories
	discover: mdiStarCircleOutline,
	installed: mdiAccountOutline,
	enabled: mdiCheck,
	disabled: mdiClose,
	bundles: mdiArchiveOutline,
	supported: mdiStarShootingOutline,
	featured: mdiStar,
	updates: mdiDownload,

	// generic category
	ai: mdiCreationOutline,
	auth: mdiKeyOutline,
	customization: mdiCogOutline,
	dashboard: mdiViewColumnOutline,
	files: mdiFolder,
	games: mdiControllerClassicOutline,
	integration: mdiOpenInApp,
	monitoring: mdiMonitorEye,
	multimedia: mdiMultimedia,
	office: mdiFileDocumentEdit,
	organization: mdiOfficeBuildingOutline,
	search: mdiMagnify,
	security: mdiSecurity,
	social: mdiAccountMultipleOutline,
	tools: mdiTools,
	workflow: mdiClipboardFlowOutline,
})

/**
 * Currently known types of app discover section elements
 */
export const APP_DISCOVER_KNOWN_TYPES = ['post', 'showcase', 'carousel'] as const
