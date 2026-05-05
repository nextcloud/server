/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
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

/**
 * SVG paths used for appstore category icons
 */
export default Object.freeze({
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
