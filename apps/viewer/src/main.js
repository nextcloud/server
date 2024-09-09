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
import { translate as t } from '@nextcloud/l10n'
import Vue from 'vue'

import ViewerComponent from './views/Viewer.vue'

Vue.mixin({
	methods: {
		t,
	},
})

Vue.prototype.OC = window.OC
Vue.prototype.OCA = window.OCA

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
