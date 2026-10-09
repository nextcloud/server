/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { IColumn } from '@nextcloud/files'
import type { InjectionKey, Ref } from 'vue'

import { inject, readonly, ref } from 'vue'

/** Row height of the files list, must match `--row-height` */
const ROW_HEIGHT = 44
/** Margin around cells after the actions cell, must match `--cell-margin` */
const CELL_MARGIN = 14

/** Width of the selection checkbox cell */
const CHECKBOX_WIDTH = ROW_HEIGHT
/** Width of the actions menu button */
const ACTIONS_MENU_WIDTH = ROW_HEIGHT
/** Width of an inline action button including its margin */
export const INLINE_ACTION_WIDTH = ROW_HEIGHT + CELL_MARGIN
/** Minimal width reserved for the name column */
const MIN_NAME_WIDTH = 200

/** Widths of the columns, must match the styles of the files list */
const COLUMN_WIDTHS = {
	mime: ROW_HEIGHT * 3.5,
	size: ROW_HEIGHT * 2,
	mtime: ROW_HEIGHT * 2.5,
	custom: ROW_HEIGHT * 2.5,
}

export interface FileListLayout {
	/** Whether inline actions are shown, otherwise they are moved to the actions menu */
	inlineActions: boolean
	/** Whether the mime column is shown */
	mime: boolean
	/** Whether the size column is shown */
	size: boolean
	/** Whether the mtime column is shown */
	mtime: boolean
	/** The custom view columns to show */
	columns: IColumn[]
}

export const FileListLayoutKey: InjectionKey<Ref<FileListLayout>> = Symbol('fileListLayout')

/** The largest width needed by the inline actions of a row */
const inlineActionsWidth = ref(0)

/**
 * Report the width needed by the inline actions of a row
 *
 * @param width - The width needed by the inline actions
 */
export function reportInlineActionsWidth(width: number) {
	if (width > inlineActionsWidth.value) {
		inlineActionsWidth.value = width
	}
}

/**
 * Reset the reported inline actions width, e.g. when the shown content changes
 */
export function resetInlineActionsWidth() {
	inlineActionsWidth.value = 0
}

/**
 * Get the largest width needed by the inline actions of a row
 */
export function useInlineActionsWidth(): Readonly<Ref<number>> {
	return readonly(inlineActionsWidth)
}

/**
 * Compute which parts of the files list fit into the available width.
 * If space is tight, the following parts are hidden in this order
 * until the name column has at least `MIN_NAME_WIDTH` space left:
 * inline actions, mime column, custom view columns (last first).
 * The size and mtime columns are kept as available.
 *
 * @param width - The available width of the files list
 * @param available - The parts of the files list available in the current view
 * @param inlineActionsWidth - The width needed by the inline actions
 */
export function getFileListLayout(width: number, available: FileListLayout, inlineActionsWidth: number): FileListLayout {
	const layout: FileListLayout = { ...available, columns: [...available.columns] }

	const cellWidth = (columnWidth: number) => columnWidth + 2 * CELL_MARGIN
	const getUsedWidth = () => CHECKBOX_WIDTH
		+ ACTIONS_MENU_WIDTH
		+ (layout.inlineActions ? inlineActionsWidth : 0)
		+ (layout.mime ? cellWidth(COLUMN_WIDTHS.mime) : 0)
		+ (layout.size ? cellWidth(COLUMN_WIDTHS.size) : 0)
		+ (layout.mtime ? cellWidth(COLUMN_WIDTHS.mtime) : 0)
		+ layout.columns.length * cellWidth(COLUMN_WIDTHS.custom)

	const reductions: (() => void)[] = [
		() => { layout.inlineActions = false },
		() => { layout.mime = false },
		...available.columns.map(() => () => { layout.columns.pop() }),
	]

	for (const reduce of reductions) {
		if (width - getUsedWidth() >= MIN_NAME_WIDTH) {
			break
		}
		reduce()
	}

	return layout
}

/**
 * Get the current layout of the files list as provided by the files list
 */
export function useFileListLayout(): Ref<FileListLayout> {
	return inject(FileListLayoutKey, ref({
		inlineActions: true,
		mime: false,
		size: false,
		mtime: false,
		columns: [],
	}))
}
