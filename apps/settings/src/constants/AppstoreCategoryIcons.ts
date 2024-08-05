/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import {
	mdiAccount,
	mdiAccountMultiple,
	mdiArchive,
	mdiCheck,
	mdiClipboardFlow,
	mdiClose,
	mdiCog,
	mdiControllerClassic,
	mdiDownload,
	mdiFileDocumentEdit,
	mdiFolder,
	mdiKey,
	mdiMagnify,
	mdiMonitorEye,
	mdiMultimedia,
	mdiOfficeBuilding,
	mdiOpenInApp,
	mdiSecurity,
	mdiStar,
	mdiStarCircleOutline,
	mdiStarShooting,
	mdiTools,
	mdiViewColumn,
} from '@mdi/js'

/**
 * SVG paths used for appstore category icons
 */
export default Object.freeze({
	// system special categories
	discover: mdiStarCircleOutline,
	installed: mdiAccount,
	enabled: mdiCheck,
	disabled: mdiClose,
	bundles: mdiArchive,
	supported: mdiStarShooting,
	featured: mdiStar,
	updates: mdiDownload,

	// generic categories
	auth: mdiKey,
	customization: mdiCog,
	dashboard: mdiViewColumn,
	files: mdiFolder,
	games: mdiControllerClassic,
	integration: mdiOpenInApp,
	monitoring: mdiMonitorEye,
	multimedia: mdiMultimedia,
	office: mdiFileDocumentEdit,
	organization: mdiOfficeBuilding,
	search: mdiMagnify,
	security: mdiSecurity,
	social: mdiAccountMultiple,
	tools: mdiTools,
	workflow: mdiClipboardFlow,
})
