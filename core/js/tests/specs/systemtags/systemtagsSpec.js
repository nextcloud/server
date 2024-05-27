/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
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
