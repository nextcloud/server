/**
 * @copyright 2016-2018 John Molakvoæ <skjnldsv@protonmail.com>
 * @copyright 2013 Morris Jobke <morris.jobke@gmail.com>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 * @author Julius Härtl <jus@bitgrid.net>
 * @author Sergey Shliakhov <husband.sergey@gmail.com>
 *
 * @license AGPL-3.0-or-later
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

/* eslint-disable */
import $ from 'jquery'
import md5 from 'blueimp-md5'

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
 * <div id="albumart" style="background-color: rgb(121, 90, 171); ... ">T</div>
 *
 * You may also call it like this, to have a different background, than the seed:
 *
 * $('#albumart').imageplaceholder('The Album Title', 'Album Title');
 *
 * Resulting in:
 *
 * <div id="albumart" style="background-color: rgb(121, 90, 171); ... ">A</div>
 *
 */

/*
* Alternatively, you can use the prototype function to convert your string to rgb colors:
*
* "a6741a86aded5611a8e46ce16f2ad646".toRgb()
*
* Will return the rgb parameters within the following object:
*
* Color {r: 208, g: 158, b: 109}
*
*/

const toRgb = (s) => {
	// Normalize hash
	var hash = s.toLowerCase()

	// Already a md5 hash?
	if (hash.match(/^([0-9a-f]{4}-?){8}$/) === null) {
		hash = md5(hash)
	}

	hash = hash.replace(/[^0-9a-f]/g, '')

	function Color(r, g, b) {
		this.r = r
		this.g = g
		this.b = b
	}

	function stepCalc(steps, ends) {
		var step = new Array(3)
		step[0] = (ends[1].r - ends[0].r) / steps
		step[1] = (ends[1].g - ends[0].g) / steps
		step[2] = (ends[1].b - ends[0].b) / steps
		return step
	}

	function mixPalette(steps, color1, color2) {
		var palette = []
		palette.push(color1)
		var step = stepCalc(steps, [color1, color2])
		for (var i = 1; i < steps; i++) {
			var r = parseInt(color1.r + (step[0] * i))
			var g = parseInt(color1.g + (step[1] * i))
			var b = parseInt(color1.b + (step[2] * i))
			palette.push(new Color(r, g, b))
		}
		return palette
	}

	const red = new Color(182, 70, 157);
	const yellow = new Color(221, 203, 85);
	const blue = new Color(0, 130, 201); // Nextcloud blue
	// Number of steps to go from a color to another
	// 3 colors * 6 will result in 18 generated colors
	const steps = 6;

	const palette1 = mixPalette(steps, red, yellow);
	const palette2 = mixPalette(steps, yellow, blue);
	const palette3 = mixPalette(steps, blue, red);

	const finalPalette = palette1.concat(palette2).concat(palette3);

	// Convert a string to an integer evenly
	function hashToInt(hash, maximum) {
		var finalInt = 0
		var result = []

		// Splitting evenly the string
		for (var i = 0; i < hash.length; i++) {
			// chars in md5 goes up to f, hex:16
			result.push(parseInt(hash.charAt(i), 16) % 16)
		}
		// Adds up all results
		for (var j in result) {
			finalInt += result[j]
		}
		// chars in md5 goes up to f, hex:16
		// make sure we're always using int in our operation
		return parseInt(parseInt(finalInt) % maximum)
	}

	return finalPalette[hashToInt(hash, steps * 3)]
}

String.prototype.toRgb = function() {
	OC.debug && console.warn('String.prototype.toRgb is deprecated! It will be removed in Nextcloud 22.')

	return toRgb(this)
}

$.fn.imageplaceholder = function(seed, text, size) {
	text = text || seed

	// Compute the hash
	var rgb = toRgb(seed)
	this.css('background-color', 'rgb(' + rgb.r + ', ' + rgb.g + ', ' + rgb.b + ')')

	// Placeholders are square
	var height = this.height() || size || 32
	this.height(height)
	this.width(height)

	// CSS rules
	this.css('color', '#fff')
	this.css('font-weight', 'normal')
	this.css('text-align', 'center')

	// calculate the height
	this.css('line-height', height + 'px')
	this.css('font-size', (height * 0.55) + 'px')

	if (seed !== null && seed.length) {
		var placeholderText = text.replace(/\s+/g, ' ').trim().split(' ', 2).map((word) => word[0].toUpperCase()).join('')
		this.html(placeholderText);
	}
}

$.fn.clearimageplaceholder = function() {
	this.css('background-color', '')
	this.css('color', '')
	this.css('font-weight', '')
	this.css('text-align', '')
	this.css('line-height', '')
	this.css('font-size', '')
	this.html('')
	this.removeClass('icon-loading')
	this.removeClass('icon-loading-small')
}
