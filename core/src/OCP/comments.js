/**
 * @copyright (c) 2017 Arthur Schiwon <blizzz@arthur-schiwon.de>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @license GNU AGPL version 3 or any later version
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

import $ from 'jquery'

/*
 * Detects links:
 * Either the http(s) protocol is given or two strings, basically limited to ascii with the last
 * word being at least one digit long,
 * followed by at least another character
 *
 * The downside: anything not ascii is excluded. Not sure how common it is in areas using different
 * alphabets… the upside: fake domains with similar looking characters won't be formatted as links
 */
const urlRegex = /(\s|^)(https?:\/\/)?((?:[-A-Z0-9+_]+\.)+[-A-Z]+(?:\/[-A-Z0-9+&@#%?=~_|!:,.;()]*)*)(\s|$)/ig

/**
 * @param content
 */
export function plainToRich(content) {
	return this.formatLinksRich(content)
}

/**
 * @param content
 */
export function richToPlain(content) {
	return this.formatLinksPlain(content)
}

/**
 * @param content
 */
export function formatLinksRich(content) {
	return content.replace(urlRegex, function(_, leadingSpace, protocol, url, trailingSpace) {
		let linkText = url
		if (!protocol) {
			protocol = 'https://'
		} else if (protocol === 'http://') {
			linkText = protocol + url
		}

		return leadingSpace + '<a class="external" target="_blank" rel="noopener noreferrer" href="' + protocol + url + '">' + linkText + '</a>' + trailingSpace
	})
}

/**
 * @param content
 */
export function formatLinksPlain(content) {
	const $content = $('<div></div>').html(content)
	$content.find('a').each(function() {
		const $this = $(this)
		$this.html($this.attr('href'))
	})
	return $content.html()
}
