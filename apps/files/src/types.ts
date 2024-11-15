/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import type { Folder, Node } from '@nextcloud/files'
import type { Upload } from '@nextcloud/upload'

// Global definitions
export type Service = string
export type FileSource = string
export type ViewId = string

// Files store
export type FilesStore = {
	[source: FileSource]: Node
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
	[path: string]: FileSource
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
	source: FileSource
}

// User config store
export interface UserConfig {
	[key: string]: boolean
}
export interface UserConfigStore {
	userConfig: UserConfig
}

export interface SelectionStore {
	selected: FileSource[]
	lastSelection: FileSource[]
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
	dragging: FileSource[]
}

export interface TemplateFile {
	app: string
	label: string
	extension: string
	iconClass?: string
	iconSvgInline?: string
	mimetypes: string[]
	ratio?: number
	templates?: Record<string, unknown>[]
}

export type Capabilities = {
	files: {
		bigfilechunking: boolean
		blacklisted_files: string[]
		forbidden_filename_basenames: string[]
		forbidden_filename_characters: string[]
		forbidden_filename_extensions: string[]
		forbidden_filenames: string[]
		undelete: boolean
		version_deletion: boolean
		version_labeling: boolean
		versioning: boolean
	}
}

export enum MoveCopyAction {
	MOVE = 'Move',
	COPY = 'Copy',
	MOVE_OR_COPY = 'move-or-copy',
}

export type MoveCopyResult = {
	destination: Folder
	action: MoveCopyAction.COPY | MoveCopyAction.MOVE
}
