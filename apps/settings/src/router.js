/**
 * @copyright Copyright (c) 2018 John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 * @author Julius Härtl <jus@bitgrid.net>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
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

import Vue from 'vue'
import Router from 'vue-router'
import { generateUrl } from '@nextcloud/router'
import { APPS_SECTION_ENUM } from './constants/AppsConstants.js'
import store from './store/index.js'
import { setPageHeading } from '../../../core/src/OCP/accessibility.js'

// Dynamic loading
const Users = () => import(/* webpackChunkName: 'settings-users' */'./views/Users.vue')
const Apps = () => import(/* webpackChunkName: 'settings-apps-view' */'./views/Apps.vue')

Vue.use(Router)

/*
 * This is the list of routes where the vuejs app will
 * take over php to provide data
 * You need to forward the php routing (routes.php) to
 * the settings-vue template, where the vue-router will
 * ensure the proper route.
 * ⚠️ Routes needs to match the php routes.
 */
const baseTitle = document.title
const router = new Router({
	mode: 'history',
	// if index.php is in the url AND we got this far, then it's working:
	// let's keep using index.php in the url
	base: generateUrl(''),
	linkActiveClass: 'active',
	routes: [
		{
			path: '/:index(index.php/)?settings/users',
			component: Users,
			props: true,
			name: 'users',
			meta: {
				title: () => {
					return t('settings', 'Active users')
				},
			},
			children: [
				{
					path: ':selectedGroup',
					name: 'group',
					meta: {
						title: (to) => {
							if (to.params.selectedGroup === 'admin') {
								return t('settings', 'Admins')
							}
							if (to.params.selectedGroup === 'disabled') {
								return t('settings', 'Disabled users')
							}
							return decodeURIComponent(to.params.selectedGroup)
						},
					},
					component: Users,
				},
			],
		},
		{
			path: '/:index(index.php/)?settings/apps',
			component: Apps,
			props: true,
			name: 'apps',
			meta: {
				title: () => {
					return t('settings', 'Your apps')
				},
			},
			children: [
				{
					path: ':category',
					name: 'apps-category',
					meta: {
						title: async (to) => {
							if (to.name === 'apps') {
								return t('settings', 'Your apps')
							}
							if (APPS_SECTION_ENUM[to.params.category]) {
								return APPS_SECTION_ENUM[to.params.category]
							}
							await store.dispatch('getCategories')
							const category = store.getters.getCategoryById(to.params.category)
							if (category.displayName) {
								return category.displayName
							}
						},
					},
					component: Apps,
					children: [
						{
							path: ':id',
							name: 'apps-details',
							component: Apps,
						},
					],
				},
			],
		},
	],
})

router.afterEach(async (to) => {
	const metaTitle = await to.meta.title?.(to)
	if (metaTitle) {
		document.title = `${metaTitle} - ${baseTitle}`
		setPageHeading(metaTitle)
	} else {
		document.title = baseTitle
	}
})

export default router
