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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
describe('check if dav app supports CORS', function() {
	describe('OPTIONS requests', () => {
		it('set the access control header', function() {
			const url = new URL(Cypress.config('baseUrl') as string)
			url.pathname = 'remote.php/dav/files/admin/'

			cy.request({
				method: 'OPTIONS',
				url: url.toString(),
				headers: {
					Origin: 'http://example.com',
					'Access-Control-Allow-Methods': 'http://example.com',
				},
			}).then(response => {
				expect(response.status).to.equal(200)
				expect(response).to.have.property('headers')
				expect(response.headers).to.have.property('access-control-allow-origin')
				expect(response.headers['access-control-allow-origin']).to.equal('*')
			})
		})

		it('does not set header if Origin is invalid', function() {
			const url = new URL(Cypress.config('baseUrl') as string)
			url.pathname = 'remote.php/dav/files/admin/'

			cy.request({
				method: 'OPTIONS',
				url: url.toString(),
				failOnStatusCode: false,
				headers: {
					Origin: 'example.com',
					'Access-Control-Allow-Methods': 'http://example.com',
				},
			}).then(response => {
				expect(response.headers).to.not.have.property('access-control-allow-origin')
			})
		})
	})

	describe('WebDAV requests', () => {
		before(() => {
			cy.runOccCommand('config:system:set --value "http://good.example.com" cors.allowed-domains 0')
		})
		after(() => {
			cy.runOccCommand('config:system:delete cors.allowed-domains')
		})
		it('do not set CORS headers when origin is not allowed', function() {
			const url = new URL(Cypress.config('baseUrl') as string)
			url.pathname = 'remote.php/dav/files/admin/'

			cy.request({
				method: 'PROPFIND',
				url: url.toString(),
				failOnStatusCode: false,
				headers: {
					Origin: 'http://example.com',
					Authorization: `Basic ${btoa('admin:admin')}`,
				},
				body: `<?xml version="1.0"?>
<d:propfind xmlns:d="DAV:" xmlns:nc="http://nextcloud.org/ns" xmlns:oc="http://owncloud.org/ns" xmlns:ocs="http://open-collaboration-services.org/ns">
	<d:prop>
		<d:getcontentlength /> <d:getcontenttype /> <d:getetag /> <d:getlastmodified /> <d:quota-available-bytes /> <d:resourcetype /> <nc:has-preview /> <nc:is-encrypted /> <nc:mount-type /> <nc:share-attributes /> <oc:comments-unread /> <oc:favorite /> <oc:fileid /> <oc:owner-display-name /> <oc:owner-id /> <oc:permissions /> <oc:share-types /> <oc:size /> <ocs:share-permissions /> <nc:system-tags />
	</d:prop>
</d:propfind>`,
			}).then(response => {
				expect(response.status).to.equal(207)
				expect(response.headers).to.not.have.property('access-control-allow-origin')
			})
		})

		it('set CORS headers when origin is allowed', function() {
			const url = new URL(Cypress.config('baseUrl') as string)
			url.pathname = 'remote.php/dav/files/admin/'

			cy.request({
				method: 'PROPFIND',
				url: url.toString(),
				failOnStatusCode: false,
				headers: {
					Origin: 'http://good.example.com',
					Authorization: `Basic ${btoa('admin:admin')}`,
				},
				body: `<?xml version="1.0"?>
<d:propfind xmlns:d="DAV:" xmlns:nc="http://nextcloud.org/ns" xmlns:oc="http://owncloud.org/ns" xmlns:ocs="http://open-collaboration-services.org/ns">
	<d:prop>
		<d:getcontentlength /> <d:getcontenttype /> <d:getetag /> <d:getlastmodified /> <d:quota-available-bytes /> <d:resourcetype /> <nc:has-preview /> <nc:is-encrypted /> <nc:mount-type /> <nc:share-attributes /> <oc:comments-unread /> <oc:favorite /> <oc:fileid /> <oc:owner-display-name /> <oc:owner-id /> <oc:permissions /> <oc:share-types /> <oc:size /> <ocs:share-permissions /> <nc:system-tags />
	</d:prop>
</d:propfind>`,
			}).then(response => {
				expect(response.status).to.equal(207)
				expect(response.headers).to.have.property('access-control-allow-origin')
				expect(response.headers['access-control-allow-origin']).to.equal('http://good.example.com')
			})
		})
	})
})
