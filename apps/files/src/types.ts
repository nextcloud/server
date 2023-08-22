/**
 * @copyright Copyright (c) 2023 John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @license AGPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
import type { Folder, Node } from '@nextcloud/files'
import type { Upload } from '@nextcloud/upload'

// Global definitions
export type Service = string
export type FileId = number
export type ViewId = string

// Files store
export type FilesStore = {
	[fileid: FileId]: Node
}

export type RootsStore = {
	[service: Service]: Folder
}

export type FilesState = {
	files: FilesStore,
	roots: RootsStore,
}

export interface RootOptions {
	root: Folder
	service: Service
}

// Paths store
export type PathConfig = {
	[path: string]: number
}

export type ServicesState = {
	[service: Service]: PathConfig
}

export type PathsStore = {
	paths: ServicesState
}

export interface PathOptions {
	service: Service
	path: string
	fileid: FileId
}

// User config store
export interface UserConfig {
	[key: string]: boolean
}
export interface UserConfigStore {
	userConfig: UserConfig
}

export interface SelectionStore {
	selected: FileId[]
	lastSelection: FileId[]
	lastSelectedIndex: number | null
}

// Actions menu store
export type GlobalActions = 'global'
export interface ActionsMenuStore {
	opened: GlobalActions|string|null
}

// View config store
export interface ViewConfig {
	[key: string]: string|boolean
}
export interface ViewConfigs {
	[viewId: ViewId]: ViewConfig
}
export interface ViewConfigStore {
	viewConfig: ViewConfigs
}

// Renaming store
export interface RenamingStore {
	renamingNode?: Node
	newName: string
}

// Uploader store
export interface UploaderStore {
	queue: Upload[]
}

// Drag and drop store
export interface DragAndDropStore {
	dragging: FileId[]
}

