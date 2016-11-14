/**
 * @copyright 2016, Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @author Roeland Jago Douma <roeland@famdouma.nl>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

describe('OCA.BruteForceSettings.IPWhiteList tests', function() {
	beforeEach(function() {
		// init parameters and test table elements
		$('#testArea').append(
			'<table>'+
			'<tbody id="whitelist-list">' +
			'</tbody>' +
			'</table>' +
			'<input type="text" name="whitelist_ip" id="whitelist_ip" placeholder="1.2.3.4" style="width: 200px;" />/' +
			'<input type="number" id="whitelist_mask" name="whitelist_mask" placeholder="24" style="width: 50px;">' +
			'<input type="button" id="whitelist_submit" value="Add">'
		);
	});

	it('get intial empty', function() {
		OCA.BruteForceSettings.WhiteList.init();

		expect(fakeServer.requests.length).toEqual(1);
		expect(fakeServer.requests[0].method).toEqual('GET');
		expect(fakeServer.requests[0].url).toEqual(
			OC.generateUrl('/apps/bruteforcesettings/ipwhitelist')
		);
		fakeServer.requests[0].respond(
			200,
			{ 'Content-Type': 'application/json' },
			'[]'
		);

		expect($('#whitelist-list > tr').length).toEqual(0);
	});
	it('get intial filled', function() {
		OCA.BruteForceSettings.WhiteList.init();

		expect(fakeServer.requests.length).toEqual(1);
		expect(fakeServer.requests[0].method).toEqual('GET');
		expect(fakeServer.requests[0].url).toEqual(
			OC.generateUrl('/apps/bruteforcesettings/ipwhitelist')
		);
		fakeServer.requests[0].respond(
			200,
			{ 'Content-Type': 'application/json' },
			JSON.stringify([
				{
					id: 1,
					ip: '11.22.0.0',
					mask: 16
				},
				{
					id: 12,
					ip: 'cafe:cafe::',
					mask: 80
				}
			])
		);

		expect($('#whitelist-list > tr').length).toEqual(2);

		var el1 = $($('#whitelist-list > tr').get(0));
		expect(el1.data('id')).toEqual(1);
		expect($(el1.find('td > span')[0]).html()).toEqual('11.22.0.0/16');

		var el2 = $($('#whitelist-list > tr').get(1));
		expect(el2.data('id')).toEqual(12);
		expect($(el2.find('td > span')[0]).html()).toEqual('cafe:cafe::/80');
	});
	it('add whitelist', function() {
		OCA.BruteForceSettings.WhiteList.init();

		expect(fakeServer.requests.length).toEqual(1);
		expect(fakeServer.requests[0].method).toEqual('GET');
		expect(fakeServer.requests[0].url).toEqual(
			OC.generateUrl('/apps/bruteforcesettings/ipwhitelist')
		);
		fakeServer.requests[0].respond(
			200,
			{ 'Content-Type': 'application/json' },
			'[]'
		);

		expect($('#whitelist-list > tr').length).toEqual(0);

		$('#whitelist_ip').val('2.4.8.16');
		$('#whitelist_mask').val('8');
		$('#whitelist_submit').click();

		expect(fakeServer.requests.length).toEqual(2);
		expect(fakeServer.requests[1].method).toEqual('POST');
		expect(JSON.parse(fakeServer.requests[1].requestBody)).toEqual({
			ip: '2.4.8.16',
			mask: '8'
		});
		expect(fakeServer.requests[1].url).toEqual(
			OC.generateUrl('/apps/bruteforcesettings/ipwhitelist')
		);
		fakeServer.requests[1].respond(
			200,
			{ 'Content-Type': 'application/json' },
			JSON.stringify({
				id: 99,
				ip: '2.4.8.16',
				mask: 8
			})
		);

		expect($('#whitelist-list > tr').length).toEqual(1);

		var el1 = $($('#whitelist-list > tr').get(0));
		expect(el1.data('id')).toEqual(99);
		expect($(el1.find('td > span')[0]).html()).toEqual('2.4.8.16/8');
	});
	it('delete whitelist', function() {
		OCA.BruteForceSettings.WhiteList.init();

		expect(fakeServer.requests.length).toEqual(1);
		expect(fakeServer.requests[0].method).toEqual('GET');
		expect(fakeServer.requests[0].url).toEqual(
			OC.generateUrl('/apps/bruteforcesettings/ipwhitelist')
		);
		fakeServer.requests[0].respond(
			200,
			{ 'Content-Type': 'application/json' },
			JSON.stringify([
				{
					id: 1,
					ip: '1.2.3.4',
					mask: 8
				}
			])
		);

		expect($('#whitelist-list > tr').length).toEqual(1);

		var el1 = $($('#whitelist-list > tr').get(0));
		expect(el1.data('id')).toEqual(1);
		expect($(el1.find('td > span')[0]).html()).toEqual('1.2.3.4/8');
		el1.find('.icon-delete').click();

		expect(fakeServer.requests.length).toEqual(2);
		expect(fakeServer.requests[1].method).toEqual('DELETE');
		expect(fakeServer.requests[1].url).toEqual(
			OC.generateUrl('/apps/bruteforcesettings/ipwhitelist/1')
		);

		fakeServer.requests[1].respond(
			200,
			{ 'Content-Type': 'application/json' },
			'[]'
		);

		expect($('#whitelist-list > tr').length).toEqual(0);
	});
});
