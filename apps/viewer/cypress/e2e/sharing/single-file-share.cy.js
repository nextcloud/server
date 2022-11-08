/**
 * @copyright Copyright (c) 2022 Max <max@nextcloud.com>
 *
 * @author Max <max@nextcloud.com>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

import { randHash } from '../../utils'
const randUser = randHash()

describe('See shared folder with link share', function() {
	let imageToken
	let videoToken

	before(function() {
		cy.nextcloudCreateUser(randUser)

		cy.uploadFile(randUser, 'image1.jpg', 'image/jpeg')
		cy.uploadFile(randUser, 'video1.mp4', 'video/mp4')

		// Visit nextcloud
		cy.login(randUser)
		cy.visit('/apps/files')

		cy.createLinkShare('/image1.jpg').then(token => { imageToken = token })
		cy.createLinkShare('/video1.mp4').then(token => { videoToken = token })

		cy.logout()
	})

	it('Opens the shared image in the viewer', function() {
		cy.visit(`/s/${imageToken}`)

		cy.get('#imgframe img').should('be.visible')
		cy.get('#imgframe > #viewer').should('be.visible')

		cy.scrollTo('bottom', { ensureScrollable: false })
		cy.get('a#downloadFile').should('be.visible')
	})

	it('Opens the shared video in the viewer', function() {
		cy.visit(`/s/${videoToken}`)

		cy.get('#imgframe .plyr').should('be.visible')
		cy.get('#imgframe > #viewer').should('be.visible')

		cy.scrollTo('bottom', { ensureScrollable: false })
		cy.get('a#downloadFile').should('be.visible')
	})
})
