/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

describe('jquery.placeholder tests', function() {

	var $div;

	beforeEach(function() {
		$('#testArea').append($('<div id="placeholderdiv">'));
		$div = $('#placeholderdiv');
	});

	afterEach(function() {
		$div.remove();
	});

	describe('placeholder text', function() {
		it('shows one first letter if one word in a input text', function() {
			spyOn($div, 'html');
			$div.imageplaceholder('Seed', 'Name')
			expect($div.html).toHaveBeenCalledWith('N');
		});

		it('shows two first letters if two words in a input text', function() {
			spyOn($div, 'html');
			$div.imageplaceholder('Seed', 'First Second')
			expect($div.html).toHaveBeenCalledWith('FS');
		});

		it('shows two first letters if more then two words in a input text', function() {
			spyOn($div, 'html');
			$div.imageplaceholder('Seed', 'First Second Middle')
			expect($div.html).toHaveBeenCalledWith('FS');
		});
	});
});
