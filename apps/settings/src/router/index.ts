/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import Vue from 'vue'
import Router from 'vue-router'
import { generateUrl } from '@nextcloud/router'
import routes from './routes.ts'

Vue.use(Router)

const router = new Router({
	mode: 'history',
	// if index.php is in the url AND we got this far, then it's working:
	// let's keep using index.php in the url
	base: generateUrl(''),
	linkActiveClass: 'active',
	routes,
})

const ALL_VIEWS = [
	'all',
	'disabled',
	'group',
	'recent',
]

router.beforeEach((to, from, next) => {
	// make sure old URLs without the `/group/` part keep working
	if (to.name === 'users-view' && !ALL_VIEWS.includes(to.params.view)) {
		return next({ name: 'group', params: { selectedGroup: to.params.view } })
	}
	// if there is no group selected redirect to all accounts
	if (to.name === 'users-view' && to.params.view === 'group' && !to.params.selectedGroup) {
		return next({ name: 'users-view', params: { view: 'all' } })
	}
	next()
})

export default router
