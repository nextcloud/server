/**
 * @copyright Copyright (c) 2019 Gary Kim <gary@garykim.dev>
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
import { generateUrl } from '@nextcloud/router'
import logger from '../logger.js'

export default () => {
	if ('serviceWorker' in navigator) {
		// Use the window load event to keep the page load performant
		window.addEventListener('load', async () => {
			try {
				const url = generateUrl('/apps/files/preview-service-worker.js', {}, { noRewrite: true })
				const registration = await navigator.serviceWorker.register(url, { scope: '/' })
				logger.debug('SW registered: ', { registration })
			} catch (error) {
				logger.error('SW registration failed: ', { error })
			}
		})
	} else {
		logger.debug('Service Worker is not enabled on this browser.')
	}
}
