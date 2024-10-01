/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

describe('OCP.Comments tests', function() {
	function dataProvider() {
		return [
			{input: 'nextcloud.com', expected: 'nextcloud.com'},
			{input: 'http://nextcloud.com', expected: '<a class="external" target="_blank" rel="noopener noreferrer" href="http://nextcloud.com">http://nextcloud.com</a>'},
			{input: 'https://nextcloud.com', expected: '<a class="external" target="_blank" rel="noopener noreferrer" href="https://nextcloud.com">nextcloud.com</a>'},
			{input: 'hi nextcloud.com', expected: 'hi nextcloud.com'},
			{input: 'hi http://nextcloud.com', expected: 'hi <a class="external" target="_blank" rel="noopener noreferrer" href="http://nextcloud.com">http://nextcloud.com</a>'},
			{input: 'hi https://nextcloud.com', expected: 'hi <a class="external" target="_blank" rel="noopener noreferrer" href="https://nextcloud.com">nextcloud.com</a>'},
			{input: 'nextcloud.com foobar', expected: 'nextcloud.com foobar'},
			{input: 'http://nextcloud.com foobar', expected: '<a class="external" target="_blank" rel="noopener noreferrer" href="http://nextcloud.com">http://nextcloud.com</a> foobar'},
			{input: 'https://nextcloud.com foobar', expected: '<a class="external" target="_blank" rel="noopener noreferrer" href="https://nextcloud.com">nextcloud.com</a> foobar'},
			{input: 'hi nextcloud.com foobar', expected: 'hi nextcloud.com foobar'},
			{input: 'hi http://nextcloud.com foobar', expected: 'hi <a class="external" target="_blank" rel="noopener noreferrer" href="http://nextcloud.com">http://nextcloud.com</a> foobar'},
			{input: 'hi https://nextcloud.com foobar', expected: 'hi <a class="external" target="_blank" rel="noopener noreferrer" href="https://nextcloud.com">nextcloud.com</a> foobar'},
			{input: 'hi help.nextcloud.com/category/topic foobar', expected: 'hi help.nextcloud.com/category/topic foobar'},
			{input: 'hi http://help.nextcloud.com/category/topic foobar', expected: 'hi <a class="external" target="_blank" rel="noopener noreferrer" href="http://help.nextcloud.com/category/topic">http://help.nextcloud.com/category/topic</a> foobar'},
			{input: 'hi https://help.nextcloud.com/category/topic foobar', expected: 'hi <a class="external" target="_blank" rel="noopener noreferrer" href="https://help.nextcloud.com/category/topic">help.nextcloud.com/category/topic</a> foobar'},
			{input: 'noreply@nextcloud.com', expected: 'noreply@nextcloud.com'},
			{input: 'hi noreply@nextcloud.com', expected: 'hi noreply@nextcloud.com'},
			{input: 'hi <noreply@nextcloud.com>', expected: 'hi <noreply@nextcloud.com>'},
			{input: 'FirebaseInstanceId.getInstance().deleteInstanceId()', expected: 'FirebaseInstanceId.getInstance().deleteInstanceId()'},
			{input: 'I mean...it', expected: 'I mean...it'},
		];
	}

	it('should parse URLs only', function () {
		dataProvider().forEach(function(data) {
			var result = OCP.Comments.plainToRich(data.input);
			expect(result).toEqual(data.expected);
		});
	});
});
