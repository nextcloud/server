/*!
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import type { File as NcFile } from '@nextcloud/files'

import { getCurrentUser } from '@nextcloud/auth'
import { File } from '@nextcloud/files'
import { isPublicShare } from '@nextcloud/sharing/public'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import {
	extractFilePathFromSource,
	extractFilePaths,
	genFileInfo,
} from '../../src/utils/fileUtils.ts'
import {
	findLivePhotoPeerFromFileId,
	findLivePhotoPeerFromName,
} from '../../src/utils/livePhotoUtils.ts'
import { isNumber } from '../../src/utils/numberUtil.ts'
import { getPreviewIfAny } from '../../src/utils/previewUtils.ts'
import { makeFile } from '../factories.ts'

// getCurrentUser drives extractFilePathFromSource.
vi.mock('@nextcloud/auth', () => ({
	getCurrentUser: vi.fn(() => ({ uid: 'admin' })),
}))

// generateUrl echoes the given path so we can assert on the built query string.
vi.mock('@nextcloud/router', () => ({
	generateUrl: vi.fn((url: string) => url),
}))

// Public share helpers used by previewUtils.
vi.mock('@nextcloud/sharing/public', () => ({
	isPublicShare: vi.fn(() => false),
	getSharingToken: vi.fn(() => 'share-token'),
}))

describe('fileUtils.extractFilePaths', () => {
	it('splits a nested path into [dir, name]', () => {
		expect(extractFilePaths('/foo/bar/baz.txt')).toEqual(['/foo/bar', 'baz.txt'])
	})

	it('returns an empty dir for a bare file name', () => {
		expect(extractFilePaths('file.txt')).toEqual(['', 'file.txt'])
	})

	it('throws on a trailing slash (empty file name)', () => {
		expect(() => extractFilePaths('/foo/bar/')).toThrow(/Unable to extract file name/)
	})

	it('throws on an empty string', () => {
		expect(() => extractFilePaths('')).toThrow(/Unable to extract file name/)
	})
})

describe('fileUtils.extractFilePathFromSource', () => {
	beforeEach(() => {
		vi.mocked(getCurrentUser).mockReturnValue({ uid: 'admin' } as ReturnType<typeof getCurrentUser>)
	})

	it('extracts the path following the current user segment', () => {
		const source = 'https://cloud.example.com/remote.php/dav/files/admin/path/to/file.txt'
		expect(extractFilePathFromSource(source)).toBe('path/to/file.txt')
	})

	it('throws when the source does not contain the user segment', () => {
		const source = 'https://cloud.example.com/remote.php/dav/files/other/file.txt'
		expect(() => extractFilePathFromSource(source)).toThrow(/Unable to extract file paths/)
	})

	it('throws when there is no current user', () => {
		vi.mocked(getCurrentUser).mockReturnValueOnce(null)
		const source = 'https://cloud.example.com/remote.php/dav/files/admin/file.txt'
		expect(() => extractFilePathFromSource(source)).toThrow(/Unable to extract file paths/)
	})
})

describe('fileUtils.genFileInfo', () => {
	it('camelCases keys and coerces string booleans', () => {
		const info = genFileInfo({
			'is-favorite': 'true',
			'hide-download': 'false',
		} as never)
		expect(info).toEqual({ isFavorite: true, hideDownload: false })
	})

	it('coerces numeric strings to numbers but keeps string-typed properties as strings', () => {
		const info = genFileInfo({
			filename: '/files/admin/photo.jpg',
			basename: 'photo.jpg',
			'owner-id': 'admin',
			size: '1024',
			fileid: '42',
			etag: 'abc123',
		} as never)
		expect(info).toEqual({
			filename: '/files/admin/photo.jpg',
			basename: 'photo.jpg',
			ownerId: 'admin',
			size: 1024,
			fileid: 42,
			etag: 'abc123',
		})
		expect(typeof info.size).toBe('number')
		expect(typeof info.fileid).toBe('number')
	})

	it('keeps a fully-numeric owner-id as a string', () => {
		const info = genFileInfo({ 'owner-id': '12345' } as never)
		expect(info.ownerId).toBe('12345')
		expect(typeof info.ownerId).toBe('string')
	})

	it('flattens nested objects and merges their camelCased keys', () => {
		const info = genFileInfo({
			props: {
				'has-preview': 'true',
				getcontentlength: '2048',
			},
		} as never)
		expect(info).toEqual({ hasPreview: true, getcontentlength: 2048 })
	})

	it('preserves array values as-is', () => {
		const attrs = [{ key: 'a', scope: 'user', value: true }]
		const info = genFileInfo({ 'share-attributes': attrs } as never)
		expect(info.shareAttributes).toBe(attrs)
	})
})

describe('numberUtil.isNumber', () => {
	it.each([
		[5, true],
		[3.14, true],
		['42', true],
		['3.14', true],
	])('treats %o as a number', (input, expected) => {
		expect(isNumber(input)).toBe(expected)
	})

	it.each([
		['abc', false],
		['', false],
		['   ', false],
		['0x1F', false],
		['1e3', false],
		[NaN, false],
		[null, false],
		[undefined, false],
	])('treats %o as not a number', (input, expected) => {
		expect(isNumber(input)).toBe(expected)
	})

	// Documented quirk: the leading `!num` guard makes 0 report as non-numeric.
	it('reports 0 as not a number (falsy guard quirk)', () => {
		expect(isNumber(0)).toBe(false)
	})
})

describe('previewUtils.getPreviewIfAny', () => {
	beforeEach(() => {
		vi.mocked(isPublicShare).mockReturnValue(false)
		// Deterministic viewport so the generated x/y params are stable.
		vi.stubGlobal('devicePixelRatio', 2)
		vi.stubGlobal('screen', { width: 1000, height: 800 })
	})

	afterEach(() => {
		vi.unstubAllGlobals()
	})

	/**
	 * Build a File with the attributes previewUtils reads.
	 *
	 * @param attributes - previewUrl / hasPreview / etag overrides
	 */
	function makeFileWithAttributes(attributes: Record<string, unknown>): NcFile {
		return new File({
			id: 99,
			source: 'https://cloud.example.com/remote.php/dav/files/admin/photo.jpg',
			root: '/files/admin',
			mime: 'image/jpeg',
			owner: 'admin',
			mtime: new Date('2024-01-01T00:00:00Z'),
			size: 1024,
			attributes,
		})
	}

	it('returns a pre-computed previewUrl attribute directly', () => {
		const file = makeFileWithAttributes({ previewUrl: '/direct/preview.png', hasPreview: true })
		expect(getPreviewIfAny(file)).toBe('/direct/preview.png')
	})

	it('builds a /core/preview URL when the file has a preview (private share)', () => {
		const file = makeFileWithAttributes({ hasPreview: true })
		const url = getPreviewIfAny(file)
		expect(url).toContain('/core/preview?')
		expect(url).toContain('fileId=99')
		expect(url).toContain('x=2000')
		expect(url).toContain('y=1600')
		expect(url).toContain('a=true')
	})

	it('strips &quot; entities from the etag param', () => {
		const file = makeFileWithAttributes({ hasPreview: true, etag: '&quot;abc123&quot;' })
		expect(getPreviewIfAny(file)).toContain('etag=abc123')
	})

	it('builds a public preview URL when on a public share', () => {
		vi.mocked(isPublicShare).mockReturnValue(true)
		const file = makeFileWithAttributes({ hasPreview: true })
		const url = getPreviewIfAny(file)
		expect(url).toContain('/apps/files_sharing/publicpreview/share-token')
		expect(url).toContain('file=photo.jpg')
	})

	it('falls back to the file source when there is no preview', () => {
		const file = makeFileWithAttributes({ hasPreview: false })
		expect(getPreviewIfAny(file)).toBe(file.source)
	})
})

describe('livePhotoUtils.findLivePhotoPeerFromFileId', () => {
	it('finds the file whose fileid matches', () => {
		const a = makeFile({ id: 1 })
		const b = makeFile({ id: 2 })
		expect(findLivePhotoPeerFromFileId(2, [a, b])).toBe(b)
	})

	it('returns undefined when no file matches', () => {
		const a = makeFile({ id: 1 })
		expect(findLivePhotoPeerFromFileId(999, [a])).toBeUndefined()
	})
})

describe('livePhotoUtils.findLivePhotoPeerFromName', () => {
	it('pairs a video with the still image sharing its base name', () => {
		const video = makeFile({ id: 1, basename: 'IMG_1234.mov', mime: 'video/quicktime' })
		const photo = makeFile({ id: 2, basename: 'IMG_1234.jpg' })
		expect(findLivePhotoPeerFromName(video, [video, photo])).toBe(photo)
	})

	it('ignores non-image peers even with a matching name', () => {
		const video = makeFile({ id: 1, basename: 'IMG_1234.mov', mime: 'video/quicktime' })
		const other = makeFile({ id: 2, basename: 'IMG_1234.txt', mime: 'text/plain' })
		expect(findLivePhotoPeerFromName(video, [video, other])).toBeUndefined()
	})

	it('never returns the reference file itself', () => {
		const video = makeFile({ id: 1, basename: 'IMG_1234.mov', mime: 'video/quicktime' })
		expect(findLivePhotoPeerFromName(video, [video])).toBeUndefined()
	})

	it('accepts jpeg and png extensions', () => {
		const video = makeFile({ id: 1, basename: 'clip.mov', mime: 'video/quicktime' })
		const png = makeFile({ id: 2, basename: 'clip.png' })
		expect(findLivePhotoPeerFromName(video, [video, png])).toBe(png)
	})
})
