/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import type { Ref } from 'vue'

import { marked } from 'marked'
import { computed } from 'vue'
import dompurify from 'dompurify'

export const useMarkdown = (text: Ref<string|undefined|null>, minHeadingLevel: Ref<number|undefined>) => {
	const minHeading = computed(() => Math.min(Math.max(minHeadingLevel.value ?? 1, 1), 6))
	const renderer = new marked.Renderer()

	renderer.link = function(href, title, text) {
		let out = `<a href="${href}" rel="noreferrer noopener" target="_blank"`
		if (title) {
			out += ' title="' + title + '"'
		}
		out += '>' + text + '</a>'
		return out
	}

	renderer.image = function(href, title, text) {
		if (text) {
			return text
		}
		return title ?? ''
	}

	renderer.heading = (text: string, level: number) => {
		const headingLevel = Math.max(minHeading.value, level)
		return `<h${headingLevel}>${text}</h${headingLevel}>`
	}

	const html = computed(() => dompurify.sanitize(
		marked((text.value ?? '').trim(), {
			renderer,
			gfm: false,
			breaks: false,
			pedantic: false,
		}),
		{
			SAFE_FOR_JQUERY: true,
			ALLOWED_TAGS: [
				'h1',
				'h2',
				'h3',
				'h4',
				'h5',
				'h6',
				'strong',
				'p',
				'a',
				'ul',
				'ol',
				'li',
				'em',
				'del',
				'blockquote',
			],
		},
	))

	return { html }
}
