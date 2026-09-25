/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { join } from 'node:path'
import { beforeAll, beforeEach, describe, expect, it, vi } from 'vitest'
import { DataTransferItem as DataTransferItemMock, FileSystemDirectoryEntry, fileSystemEntryToDataTransferItem, FileSystemFileEntry } from '../../../../__tests__/FileSystemAPIUtils.ts'
import { logger } from '../utils/logger.ts'
import { newNodeName } from '../utils/newNodeDialog.ts'
import { dataTransferToFileTree } from './DropService.ts'
import { Directory, renameInvalidDroppedEntries, traverseTree } from './DropServiceUtils.ts'

vi.mock('@nextcloud/dialogs')
vi.mock('../utils/newNodeDialog.ts')
vi.mock('@nextcloud/capabilities', () => ({
	getCapabilities: () => ({
		files: {
			forbidden_filename_characters: ['/', '\\'],
			forbidden_filenames: ['.htaccess'],
			forbidden_filename_basenames: [],
			forbidden_filename_extensions: ['.part', ' '],
		},
	}),
}))

const dataTree = {
	'file0.txt': ['Hello, world!', 1234567890],
	dir1: {
		'file1.txt': ['Hello, world!', 4567891230],
		'file2.txt': ['Hello, world!', 7891234560],
	},
	dir2: {
		'file3.txt': ['Hello, world!', 1234567890],
	},
}

// This is mocking a file tree using the FileSystem API
function buildFileSystemDirectoryEntry(path: string, tree: any): FileSystemDirectoryEntry {
	const entries = Object.entries(tree).map(([name, contents]) => {
		const fullPath = join(path, name)
		if (Array.isArray(contents)) {
			return new FileSystemFileEntry(fullPath, contents[0], contents[1])
		} else {
			return buildFileSystemDirectoryEntry(fullPath, contents)
		}
	})
	return new FileSystemDirectoryEntry(path, entries)
}

function buildDataTransferItemArray(path: string, tree: any, isFileSystemAPIAvailable = true): DataTransferItemMock[] {
	return Object.entries(tree).map(([name, contents]) => {
		const fullPath = join(path, name)
		if (Array.isArray(contents)) {
			const entry = new FileSystemFileEntry(fullPath, contents[0], contents[1])
			return fileSystemEntryToDataTransferItem(entry, isFileSystemAPIAvailable)
		}

		const entry = buildFileSystemDirectoryEntry(fullPath, contents)
		return fileSystemEntryToDataTransferItem(entry, isFileSystemAPIAvailable)
	})
}

describe('Filesystem API traverseTree', () => {
	it('Should traverse a file tree from root', async () => {
		// Fake a FileSystemEntry tree
		const root = buildFileSystemDirectoryEntry('root', dataTree)
		const tree = await traverseTree(root as unknown as FileSystemEntry) as Directory

		expect(tree.name).toBe('root')
		expect(tree).toBeInstanceOf(Directory)
		expect(tree.contents).toHaveLength(3)
		expect(tree.size).toBe(13 * 4) // 13 bytes from 'Hello, world!'
	})

	it('Should traverse a file tree from a subdirectory', async () => {
		// Fake a FileSystemEntry tree
		const dir2 = buildFileSystemDirectoryEntry('dir2', dataTree.dir2)
		const tree = await traverseTree(dir2 as unknown as FileSystemEntry) as Directory

		expect(tree.name).toBe('dir2')
		expect(tree).toBeInstanceOf(Directory)
		expect(tree.contents).toHaveLength(1)
		expect(tree.contents[0].name).toBe('file3.txt')
		expect(tree.size).toBe(13) // 13 bytes from 'Hello, world!'
	})

	it('Should properly compute the last modified', async () => {
		// Fake a FileSystemEntry tree
		const root = buildFileSystemDirectoryEntry('root', dataTree)
		const rootTree = await traverseTree(root as unknown as FileSystemEntry) as Directory

		expect(rootTree.lastModified).toBe(7891234560)

		// Fake a FileSystemEntry tree
		const dir2 = buildFileSystemDirectoryEntry('root', dataTree.dir2)
		const dir2Tree = await traverseTree(dir2 as unknown as FileSystemEntry) as Directory
		expect(dir2Tree.lastModified).toBe(1234567890)
	})
})

describe('renameInvalidDroppedEntries', () => {
	beforeEach(() => vi.mocked(newNodeName).mockReset())

	it('does not prompt for a valid tree', async () => {
		const tree = new Directory('root', [
			new Directory('folder', [new File([], 'file.txt')]),
		])

		expect(await renameInvalidDroppedEntries(tree)).toBe(true)
		expect(newNodeName).not.toHaveBeenCalled()
	})

	it('renames an invalid top-level folder and keeps its contents', async () => {
		const tree = new Directory('root', [new Directory('folder\\', [new File([], 'file.txt')])])
		vi.mocked(newNodeName).mockResolvedValue('folder')

		expect(await renameInvalidDroppedEntries(tree)).toBe(true)
		expect(newNodeName).toHaveBeenCalledWith('folder\\', [], expect.objectContaining({ isFolder: true }))
		expect(tree.contents[0].name).toBe('folder')
		expect((tree.contents[0] as Directory).contents[0].name).toBe('file.txt')
	})

	it('renames an invalid nested file and keeps its content', async () => {
		const tree = new Directory('root', [
			new Directory('folder', [new File(['content'], 'file\\.txt', { type: 'text/plain' })]),
		])
		vi.mocked(newNodeName).mockResolvedValue('file.txt')

		expect(await renameInvalidDroppedEntries(tree)).toBe(true)
		expect(newNodeName).toHaveBeenCalledWith('file\\.txt', [], expect.objectContaining({ isFolder: false }))

		const file = (tree.contents[0] as Directory).contents[0]
		expect(file.name).toBe('file.txt')
		expect(file.type).toBe('text/plain')
		expect(await file.text()).toBe('content')
	})

	it('keeps the new name unique within the dropped folder', async () => {
		const tree = new Directory('root', [
			new File([], 'file.txt'),
			new File([], 'file\\.txt'),
		])
		vi.mocked(newNodeName).mockResolvedValue('file.txt')

		expect(await renameInvalidDroppedEntries(tree)).toBe(true)
		expect(newNodeName).toHaveBeenCalledWith('file\\.txt', ['file.txt'], expect.anything())
		expect(tree.contents[1].name).toBe('file (1).txt')
	})

	it('trims the name returned by the dialog', async () => {
		const tree = new Directory('root', [new Directory('folder\\')])
		vi.mocked(newNodeName).mockResolvedValue(' folder ')

		expect(await renameInvalidDroppedEntries(tree)).toBe(true)
		expect(tree.contents[0].name).toBe('folder')
	})

	it('aborts when the user cancels the rename', async () => {
		const tree = new Directory('root', [new Directory('folder\\')])
		vi.mocked(newNodeName).mockResolvedValue(null)

		expect(await renameInvalidDroppedEntries(tree)).toBe(false)
		expect(tree.contents[0].name).toBe('folder\\')
	})
})

describe('DropService dataTransferToFileTree', () => {
	beforeAll(() => {
		// @ts-expect-error jsdom doesn't have DataTransferItem
		delete window.DataTransferItem
		// DataTransferItem doesn't exists in jsdom, let's mock
		// a dumb one so we can check the instanceof
		// @ts-expect-error jsdom doesn't have DataTransferItem
		window.DataTransferItem = DataTransferItemMock
	})

	it('Should return a RootDirectory with Filesystem API', async () => {
		vi.spyOn(logger, 'error').mockImplementation(() => vi.fn())
		vi.spyOn(logger, 'warn').mockImplementation(() => vi.fn())

		const dataTransferItems = buildDataTransferItemArray('root', dataTree)
		const fileTree = await dataTransferToFileTree(dataTransferItems as unknown as DataTransferItem[])

		expect(fileTree.name).toBe('root')
		expect(fileTree).toBeInstanceOf(Directory)
		expect(fileTree.contents).toHaveLength(3)

		// The file tree should be recursive when using the Filesystem API
		expect(fileTree.contents[1]).toBeInstanceOf(Directory)
		expect((fileTree.contents[1] as Directory).contents).toHaveLength(2)
		expect(fileTree.contents[2]).toBeInstanceOf(Directory)
		expect((fileTree.contents[2] as Directory).contents).toHaveLength(1)

		expect(logger.error).not.toBeCalled()
		expect(logger.warn).not.toBeCalled()
	})

	it('Should return a RootDirectory with legacy File API ignoring recursive directories', async () => {
		vi.spyOn(logger, 'error').mockImplementation(() => vi.fn())
		vi.spyOn(logger, 'warn').mockImplementation(() => vi.fn())

		const dataTransferItems = buildDataTransferItemArray('root', dataTree, false)

		const fileTree = await dataTransferToFileTree(dataTransferItems as unknown as DataTransferItem[])

		expect(fileTree.name).toBe('root')
		expect(fileTree).toBeInstanceOf(Directory)
		expect(fileTree.contents).toHaveLength(1)

		// The file tree should be recursive when using the Filesystem API
		expect(fileTree.contents[0]).not.toBeInstanceOf(Directory)
		expect((fileTree.contents[0].name)).toBe('file0.txt')

		expect(logger.error).not.toBeCalled()
		expect(logger.warn).toHaveBeenNthCalledWith(1, 'Could not get FilesystemEntry of item, falling back to file')
		expect(logger.warn).toHaveBeenNthCalledWith(2, 'Could not get FilesystemEntry of item, falling back to file')
		expect(logger.warn).toHaveBeenNthCalledWith(3, 'Browser does not support Filesystem API. Directories will not be uploaded')
		expect(logger.warn).toHaveBeenNthCalledWith(4, 'Could not get FilesystemEntry of item, falling back to file')
		expect(logger.warn).toHaveBeenCalledTimes(4)
	})
})
