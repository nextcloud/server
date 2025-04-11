/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { beforeAll, describe, expect, it, vi } from 'vitest'

import { FileSystemDirectoryEntry, FileSystemFileEntry, fileSystemEntryToDataTransferItem, DataTransferItem as DataTransferItemMock } from '../../../../__tests__/FileSystemAPIUtils'
import { join } from 'node:path'
import { Directory, traverseTree } from './DropServiceUtils'
import { dataTransferToFileTree } from './DropService'
import logger from '../logger'

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
const buildFileSystemDirectoryEntry = (path: string, tree: any): FileSystemDirectoryEntry => {
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

const buildDataTransferItemArray = (path: string, tree: any, isFileSystemAPIAvailable = true): DataTransferItemMock[] => {
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
