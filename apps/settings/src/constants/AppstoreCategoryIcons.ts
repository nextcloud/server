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
	mdiCctv,
	mdiCheck,
	mdiCircleOutline,
	mdiClose,
	mdiCog,
	mdiController,
	mdiDownload,
	mdiFileDocument,
	mdiFolder,
	mdiFormatListBulletedSquare,
	mdiImage,
	mdiKey,
	mdiLock,
	mdiMagnify,
	mdiPowerPlugOutline,
	mdiStarShooting,
	mdiWrench,
} from '@mdi/js'

const workflow = 'm 4.2414469,4.1247025 c -1.3787723,0 -2.5218593,1.1430733 -2.5218593,2.5218593 V 9.6249409 H 1.3946197 c -1.85949304,-0.026298 -1.85949304,2.7763831 0,2.7501201 h 0.3249679 v 2.978378 c 0,1.378771 1.1430732,2.521859 2.5218593,2.521859 h 5.9567581 c 1.378773,0 2.52186,-1.143073 2.52186,-2.521859 v -2.978378 h 4.58706 l -2.452005,2.452005 c -1.3498,1.296008 0.648354,3.29423 1.944471,1.944473 l 4.799233,-4.799233 c 0.532148,-0.533249 0.537648,-1.406549 0,-1.944473 L 16.799591,5.2286004 C 16.540667,4.9625263 16.185214,4.8123561 15.813948,4.8123286 c -1.233978,0 -1.843129,1.5000524 -0.958788,2.3607022 l 2.452007,2.4520063 H 12.720105 V 6.646658 c 0,-1.3787722 -1.143072,-2.5218592 -2.521859,-2.5218592 H 4.2414881 Z m 0,2.0625894 h 5.9567581 c 0.271767,0 0.459242,0.1874756 0.459242,0.4592424 v 8.7068767 c 0,0.271767 -0.187475,0.459242 -0.459242,0.459242 H 4.2414469 c -0.2717668,0 -0.4592425,-0.187475 -0.4592425,-0.459242 V 6.6465343 c 0,-0.2717668 0.1874757,-0.4592424 0.4592425,-0.4592424 z'

/**
 * SVG paths used for appstore category icons
 */
export default Object.freeze({
	auth: mdiKey,
	bundles: mdiArchive,
	customization: mdiWrench,
	dashboard: mdiCircleOutline,
	disabled: mdiClose,
	enabled: mdiCheck,
	files: mdiFolder,
	games: mdiController,
	installed: mdiAccount,
	integration: mdiPowerPlugOutline,
	monitoring: mdiCctv,
	multimedia: mdiImage,
	office: mdiFileDocument,
	organization: mdiFormatListBulletedSquare,
	search: mdiMagnify,
	security: mdiLock,
	social: mdiAccountMultiple,
	supported: mdiStarShooting,
	tools: mdiCog,
	updates: mdiDownload,
	workflow,
})
