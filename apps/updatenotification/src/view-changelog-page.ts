/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import Vue from 'vue'
import App from './views/App.vue'

export default new Vue({
	name: 'ViewChangelogPage',
	render: (h) => h(App),
	el: '#content',
})
