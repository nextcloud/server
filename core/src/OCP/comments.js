/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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
 *
 * This is a copy of the backend regex in IURLGenerator, make sure to adjust both when changing
 */
const urlRegex = /(\s|^)(https?:\/\/)([-A-Z0-9+_.]+(?::[0-9]+)?(?:\/[-A-Z0-9+&@#%?=~_|!:,.;()]*)*)(\s|$)/ig

/**
 * @param {any} content -
 */
export function plainToRich(content) {
	return this.formatLinksRich(content)
}

/**
 * @param {any} content -
 */
export function richToPlain(content) {
	return this.formatLinksPlain(content)
}

/**
 * @param {any} content -
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
 * @param {any} content -
 */
export function formatLinksPlain(content) {
	const $content = $('<div></div>').html(content)
	$content.find('a').each(function() {
		const $this = $(this)
		$this.html($this.attr('href'))
	})
	return $content.html()
}
