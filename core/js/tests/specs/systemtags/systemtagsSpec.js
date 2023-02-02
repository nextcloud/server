/**
* ownCloud
*
* @author Joas Schilling
* @copyright 2016 Joas Schilling <nickvergessen@owncloud.com>
*
* This library is free software; you can redistribute it and/or
* modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
* License as published by the Free Software Foundation; either
* version 3 of the License, or any later version.
*
* This library is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU AFFERO GENERAL PUBLIC LICENSE for more details.
*
* You should have received a copy of the GNU Affero General Public
* License along with this library.  If not, see <http://www.gnu.org/licenses/>.
*
*/

describe('OC.SystemTags tests', function() {
	it('describes non existing tag', function() {
		var $return = OC.SystemTags.getDescriptiveTag('23');
		expect($return.textContent).toEqual('Non-existing tag #23');
		expect($return.classList.contains('non-existing-tag')).toEqual(true);
	});

	it('describes SystemTagModel', function() {
		var tag = new OC.SystemTags.SystemTagModel({
			id: 23,
			name: 'Twenty Three',
			userAssignable: true,
			userVisible: true
		});
		var $return = OC.SystemTags.getDescriptiveTag(tag);
		expect($return.textContent).toEqual('Twenty Three');
		expect($return.classList.contains('non-existing-tag')).toEqual(false);
	});

	it('describes JSON tag object', function() {
		var $return = OC.SystemTags.getDescriptiveTag({
			id: 42,
			name: 'Fourty Two',
			userAssignable: true,
			userVisible: true
		});
		expect($return.textContent).toEqual('Fourty Two');
		expect($return.classList.contains('non-existing-tag')).toEqual(false);
	});

	it('scope', function() {
		function testScope(userVisible, userAssignable, expectedText) {
			var $return = OC.SystemTags.getDescriptiveTag({
				id: 42,
				name: 'Fourty Two',
				userAssignable: userAssignable,
				userVisible: userVisible
			});
			expect($return.textContent).toEqual(expectedText);
			expect($return.classList.contains('non-existing-tag')).toEqual(false);
		}

		testScope(true, true, 'Fourty Two');
		testScope(false, true, 'Fourty Two (Invisible)');
		testScope(false, false, 'Fourty Two (Invisible)');
		testScope(true, false, 'Fourty Two (Restricted)');
	});
});
