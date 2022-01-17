/**
 * Copyright (c) 2019 Serhii Shliakhov <shlyakhov.up@gmail.com>
 *
 * This file is licensed under the Affero General Public License version 3
 * or later.
 *
 * See the COPYING-README file.
 *
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
