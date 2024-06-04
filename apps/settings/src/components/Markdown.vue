<!--
  - SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div class="settings-markdown" v-html="renderMarkdown" />
</template>

<script>
import { marked } from 'marked'
import dompurify from 'dompurify'

export default {
	name: 'Markdown',
	props: {
		text: {
			type: String,
			default: '',
		},
		minHeading: {
			type: Number,
			default: 1,
		},
	},
	computed: {
		renderMarkdown() {
			const renderer = new marked.Renderer()
			renderer.link = function(href, title, text) {
				let prot
				try {
					prot = decodeURIComponent(unescape(href))
						.replace(/[^\w:]/g, '')
						.toLowerCase()
				} catch (e) {
					return ''
				}

				if (prot.indexOf('http:') !== 0 && prot.indexOf('https:') !== 0) {
					return ''
				}

				let out = '<a href="' + href + '" rel="noreferrer noopener"'
				if (title) {
					out += ' title="' + title + '"'
				}
				out += '>' + text + '</a>'
				return out
			}
			renderer.heading = (text, level) => {
				level = Math.min(6, level + (this.minHeading - 1))
				return `<h${level}>${text}</h${level}>`
			}
			renderer.image = function(href, title, text) {
				if (text) {
					return text
				}
				return title
			}
			renderer.blockquote = function(quote) {
				return quote
			}
			return dompurify.sanitize(
				marked(this.text.trim(), {
					renderer,
					gfm: false,
					highlight: false,
					tables: false,
					breaks: false,
					pedantic: false,
					sanitize: true,
					smartLists: true,
					smartypants: false,
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
			)
		},
	},
}
</script>

<style scoped lang="scss">
	.settings-markdown::v-deep {

	h1,
	h2,
	h3,
	h4,
	h5,
	h6 {
		font-weight: 600;
		line-height: 120%;
		margin-top: 24px;
		margin-bottom: 12px;
		color: var(--color-main-text);
	}

	h1 {
		font-size: 36px;
		margin-top: 48px;
	}

	h2 {
		font-size: 28px;
		margin-top: 48px;
	}

	h3 {
		font-size: 24px;
	}

	h4 {
		font-size: 21px;
	}

	h5 {
		font-size: 17px;
	}

	h6 {
		font-size: var(--default-font-size);
	}

	pre {
		white-space: pre;
		overflow-x: auto;
		background-color: var(--color-background-dark);
		border-radius: var(--border-radius);
		padding: 1em 1.3em;
		margin-bottom: 1em;
	}

	p code {
		background-color: var(--color-background-dark);
		border-radius: var(--border-radius);
		padding: .1em .3em;
	}

	li {
		position: relative;
	}

	ul, ol {
		padding-left: 10px;
		margin-left: 10px;
	}

	ul li {
		list-style-type: disc;
	}

	ul > li > ul > li {
		list-style-type: circle;
	}

	ul > li > ul > li ul li {
		list-style-type: square;
	}

	blockquote {
		padding-left: 1em;
		border-left: 4px solid var(--color-primary-element);
		color: var(--color-text-maxcontrast);
		margin-left: 0;
		margin-right: 0;
	}

	}
</style>
