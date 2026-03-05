/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

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
 * Converts plain text to rich text
 *
 * @param content - The plain text content
 */
export function plainToRich(content: string) {
	return formatLinksRich(content)
}

/**
 * Converts rich text to plain text
 *
 * @param content - The rich text content
 */
export function richToPlain(content: string) {
	return formatLinksPlain(content)
}

/**
 * Format links in the given content to rich text links
 *
 * @param content - The content containing plain text URLs
 */
export function formatLinksRich(content: string) {
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
 * Format links in the given content to plain text links
 *
 * @param content - The content containing rich text URLs
 */
export function formatLinksPlain(content: string) {
	const el = document.createElement('div')
	el.innerHTML = content
	el.querySelectorAll('a').forEach((anchor) => {
		anchor.replaceWith(document.createTextNode(anchor.getAttribute('href') || ''))
	})

	return el.innerHTML
}
