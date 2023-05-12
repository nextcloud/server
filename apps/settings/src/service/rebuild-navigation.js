/**
 * @copyright Copyright (c) 2016 Carl Schwan <carl@carlschwan.eu>
 *
 * @author Carl Schwan <carl@carlschwan.eu>
 * @author Joas Schilling <coding@schilljs.com>
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

import axios from '@nextcloud/axios'
import { generateOcsUrl } from '@nextcloud/router'
import { emit } from '@nextcloud/event-bus'
export default () => {
	return axios.get(generateOcsUrl('core/navigation', 2) + '/apps?format=json')
		.then(({ data }) => {
			if (data.ocs.meta.statuscode !== 200) {
				return
			}

			emit('nextcloud:app-menu.refresh', { apps: data.ocs.data })
			window.dispatchEvent(new Event('resize'))
		})
}
