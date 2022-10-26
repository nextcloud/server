/**
 * @copyright Copyright (c) 2019 John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @author John Molakvoæ <skjnldsv@protonmail.com>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
import Vue from 'vue'
import ViewerComponent from './views/Viewer.vue'
import ViewerService from './services/Viewer.js'
import { translate as t } from '@nextcloud/l10n'

import { generateFilePath } from '@nextcloud/router'

Vue.mixin({
	methods: {
		t,
	},
})

// Inject proper font for cypress visual regression testing
if (isTesting) {
	// Import font so CI has the same
	import(/* webpackChunkName: 'roboto-font' */'@fontsource/roboto')
}

Vue.prototype.OC = OC
Vue.prototype.OCA = OCA

// CSP config for webpack dynamic chunk loading
// eslint-disable-next-line
__webpack_nonce__ = btoa(OC.requestToken)

// Correct the root of the app for chunk loading
// OC.linkTo matches the apps folders
// OC.generateUrl ensure the index.php (or not)
// We do not want the index.php since we're loading files
// eslint-disable-next-line
__webpack_public_path__ = generateFilePath('viewer', '', 'js/')

// Init Viewer Service
if (window.OCA) {
	Object.assign(window.OCA, { Viewer: new ViewerService() })
	OCA.Viewer.version = appVersion
}

// Create document root
const ViewerRoot = document.createElement('div')
ViewerRoot.id = 'viewer'
document.body.appendChild(ViewerRoot)

// Put controls for video viewer
// Needed as Firefox CSP blocks the loading of the svg through the normal plyr system
const VideoControls = document.createElement('div')
VideoControls.innerHTML = PLYR_ICONS
VideoControls.style.display = 'none'
document.body.appendChild(VideoControls)

// Init vue
export default new Vue({
	el: '#viewer',
	// When debugging the page, it's easier to find which app
	// is which. Especially when there is multiple apps
	// roots mounted o the same page!
	// eslint-disable-next-line vue/match-component-file-name
	name: 'ViewerRoot',
	render: h => h(ViewerComponent),
})
