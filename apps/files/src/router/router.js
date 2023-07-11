/**
 * @copyright Copyright (c) 2022 John Molakvoæ <skjnldsv@protonmail.com>
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
import queryString from 'query-string'

Vue.use(Router)

const router = new Router({
	mode: 'history',

	// if index.php is in the url AND we got this far, then it's working:
	// let's keep using index.php in the url
	base: generateUrl('/apps/files', ''),
	linkActiveClass: 'active',

	routes: [
		{
			path: '/',
			// Pretending we're using the default view
			alias: '/files',
		},
		{
			path: '/:view/:fileid?',
			name: 'filelist',
			props: true,
		},
	],

	// Custom stringifyQuery to prevent encoding of slashes in the url
	stringifyQuery(query) {
		const result = queryString.stringify(query).replace(/%2F/gmi, '/')
		return result ? ('?' + result) : ''
	},
})

router.beforeEach((to, from, next) => {
	// TODO: Remove this when the legacy files list is removed
	try {
		const views = window.OCP.Files?.Navigation?.views || []
		const isLegacy = views.find(view => view?.id === to?.params?.view)?.legacy === true
		if (isLegacy && to?.query?.dir !== from?.query?.dir) {
			// https://github.com/nextcloud/server/blob/1b422df12ac8eb26514849fb117e0dcf58623b88/apps/files/js/filelist.js#L2052-L2076
			window.OCA.Files.App.fileList.changeDirectory(to?.query?.dir || '/', false, false, to?.query?.fileid, true)
		}
	} catch (error) {}
	next()
})

export default router
