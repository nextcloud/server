/**
 * @copyright Copyright (c) 2021 Arthur Schiwon <blizzz@nextcloud.com>
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
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

import fileDownload from 'js-file-download'
import { generateUrl } from '@nextcloud/router'
import axios from '@nextcloud/axios'

export default class Download {

	export
	const
	async get(files, dir, downloadStartSecret) {
		await axios.post(
			generateUrl('apps/files/ajax/download.php'),
			{
				files,
				dir,
				downloadStartSecret,
			},
			{
				responseType: 'blob',
			}
		).then(res => {
			const fileNameMatch = res.headers['content-disposition'].match(/filename="(.+)"/)
			let fileName = ''
			if (fileNameMatch.length === 2) {
				fileName = fileNameMatch[1]
			}
			fileDownload(res.data, fileName)
		})
	}

}
