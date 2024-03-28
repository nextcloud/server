import { basename } from 'node:path'

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

	constructor(fullPath: string, contents: string) {
		super(true, fullPath)
		this._contents = contents
	}

	file(success: (file: File) => void) {
		success(new File([this._contents], this.name))
	}

}

export class FileSystemDirectoryEntry extends FileSystemEntry {

	private _entries: FileSystemEntry[]

	constructor(fullPath: string, entries: FileSystemEntry[]) {
		super(false, fullPath)
		this._entries = entries || []
	}

	createReader() {
		return {
			readEntries: (success: (entries: FileSystemEntry[]) => void) => {
				success(this._entries)
			},
		}
	}

}
