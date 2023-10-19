/**
 * @copyright Copyright (c) 2023 Ferdinand Thiessen <opensource@fthiessen.de>
 *
 * @author Ferdinand Thiessen <opensource@fthiessen.de>
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
/* eslint-disable no-unused-expressions */

import { User } from '@nextcloud/cypress'

describe('OCS APIv2', () => {
	let user: User

	before(() => {
		cy.createRandomUser().then((newUser) => {
			user = newUser
		})
	})

	it('Default output is xml', () => {
		// When sending "GET" to "/cloud/capabilities"
		cy.ocsRequest('cloud/capabilities', { user, version: 2 })
			.then((response) => {
				// And the HTTP status code should be "200"
				expect(response.status).to.eq(200)
				// And the content-type should be "text/xml; charset=utf-8"
				expect(response.headers).to.have.property('content-type', 'application/xml; charset=utf-8')
			})
	})

	it('Get XML when requesting XML', () => {
		// When sending "GET" to "/cloud/capabilities?format=xml"
		cy.ocsRequest('cloud/capabilities?format=xml', { user, version: 2 })
			.then((response) => {
				// And the HTTP status code should be "200"
				expect(response.status).to.eq(200)
				// And the content-Type should be "text/xml; charset=utf-8"
				expect(response.headers).to.have.property('content-type', 'application/xml; charset=utf-8')
			})
	})

	it('Get JSON when requesting JSON', () => {
		// When sending "GET" to "/cloud/capabilities?format=json"
		cy.ocsRequest('cloud/capabilities?format=json', { user, version: 2 })
			.then((response) => {
				// And the HTTP status code should be "200"
				expect(response.status).to.eq(200)
				// And the content-Type should be "application/json; charset=utf-8"
				expect(response.headers).to.have.property('content-type', 'application/json; charset=utf-8')
			})
	})

	it('Get OCS status "ok" with valid endpoint', () => {
		cy.ocsRequest('cloud/capabilities?format=json', { user, version: 2 })
			.then((response) => {
				// And the HTTP status code should be "200"
				expect(response.status).to.eq(200)
				expect(response.headers).to.have.property('content-type', 'application/json; charset=utf-8')
				// eslint-disable-next-line @typescript-eslint/no-explicit-any
				expect((response.body as any)?.ocs?.meta?.status).to.equal('ok') // Would be "failure" or undefined otherwise
			})
	})

	it('Get 404 on unknown route', () => {
		cy.ocsRequest('cloud/press-big-red-button?format=json', { user, version: 2, failOnStatusCode: false })
			.then((response) => {
				expect(response.status).to.eq(404)
				expect(response.headers).to.have.property('content-type', 'application/json; charset=utf-8')
				// eslint-disable-next-line @typescript-eslint/no-explicit-any
				expect((response.body as any)?.ocs?.meta?.status).to.equal('failure') // Would be "failure" or undefined otherwise
			})
	})
})
