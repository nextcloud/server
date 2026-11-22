/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { IColumn } from '@nextcloud/files'
import type { FileListLayout } from './useFileListLayout.ts'

import { readFileSync } from 'node:fs'
import { dirname, resolve } from 'node:path'
import { fileURLToPath } from 'node:url'
import { beforeEach, describe, expect, it } from 'vitest'
import { isReadonly } from 'vue'
import {
	COLUMN_WIDTHS,
	DROPPABLE_BUILTIN_COLUMNS,
	getFileListLayout,
	INLINE_ACTION_WIDTH,
	KEPT_BUILTIN_COLUMNS,
	reportInlineActionsWidth,
	resetInlineActionsWidth,
	useInlineActionsWidth,
} from './useFileListLayout.ts'

/**
 * Widths the files list layout is expected to reserve.
 * These numbers are the contract: they are asserted against the stylesheet,
 * the row markup, and `getFileListLayout`. Adding a column without an entry
 * here fails the suite.
 */
const ROW_HEIGHT = 44
const CELL_MARGIN = 14
const MIN_NAME_WIDTH = 200
const CHECKBOX_WIDTH = ROW_HEIGHT
const ACTIONS_MENU_WIDTH = ROW_HEIGHT

const columnTests: Record<keyof typeof COLUMN_WIDTHS, {
	cssClass: string
	/** Multiplier of `--row-height` for the column content, excluding cell margin */
	multiplier: number
	behavior: 'dropped' | 'kept'
}> = {
	mime: {
		cssClass: 'files-list__row-mime',
		multiplier: 3.5,
		behavior: 'dropped',
	},
	size: {
		cssClass: 'files-list__row-size',
		multiplier: 2,
		behavior: 'kept',
	},
	mtime: {
		cssClass: 'files-list__row-mtime',
		multiplier: 2.5,
		behavior: 'kept',
	},
	custom: {
		cssClass: 'files-list__row-column-custom',
		multiplier: 2.5,
		behavior: 'dropped',
	},
}

const ROW_COMPONENTS = [
	'../components/FileEntry.vue',
	'../components/FilesListTableHeader.vue',
	'../components/FilesListTableFooter.vue',
]

/** Row classes that are not data columns. */
const STRUCTURAL_ROW_CLASSES = new Set([
	'files-list__row-head',
	'files-list__row-checkbox',
	'files-list__row-name',
	'files-list__row-icon',
	'files-list__row-actions',
])

describe('useFileListLayout', () => {
	beforeEach(() => {
		resetInlineActionsWidth()
	})

	describe('column coverage', () => {
		it('fails when a column is added without a matching test', () => {
			expect(Object.keys(COLUMN_WIDTHS).sort()).toEqual(Object.keys(columnTests).sort())
			expect([...DROPPABLE_BUILTIN_COLUMNS, ...KEPT_BUILTIN_COLUMNS, 'custom'].sort())
				.toEqual(Object.keys(columnTests).sort())
		})

		it('classifies every built-in column as dropped or kept', () => {
			for (const column of DROPPABLE_BUILTIN_COLUMNS) {
				expect(columnTests[column].behavior).toBe('dropped')
			}
			for (const column of KEPT_BUILTIN_COLUMNS) {
				expect(columnTests[column].behavior).toBe('kept')
			}
			expect(columnTests.custom.behavior).toBe('dropped')
			expect(DROPPABLE_BUILTIN_COLUMNS.length + KEPT_BUILTIN_COLUMNS.length)
				.toBe(Object.keys(COLUMN_WIDTHS).length - 1)
		})

		it('fails when a rendered column has no matching width test', () => {
			const expected = Object.values(columnTests).map(({ cssClass }) => cssClass).sort()
			for (const path of ROW_COMPONENTS) {
				expect(columnClasses(readVue(path)).sort(), path).toEqual(expected)
			}
		})

		it('fails when the stylesheet gains a column width without a matching test', () => {
			const expected = Object.fromEntries(Object.values(columnTests).map(({ cssClass, multiplier }) => [cssClass, multiplier]))
			expect(stylesheetColumnMultipliers(listStylesheet())).toEqual(expected)
		})
	})

	describe('width contract', () => {
		it('uses the row height and cell margin from the files list stylesheet', () => {
			const style = listStylesheet()
			expect(style).toMatch(/--row-height:\s*44px/)
			expect(style).toMatch(/--cell-margin:\s*14px/)
			expect(style).toMatch(/td\s*,\s*th\s*\{[^}]*width:\s*var\(--row-height\)/)
		})

		it('reserves the checkbox and the actions menu at one row height each', () => {
			expect(CHECKBOX_WIDTH + ACTIONS_MENU_WIDTH).toBe(88)
		})

		it('sizes an inline action as the row height plus one cell margin', () => {
			expect(INLINE_ACTION_WIDTH).toBe(ROW_HEIGHT + CELL_MARGIN)
			expect(INLINE_ACTION_WIDTH).toBe(58)
		})

		it.each(Object.entries(columnTests))('column %s content width is the row height times its multiplier', (column, spec) => {
			expect(COLUMN_WIDTHS[column]).toBe(ROW_HEIGHT * spec.multiplier)
		})

		it('adds the cell margin on both sides of every column', () => {
			expect(cellWidth(COLUMN_WIDTHS.mime)).toBe(182)
			expect(cellWidth(COLUMN_WIDTHS.size)).toBe(116)
			expect(cellWidth(COLUMN_WIDTHS.mtime)).toBe(138)
			expect(cellWidth(COLUMN_WIDTHS.custom)).toBe(138)
			for (const column of Object.keys(columnTests) as (keyof typeof columnTests)[]) {
				expect(cellWidth(COLUMN_WIDTHS[column])).toBe(COLUMN_WIDTHS[column] + 2 * CELL_MARGIN)
			}
		})
	})

	describe('getFileListLayout', () => {
		it('keeps inline actions when the name column has exactly its minimum width', () => {
			const width = CHECKBOX_WIDTH + ACTIONS_MENU_WIDTH + MIN_NAME_WIDTH
			expect(width).toBe(288)
			expect(getFileListLayout(width, available({ inlineActions: true }), 0).inlineActions).toBe(true)
			expect(getFileListLayout(width - 1, available({ inlineActions: true }), 0).inlineActions).toBe(false)
		})

		it('adds the reported inline actions width on top of the actions menu', () => {
			const inlineWidth = 2 * INLINE_ACTION_WIDTH
			expect(inlineWidth).toBe(116)
			const width = CHECKBOX_WIDTH + ACTIONS_MENU_WIDTH + inlineWidth + MIN_NAME_WIDTH
			expect(width).toBe(404)
			expect(getFileListLayout(width, available({ inlineActions: true }), inlineWidth).inlineActions).toBe(true)
			expect(getFileListLayout(width - 1, available({ inlineActions: true }), inlineWidth).inlineActions).toBe(false)
		})

		it('hides inline actions before the mime column', () => {
			const inlineWidth = INLINE_ACTION_WIDTH
			const row = available({ inlineActions: true, mime: true })
			const withInline = CHECKBOX_WIDTH + ACTIONS_MENU_WIDTH + inlineWidth + cellWidth(COLUMN_WIDTHS.mime) + MIN_NAME_WIDTH
			expect(withInline).toBe(528)
			expect(visible(getFileListLayout(withInline, row, inlineWidth))).toEqual(visible(row))
			expect(visible(getFileListLayout(withInline - 1, row, inlineWidth))).toEqual({
				inlineActions: false,
				mime: true,
				size: false,
				mtime: false,
				columns: [],
			})
		})

		it.each(DROPPABLE_BUILTIN_COLUMNS)('hides the %s column one pixel below the width it needs', (column) => {
			const threshold = CHECKBOX_WIDTH + ACTIONS_MENU_WIDTH + cellWidth(COLUMN_WIDTHS[column]) + MIN_NAME_WIDTH
			const row = available({ [column]: true })
			expect(getFileListLayout(threshold, row, 0)[column]).toBe(true)
			expect(getFileListLayout(threshold - 1, row, 0)[column]).toBe(false)
		})

		it.each(KEPT_BUILTIN_COLUMNS)('keeps the %s column when nothing else fits', (column) => {
			expect(getFileListLayout(0, available({ [column]: true }), 0)[column]).toBe(true)
			expect(getFileListLayout(-1, available({ [column]: true }), 0)[column]).toBe(true)
		})

		it.each(KEPT_BUILTIN_COLUMNS)('counts the %s column when deciding if the mime column fits', (column) => {
			const mimeCell = cellWidth(COLUMN_WIDTHS.mime)
			const keptCell = cellWidth(COLUMN_WIDTHS[column])
			const mimeAlone = CHECKBOX_WIDTH + ACTIONS_MENU_WIDTH + mimeCell + MIN_NAME_WIDTH
			const withKept = mimeAlone + keptCell
			const row = available({ mime: true, [column]: true })

			expect(getFileListLayout(withKept, row, 0).mime).toBe(true)
			expect(getFileListLayout(withKept, row, 0)[column]).toBe(true)
			expect(getFileListLayout(withKept - 1, row, 0).mime).toBe(false)
			expect(getFileListLayout(withKept - 1, row, 0)[column]).toBe(true)
			// The kept column occupies space the mime column would otherwise use.
			expect(getFileListLayout(mimeAlone, row, 0).mime).toBe(false)
		})

		it('distinguishes the size and mtime widths', () => {
			// mime + size = 88 + 182 + 116 + 200 = 586
			// mime + mtime = 88 + 182 + 138 + 200 = 608
			const mimeAndSize = available({ mime: true, size: true })
			const mimeAndMtime = available({ mime: true, mtime: true })

			expect(getFileListLayout(608, mimeAndMtime, 0).mime).toBe(true)
			expect(getFileListLayout(607, mimeAndMtime, 0).mime).toBe(false)
			expect(getFileListLayout(586, mimeAndSize, 0).mime).toBe(true)
			expect(getFileListLayout(585, mimeAndSize, 0).mime).toBe(false)
			// 600 fits mime beside size, and drops mime beside the wider mtime column.
			expect(getFileListLayout(600, mimeAndSize, 0).mime).toBe(true)
			expect(getFileListLayout(600, mimeAndMtime, 0).mime).toBe(false)
		})

		it('keeps size and mtime together down to the width of those two cells', () => {
			// 88 + 182 + 116 + 138 + 200 = 724
			const row = available({ mime: true, size: true, mtime: true })
			expect(visible(getFileListLayout(724, row, 0))).toEqual(visible(row))
			expect(visible(getFileListLayout(723, row, 0))).toEqual({
				inlineActions: false,
				mime: false,
				size: true,
				mtime: true,
				columns: [],
			})
			expect(visible(getFileListLayout(0, row, 0))).toEqual({
				inlineActions: false,
				mime: false,
				size: true,
				mtime: true,
				columns: [],
			})
		})

		it('hides the mime column before custom columns', () => {
			const columns = [column('notes'), column('tags')]
			const row = available({ mime: true, size: true, mtime: true, columns })
			// 88 + 182 + 116 + 138 + 138 + 138 + 200 = 1000
			expect(visible(getFileListLayout(1000, row, 0))).toEqual(visible(row))
			expect(visible(getFileListLayout(999, row, 0))).toEqual({
				inlineActions: false,
				mime: false,
				size: true,
				mtime: true,
				columns: ['notes', 'tags'],
			})
		})

		it('hides custom columns last first, one column at a time', () => {
			const columns = [column('a'), column('b'), column('c')]
			const row = available({ columns })
			// 88 + 3 * 138 + 200 = 702, then 138px per column that is hidden
			expect(columnIds(getFileListLayout(702, row, 0))).toEqual(['a', 'b', 'c'])
			expect(columnIds(getFileListLayout(701, row, 0))).toEqual(['a', 'b'])
			expect(columnIds(getFileListLayout(564, row, 0))).toEqual(['a', 'b'])
			expect(columnIds(getFileListLayout(563, row, 0))).toEqual(['a'])
			expect(columnIds(getFileListLayout(426, row, 0))).toEqual(['a'])
			expect(columnIds(getFileListLayout(425, row, 0))).toEqual([])
		})

		it('returns the remaining custom columns in their original order', () => {
			const columns = [column('a'), column('b'), column('c')]
			const row = available({ columns })
			const layout = getFileListLayout(563, row, 0)

			expect(row.columns).toBe(columns)
			expect(columnIds(row)).toEqual(['a', 'b', 'c'])
			expect(layout.columns).not.toBe(columns)
			expect(layout.columns).toEqual([columns[0]])
		})

		it('leaves a column hidden when the view does not provide it', () => {
			const wide = 5000
			expect(visible(getFileListLayout(wide, available(), 0))).toEqual({
				inlineActions: false,
				mime: false,
				size: false,
				mtime: false,
				columns: [],
			})
			expect(getFileListLayout(wide, available({ size: true }), 200).size).toBe(true)
			expect(getFileListLayout(wide, available({ size: true }), 200).mtime).toBe(false)
		})

		it('does not reserve space for a column that is unavailable', () => {
			// mime + size fits at 586. Counting a hidden mtime column would drop mime.
			const row = available({ mime: true, size: true, mtime: false, columns: [] })
			expect(getFileListLayout(600, row, 0).mime).toBe(true)
			expect(getFileListLayout(600, row, 0).mtime).toBe(false)
		})

		it('hides inline actions that take no width when another column still overflows', () => {
			const row = available({ inlineActions: true, mime: true })
			// 88 + 182 + 200 = 470. Inline width 0 does not change the total, so both are hidden together.
			expect(getFileListLayout(470, row, 0).inlineActions).toBe(true)
			expect(getFileListLayout(470, row, 0).mime).toBe(true)
			expect(getFileListLayout(469, row, 0).inlineActions).toBe(false)
			expect(getFileListLayout(469, row, 0).mime).toBe(false)
		})

		it('uses fractional widths with the same minimum name width', () => {
			const row = available({ mime: true })
			expect(getFileListLayout(469.9, row, 0).mime).toBe(false)
			expect(getFileListLayout(470, row, 0).mime).toBe(true)

			// 88 + 10.5 + 200 = 298.5
			const inline = available({ inlineActions: true })
			expect(getFileListLayout(298.5, inline, 10.5).inlineActions).toBe(true)
			expect(getFileListLayout(298.4, inline, 10.5).inlineActions).toBe(false)
		})

		it.each([
			// chrome 88 + inline 116 + mime 182 + size 116 + mtime 138 + two custom columns 276 = 916
			// The name column needs 200 more, so the full row fits from 1116px.
			[1116, { inlineActions: true, mime: true, size: true, mtime: true, columns: ['notes', 'tags'] }],
			[1115, { inlineActions: false, mime: true, size: true, mtime: true, columns: ['notes', 'tags'] }],
			[1000, { inlineActions: false, mime: true, size: true, mtime: true, columns: ['notes', 'tags'] }],
			[999, { inlineActions: false, mime: false, size: true, mtime: true, columns: ['notes', 'tags'] }],
			[900, { inlineActions: false, mime: false, size: true, mtime: true, columns: ['notes', 'tags'] }],
			[818, { inlineActions: false, mime: false, size: true, mtime: true, columns: ['notes', 'tags'] }],
			[817, { inlineActions: false, mime: false, size: true, mtime: true, columns: ['notes'] }],
			[700, { inlineActions: false, mime: false, size: true, mtime: true, columns: ['notes'] }],
			[680, { inlineActions: false, mime: false, size: true, mtime: true, columns: ['notes'] }],
			[679, { inlineActions: false, mime: false, size: true, mtime: true, columns: [] }],
			[542, { inlineActions: false, mime: false, size: true, mtime: true, columns: [] }],
			[541, { inlineActions: false, mime: false, size: true, mtime: true, columns: [] }],
			[0, { inlineActions: false, mime: false, size: true, mtime: true, columns: [] }],
		])('lays out the full row at %ipx', (width, expected) => {
			const row = available({
				inlineActions: true,
				mime: true,
				size: true,
				mtime: true,
				columns: [column('notes'), column('tags')],
			})
			expect(visible(getFileListLayout(width, row, 2 * INLINE_ACTION_WIDTH))).toEqual(expected)
		})

		it('hides every droppable part and keeps size and mtime when the list is narrower than the chrome', () => {
			const row = available({
				inlineActions: true,
				mime: true,
				size: true,
				mtime: true,
				columns: [column('notes'), column('tags'), column('owner')],
			})
			expect(visible(getFileListLayout(0, row, 500))).toEqual({
				inlineActions: false,
				mime: false,
				size: true,
				mtime: true,
				columns: [],
			})
			expect(visible(getFileListLayout(-50, row, 500))).toEqual({
				inlineActions: false,
				mime: false,
				size: true,
				mtime: true,
				columns: [],
			})
		})
	})

	describe('inline actions width', () => {
		it('starts at zero', () => {
			expect(useInlineActionsWidth().value).toBe(0)
		})

		it('keeps the largest width reported by a row', () => {
			reportInlineActionsWidth(58)
			reportInlineActionsWidth(116)
			reportInlineActionsWidth(30)
			expect(useInlineActionsWidth().value).toBe(116)
		})

		it('ignores a width equal to the current maximum', () => {
			reportInlineActionsWidth(80)
			reportInlineActionsWidth(80)
			expect(useInlineActionsWidth().value).toBe(80)
		})

		it('stores a smaller width after a reset', () => {
			reportInlineActionsWidth(200)
			resetInlineActionsWidth()
			reportInlineActionsWidth(58)
			expect(useInlineActionsWidth().value).toBe(58)
		})

		it('exposes a readonly width', () => {
			expect(isReadonly(useInlineActionsWidth())).toBe(true)
		})
	})
})

/**
 * Content width plus the margin on both sides of the cell.
 *
 * @param contentWidth - Column content width
 */
function cellWidth(contentWidth: number): number {
	return contentWidth + 2 * CELL_MARGIN
}

/**
 * Layout with every optional part hidden.
 *
 * @param overrides - Parts to turn on
 */
function available(overrides: Partial<FileListLayout> = {}): FileListLayout {
	return {
		inlineActions: false,
		mime: false,
		size: false,
		mtime: false,
		columns: [],
		...overrides,
	}
}

/**
 * @param id - Column id
 */
function column(id: string): IColumn {
	return {
		id,
		title: id,
		render: () => document.createElement('span'),
	}
}

/**
 * @param layout - Computed layout
 */
function columnIds(layout: FileListLayout): string[] {
	return layout.columns.map(({ id }) => id)
}

/**
 * @param layout - Computed layout
 */
function visible(layout: FileListLayout) {
	return {
		inlineActions: layout.inlineActions,
		mime: layout.mime,
		size: layout.size,
		mtime: layout.mtime,
		columns: columnIds(layout),
	}
}

/**
 * @param relativePath - Path relative to this spec
 */
function readVue(relativePath: string): string {
	const directory = dirname(fileURLToPath(import.meta.url))
	return readFileSync(resolve(directory, relativePath), 'utf8')
}

/**
 * List-mode stylesheet of the files list.
 */
function listStylesheet(): string {
	const source = readVue('../components/FilesListVirtual.vue')
	const start = source.indexOf('<style scoped lang="scss">')
	const end = source.indexOf('</style>')
	return source.slice(start, end)
}

/**
 * Static column classes rendered by a files-list row component.
 *
 * @param source - Vue single file component source
 */
function columnClasses(source: string): string[] {
	const withoutStyle = source.replace(/<style[\s\S]*?<\/style>/g, '')
	const classes = new Set<string>()
	for (const match of withoutStyle.matchAll(/files-list__row-[a-z][a-z0-9]*(?:-[a-z0-9]+)*/g)) {
		const cssClass = match[0]
		if (!STRUCTURAL_ROW_CLASSES.has(cssClass)) {
			classes.add(cssClass)
		}
	}
	return [...classes]
}

/**
 * Column content widths declared as a multiple of `--row-height`.
 *
 * @param style - List-mode stylesheet
 */
function stylesheetColumnMultipliers(style: string): Record<string, number> {
	const multipliers: Record<string, number> = {}
	for (const match of style.matchAll(/([^{}]+)\{([^{}]*)\}/g)) {
		const width = match[2].match(/width:\s*calc\(\s*var\(--row-height\)\s*\*\s*([0-9.]+)\s*\)/)
		if (!width) {
			continue
		}
		const classes = [...match[1].matchAll(/\.files-list__row-[a-z][a-z0-9]*(?:-[a-z0-9]+)*/g)]
			.map(([cssClass]) => cssClass.slice(1))
		expect(classes, 'a row-height column width needs a column class').not.toHaveLength(0)
		for (const cssClass of classes) {
			multipliers[cssClass] = Number(width[1])
		}
	}
	return multipliers
}
