/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { Locator } from '@playwright/test'

import { FilesListPage } from './FilesListPage.ts'

/**
 * Extends FilesListPage with accessors for the trashbin's custom columns.
 *
 * These columns are identified by product-owned data attributes because the
 * cells carry no accessible names — they are ordinary `<td>` elements inside
 * the virtual-scroll table.
 */
export class TrashbinListPage extends FilesListPage {
	/** The filename cell of a trashbin row. */
	fileNameCell(row: Locator): Locator {
		return row.locator('[data-cy-files-list-row-name]')
	}

	/** The "Original location" custom column cell of a trashbin row. */
	originalLocationCell(row: Locator): Locator {
		return row.locator('[data-cy-files-list-row-column-custom="files_trashbin--original-location"]')
	}

	/** The "Deleted by" custom column cell of a trashbin row. */
	deletedByCell(row: Locator): Locator {
		return row.locator('[data-cy-files-list-row-column-custom="files_trashbin--deleted-by"]')
	}

	/** The "Deleted at" custom column cell of a trashbin row. */
	deletedAtCell(row: Locator): Locator {
		return row.locator('[data-cy-files-list-row-column-custom="files_trashbin--deleted"]')
	}
}
