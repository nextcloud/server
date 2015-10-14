/**
 * Strengthify - show the weakness of a password (uses zxcvbn for this)
 * https://github.com/kabum/strengthify
 *
 * Version: 0.4.1
 * Author: Morris Jobke (github.com/kabum)
 *
 * License:
 *
 * The MIT License (MIT)
 *
 * Copyright (c) 2013 Morris Jobke <morris.jobke@gmail.com>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy of
 * this software and associated documentation files (the "Software"), to deal in
 * the Software without restriction, including without limitation the rights to
 * use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of
 * the Software, and to permit persons to whom the Software is furnished to do so,
 * subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS
 * FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR
 * COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER
 * IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN
 * CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */

/* global jQuery */
(function ($) {
	$.fn.strengthify = function(paramOptions) {
		var me = this,
			defaults = {
				zxcvbn: 'zxcvbn/zxcvbn.js',
				titles: [
					'Weakest',
					'Weak',
					'So-so',
					'Good',
					'Perfect'
				]
			},
			options = $.extend(defaults, paramOptions);

		// add elements
		$('.strengthify-wrapper')
			.append('<div class="strengthify-bg" />')
			.append('<div class="strengthify-container" />')
			.append('<div class="strengthify-separator" style="left: 25%" />')
			.append('<div class="strengthify-separator" style="left: 50%" />')
			.append('<div class="strengthify-separator" style="left: 75%" />');

		$.ajax({
			cache: true,
			dataType: 'script',
			url: options.zxcvbn
		}).done(function() {
			me.bind('keyup input', function() {
				var password = $(this).val(),
					// hide strengthigy if no input is provided
					opacity = (password === '') ? 0 : 1,
					// calculate result
					result = zxcvbn(password),
					css = '',
					// cache jQuery selections
					$container = $('.strengthify-container'),
					$wrapper = $('.strengthify-wrapper');

				$wrapper.children().css(
					'opacity',
					opacity
				).css(
					'-ms-filter',
					'"progid:DXImageTransform.Microsoft.Alpha(Opacity=' + opacity * 100 + ')"'
				);

				// style strengthify bar
				// possible scores: 0-4
				switch(result.score) {
					case 0:
					case 1:
						css = 'password-bad';
						break;
					case 2:
						css = 'password-medium';
						break;
					case 3:
					case 4:
						css = 'password-good';
						break;
				}

				$container
					.attr('class', css + ' strengthify-container')
					// possible scores: 0-4
					.css(
						'width',
						// if score is '0' it will be changed to '1' to
						// not hide strengthify if the password is extremely weak
						((result.score === 0 ? 1 : result.score) * 25) + '%'
					);

				// set a title for the wrapper
				$wrapper.attr(
					'title',
					options.titles[result.score]
				).tipsy({
					trigger: 'manual',
					opacity: opacity
				}).tipsy(
					'show'
				);

				if(opacity === 0) {
					$wrapper.tipsy(
						'hide'
					);
				}

				// reset state for empty string password
				if(password === '') {
					$container.css('width', 0);
				}

			});
		});

		return me;
	};

}(jQuery));
