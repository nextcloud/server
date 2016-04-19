/**
 * ownCloud
 *
 * @author John Molakvoæ
 * @copyright 2016 John Molakvoæ <fremulon@protonmail.com>
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
 
 /*
 * Alternatively, you can use the prototype function to convert your string to hsl colors:
 *
 * "a6741a86aded5611a8e46ce16f2ad646".toHsl()
 *
 * Will return the hsl parameters within an array:
 *
 * [290, 60, 68]
 *
 */

(function ($) {

	String.prototype.toHsl = function() {

		var hash = this.toLowerCase().replace(/[^0-9a-f]+/g, '');

		// Already a md5 hash?
		if( !hash.match(/^[0-9a-f]{32}$/g) ) {
			hash = md5(hash);
		}

		function rgbToHsl(r, g, b) {
			r /= 255, g /= 255, b /= 255;
			var max = Math.max(r, g, b), min = Math.min(r, g, b);
			var h, s, l = (max + min) / 2;
			if(max === min) {
				h = s = 0; // achromatic
			} else {
				var d = max - min;
				s = l > 0.5 ? d / (2 - max - min) : d / (max + min);
				switch(max) {
				case r: h = (g - b) / d + (g < b ? 6 : 0); break;
				case g: h = (b - r) / d + 2; break;
				case b: h = (r - g) / d + 4; break;
				}
				h /= 6;
			}
			return [h, s, l];
		}

		// Init vars
		var result = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
		var rgb = [0, 0, 0];
		var sat = 70;
		var lum = 68;
		var modulo = 16;

		// Splitting evenly the string
		for(var i in hash) {
			result[i%modulo] = result[i%modulo] + parseInt(hash.charAt(i), 16).toString();
		}

		// Converting our data into a usable rgb format
		// Start at 1 because 16%3=1 but 15%3=0 and makes the repartition even
		for(var count=1;count<modulo;count++) {
			rgb[count%3] += parseInt(result[count]);
		}

		// Reduce values bigger than rgb requirements
		rgb[0] = rgb[0]%255;
		rgb[1] = rgb[1]%255;
		rgb[2] = rgb[2]%255;

		var hsl = rgbToHsl(rgb[0], rgb[1], rgb[2]);

		// Classic formulla to check the brigtness for our eye
		// If too bright, lower the sat
		var bright = Math.sqrt( 0.299 * Math.pow(rgb[0], 2) + 0.587 * Math.pow(rgb[1], 2) + 0.114 * Math.pow(rgb[2], 2) );
		if (bright >= 200) {
			sat = 60;
		}
		return [parseInt(hsl[0] * 360), sat, lum];
	};

	$.fn.imageplaceholder = function(seed, text, size) {
		text = text || seed;

		// Compute the hash
		var hsl = seed.toHsl();
		this.css('background-color', 'hsl('+hsl[0]+', '+hsl[1]+'%, '+hsl[2]+'%)');

		// Placeholders are square
		var height = this.height() || size || 32;
		this.height(height);
		this.width(height);

		// CSS rules
		this.css('color', '#fff');
		this.css('font-weight', 'normal');
		this.css('text-align', 'center');

		// calculate the height
		this.css('line-height', height + 'px');
		this.css('font-size', (height * 0.55) + 'px');

		if(seed !== null && seed.length) {
			this.html(text[0].toUpperCase());
		}
	};
}(jQuery));
