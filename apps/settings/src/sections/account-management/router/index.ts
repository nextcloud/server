/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { generateUrl } from '@nextcloud/router'
import { createRouter, createWebHistory } from 'vue-router'
import routes from './routes.ts'

const router = createRouter({
	// if index.php is in the url AND we got this far, then it's working:
	// let's keep using index.php in the url
	history: createWebHistory(generateUrl('')),
	linkActiveClass: 'active',
	routes,
})

export default router
