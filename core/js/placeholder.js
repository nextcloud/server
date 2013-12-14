/**
 * ownCloud
 *
 * @author Morris Jobke
 * @copyright 2013 Morris Jobke <morris.jobke@gmail.com>
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

/*
 * Adds a background color to the element called on and adds the first character
 * of the passed in string. This string is also the seed for the generation of
 * the background color.
 *
 * You have following HTML:
 *
 * <div id="albumart"></div>
 *
 * And call this from Javascript:
 *
 * $('#albumart').imageplaceholder('The Album Title');
 *
 * Which will result in:
 *
 * <div id="albumart" style="background-color: hsl(123, 90%, 65%); ... ">T</div>
 *
 * You may also call it like this, to have a different background, than the seed:
 *
 * $('#albumart').imageplaceholder('The Album Title', 'Album Title');
 *
 * Resulting in:
 *
 * <div id="albumart" style="background-color: hsl(123, 90%, 65%); ... ">A</div>
 *
 */

(function ($) {
	$.fn.imageplaceholder = function(seed, text) {
		// set optional argument "text" to value of "seed" if undefined
		text = text || seed;

		var hash = md5(seed),
			maxRange = parseInt('ffffffffffffffffffffffffffffffff', 16),
			hue = parseInt(hash, 16) / maxRange * 256,
			height = this.height();
		this.css('background-color', 'hsl(' + hue + ', 90%, 65%)');

		// CSS rules
		this.css('color', '#fff');
		this.css('font-weight', 'bold');
		this.css('text-align', 'center');

		// calculate the height
		this.css('line-height', height + 'px');
		this.css('font-size', (height * 0.55) + 'px');

		if(seed !== null && seed.length) {
			this.html(text[0].toUpperCase());
		}
	};
}(jQuery));
