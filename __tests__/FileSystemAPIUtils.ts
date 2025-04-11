/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { basename } from 'node:path'
import mime from 'mime'

class FileSystemEntry {

	private _isFile: boolean
	private _fullPath: string

	constructor(isFile: boolean, fullPath: string) {
		this._isFile = isFile
		this._fullPath = fullPath
	}

	get isFile() {
		return !!this._isFile
	}

	get isDirectory() {
		return !this.isFile
	}

	get name() {
		return basename(this._fullPath)
	}

}

export class FileSystemFileEntry extends FileSystemEntry {

	private _contents: string
	private _lastModified: number

	constructor(fullPath: string, contents: string, lastModified = Date.now()) {
		super(true, fullPath)
		this._contents = contents
		this._lastModified = lastModified
	}

	file(success: (file: File) => void) {
		const lastModified = this._lastModified
		// Faking the mime by using the file extension
		const type = mime.getType(this.name) || ''
		success(new File([this._contents], this.name, { lastModified, type }))
	}

}

export class FileSystemDirectoryEntry extends FileSystemEntry {

	private _entries: FileSystemEntry[]

	constructor(fullPath: string, entries: FileSystemEntry[]) {
		super(false, fullPath)
		this._entries = entries || []
	}

	createReader() {
		let read = false
		return {
			readEntries: (success: (entries: FileSystemEntry[]) => void) => {
				if (read) {
					return success([])
				}
				read = true
				success(this._entries)
			},
		}
	}

}

/**
 * This mocks the File API's File class
 * It will allow us to test the Filesystem API as well as the
 * File API in the same test suite.
 */
export class DataTransferItem {

	private _type: string
	private _entry: FileSystemEntry

	getAsEntry?: () => FileSystemEntry

	constructor(type = '', entry: FileSystemEntry, isFileSystemAPIAvailable = true) {
		this._type = type
		this._entry = entry

		// Only when the Files API is available we are
		// able to get the entry
		if (isFileSystemAPIAvailable) {
			this.getAsEntry = () => this._entry
		}
	}

	get kind() {
		return 'file'
	}

	get type() {
		return this._type
	}

	getAsFile(): File|null {
		if (this._entry.isFile && this._entry instanceof FileSystemFileEntry) {
			let file: File | null = null
			this._entry.file((f) => {
				file = f
			})
			return file
		}

		// The browser will return an empty File object if the entry is a directory
		return new File([], this._entry.name, { type: '' })
	}

}

export const fileSystemEntryToDataTransferItem = (entry: FileSystemEntry, isFileSystemAPIAvailable = true): DataTransferItem => {
	return new DataTransferItem(
		entry.isFile ? 'text/plain' : 'httpd/unix-directory',
		entry,
		isFileSystemAPIAvailable,
	)
}
