/**
 * @copyright Copyright (c) 2019 John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

import axios from 'axios'
import { parseString } from 'xml2js'

/**
 *
 * @param {String} user the current user
 * @param {String} path the path relative to the user root
 * @param {Array} mimes the list of mimes to search
 */
export default async function(user, path, mimes) {
	const response = await axios({
		method: 'PROPFIND',
		url: `/remote.php/dav/files/${user}${path}`,
		headers: {
			requesttoken: OC.requestToken,
			'content-Type': 'text/xml'
		}
	})

	let files = []
	await parseString(response.data, (error, data) => {
		files = data['d:multistatus']['d:response']
		if (error) {
			console.error(error)
		}
	})

	return files.filter(file => file['d:propstat'][0]['d:prop'][0]['d:getcontenttype'] && mimes.indexOf(file['d:propstat'][0]['d:prop'][0]['d:getcontenttype'][0]) !== -1)

}
