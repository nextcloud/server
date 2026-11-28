/*!
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { expect, test } from 'vitest'
import { useMarkdown } from './useMarkdown.ts'

test('sanitizes raw HTML while preserving allowed formatting', () => {
	const rendered = useMarkdown('<p><strong>bold</strong> <em>italic</em> <span>plain</span><img src="https://example.com/image.jpg"><script>alert(1)</script></p>')
	expect(rendered.value).toMatchInlineSnapshot('"<p><strong>bold</strong> <em>italic</em> plain</p>"')
})

test('sanitizes raw HTML links while preserving safe URLs and attributes', () => {
	const rendered = useMarkdown('<p><a href="javascript:alert(1)" onclick="alert(1)">unsafe</a> <a href="https://example.com" title="Example" onclick="alert(1)">safe</a></p>')
	expect(rendered.value).toMatchInlineSnapshot('"<p><a>unsafe</a> <a href="https://example.com" title="Example">safe</a></p>"')
})

test('renders links', () => {
	const rendered = useMarkdown('This is [a link](http://example.com)!')
	expect(rendered.value).toMatchInlineSnapshot('"<p>This is <a href="http://example.com" rel="noreferrer noopener">a link</a>!</p>\n"')
})

test('removes links with invalid URL', () => {
	const rendered = useMarkdown('This is [a link](ftp://example.com)!')
	expect(rendered.value).toMatchInlineSnapshot('"<p>This is !</p>\n"')
})

test('renders images', () => {
	const rendered = useMarkdown('![alt text](http://example.com/image.jpg)')
	expect(rendered.value).toMatchInlineSnapshot('"<p>alt text</p>\n"')
})

test('renders images with title', () => {
	const rendered = useMarkdown('![](http://example.com/image.jpg "Title")')
	expect(rendered.value).toMatchInlineSnapshot('"<p>Title</p>\n"')
})

test('renders images with alt text and title', () => {
	const rendered = useMarkdown('![alt text](http://example.com/image.jpg "Title")')
	expect(rendered.value).toMatchInlineSnapshot(`
		"<p>alt text</p>\n"
	`)
})

test('renders block quotes', () => {
	const rendered = useMarkdown('> This is a block quote')
	expect(rendered.value).toMatchInlineSnapshot('"<blockquote>This is a block quote</blockquote>"')
})

test('renders headings', () => {
	const rendered = useMarkdown('# level 1\n## level 2\n### level 3\n#### level 4\n##### level 5\n###### level 6\n')
	expect(rendered.value).toMatchInlineSnapshot('"<h1>level 1</h1><h2>level 2</h2><h3>level 3</h3><h4>level 4</h4><h5>level 5</h5><h6>level 6</h6>"')
})

test('renders headings with minHeadingLevel', () => {
	const rendered = useMarkdown(
		'# level 1\n## level 2\n### level 3\n#### level 4\n##### level 5\n###### level 6\n',
		{ minHeadingLevel: 4 },
	)
	expect(rendered.value).toMatchInlineSnapshot('"<h4>level 1</h4><h5>level 2</h5><h6>level 3</h6><h6>level 4</h6><h6>level 5</h6><h6>level 6</h6>"')
})
