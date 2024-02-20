/**
 * @copyright Copyright (c) 2024 Ferdinand Thiessen <opensource@fthiessen.de>
 *
 * @author Ferdinand Thiessen <opensource@fthiessen.de>
 *
 * @license AGPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
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
	mdiStarShooting,
	mdiTools,
	mdiViewDashboard,
} from '@mdi/js'

/**
 * SVG paths used for appstore category icons
 */
export default Object.freeze({
	// system special categories
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
	dashboard: mdiViewDashboard,
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
