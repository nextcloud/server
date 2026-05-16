/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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
