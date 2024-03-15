/**
 * @copyright 2022 Louis Chemineau <mlouis@chmn.me>
 *
 * @author Louis Chemineau <mlouis@chmn.me>
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
 */

import { createClient } from 'webdav'
import { generateRemoteUrl } from '@nextcloud/router'
import { getRequestToken, onRequestTokenUpdate } from '@nextcloud/auth'

// init webdav client
const rootPath = 'dav'
const remote = generateRemoteUrl(rootPath)
const client = createClient(remote)

// set CSRF token header
const setHeaders = (token) => {
  client.setHeaders({
    // Add this so the server knows it is an request from the browser
    'X-Requested-With': 'XMLHttpRequest',
    // Inject user auth
    requesttoken: token ?? '',
  })
}

// refresh headers when request token changes
onRequestTokenUpdate(setHeaders)
setHeaders(getRequestToken())

export default client