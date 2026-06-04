/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { generateUrl } from '@nextcloud/router'
import Vue from 'vue'
import Router from 'vue-router'
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

export default router
