import type { Tokens } from 'marked'
import type { MaybeRefOrGetter } from 'vue'

import dompurify from 'dompurify'
import { marked } from 'marked'
import { computed, toValue } from 'vue'

export interface MarkdownOptions {
	minHeadingLevel?: number
}

/**
 * Render Markdown to HTML
 *
 * @param text - The Markdown source
 * @param options - Markdown options
 */
export function useMarkdown(text: MaybeRefOrGetter<string>, options?: MarkdownOptions) {
	const renderer = new marked.Renderer()
	renderer.blockquote = markedBlockquote
	renderer.link = markedLink
	renderer.image = markedImage

	return computed(() => {
		const minHeading = options?.minHeadingLevel ?? 1
		renderer.heading = getMarkedHeading(minHeading)
		const markdown = toValue(text).trim()

		return dompurify.sanitize(
			marked(markdown, {
				async: false,
				renderer,
				gfm: false,
				breaks: false,
				pedantic: false,
			}),
			{
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
	})
}

/**
 * Custom link renderer that only allows http and https links
 *
 * @param ctx - The render context
 * @param ctx.href - The link href
 * @param ctx.title - The link title
 * @param ctx.text - The link text
 */
function markedLink({ href, title, text }: Tokens.Link) {
	let url: URL
	try {
		url = new URL(href)
	} catch {
		return ''
	}

	if (url.protocol !== 'http:' && url.protocol !== 'https:') {
		return ''
	}

	let out = '<a href="' + href + '" rel="noreferrer noopener"'
	if (title) {
		out += ' title="' + title + '"'
	}
	out += '>' + text + '</a>'
	return out
}

/**
 * Only render image alt text or title
 *
 * @param ctx - The render context
 * @param ctx.title - The image title
 * @param ctx.text - The image alt text
 */
function markedImage({ title, text }: Tokens.Image): string {
	if (text) {
		return text
	}
	return title ?? ''
}

/**
 * Render block quotes without any special styling
 *
 * @param ctx - The render context
 * @param ctx.text - The blockquote text
 */
function markedBlockquote({ text }: Tokens.Blockquote): string {
	return `<blockquote>${text}</blockquote>`
}

/**
 * Get a custom heading renderer that clamps heading levels
 *
 * @param minHeading - The heading to clamp to
 */
function getMarkedHeading(minHeading: number) {
	/**
	 * Custom heading renderer that adjusts heading levels
	 *
	 * @param ctx - The render context
	 * @param ctx.text - The heading text
	 * @param ctx.depth - The heading depth
	 */
	return ({ text, depth }: Tokens.Heading): string => {
		depth = Math.min(6, depth + (minHeading - 1))
		return `<h${depth}>${text}</h${depth}>`
	}
}
