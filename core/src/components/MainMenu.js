/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { translate as t, translatePlural as n } from '@nextcloud/l10n'
import Vue from 'vue'

import AppMenu from './AppMenu.vue'

export const setUp = () => {

	Vue.mixin({
		methods: {
			t,
			n,
		},
	})

	const container = document.getElementById('header-start__appmenu')
	if (!container) {
		// no container, possibly we're on a public page
		return
	}
	const AppMenuApp = Vue.extend(AppMenu)
	const appMenu = new AppMenuApp({}).$mount(container)

	Object.assign(OC, {
		setNavigationCounter(id, counter) {
			appMenu.setNavigationCounter(id, counter)
		},
	})

}
