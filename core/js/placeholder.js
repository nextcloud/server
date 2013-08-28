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
 * $('#albumart').placeholder('The Album Title');
 *
 * Which will result in:
 *
 * <div id="albumart" style="background-color: rgb(123, 123, 123); ... ">T</div>
 *
 */

(function ($) {
	$.fn.placeholder = function(seed) {
		var hash = md5(seed),
			maxRange = parseInt('ffffffffff', 16),
			red = parseInt(hash.substr(0,10), 16) / maxRange * 256,
			green = parseInt(hash.substr(10,10), 16) / maxRange * 256,
			blue = parseInt(hash.substr(20,10), 16) / maxRange * 256,
			rgb = [Math.floor(red), Math.floor(green), Math.floor(blue)],
			height = this.height();
		this.css('background-color', 'rgb(' + rgb.join(',') + ')');

		// CSS rules
		this.css('color', 'rgb(255, 255, 255)');
		this.css('font-weight', 'bold');
		this.css('text-align', 'center');

		// calculate the height
		this.css('line-height', height + 'px');
		this.css('font-size', (height * 0.55) + 'px');

		if(seed !== null && seed.length) {
			this.html(seed[0].toUpperCase());
		}
	};
}(jQuery));
