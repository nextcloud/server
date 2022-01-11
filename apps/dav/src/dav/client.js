/*
 * @copyright 2021 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2021 Christoph Wurst <christoph@winzerhof-wurst.at>
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

import * as webdav from 'webdav'
import axios from '@nextcloud/axios'
import memoize from 'lodash/fp/memoize'
import { generateRemoteUrl } from '@nextcloud/router'
import { getCurrentUser } from '@nextcloud/auth'

export const getClient = memoize((service) => {
	// Add this so the server knows it is an request from the browser
	axios.defaults.headers['X-Requested-With'] = 'XMLHttpRequest'

	// force our axios
	const patcher = webdav.getPatcher()
	patcher.patch('request', axios)

	return webdav.createClient(
		generateRemoteUrl(`dav/${service}/${getCurrentUser().uid}`)
	)
})
