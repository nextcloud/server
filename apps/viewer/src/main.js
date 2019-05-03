/**
 * @copyright Copyright (c) 2019 John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @author John Molakvoæ <skjnldsv@protonmail.com>
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
import Vue from 'vue'
import ViewerComponent from 'Views/Viewer'
import ViewerService from 'Services/Viewer'

import { generateFilePath } from 'nextcloud-server/dist/router'

Vue.prototype.$ = $

Vue.prototype.t = t
Vue.prototype.n = n

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

// Init vue
export default new Vue({
	el: '#viewer',
	name: 'ViewerRoot',
	render: h => h(ViewerComponent)
})
