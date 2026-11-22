/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { FilePickerDialogPage } from './FilePickerDialogPage.ts'

/**
 * The file-picker dialog opened by the "Custom background" card/button on
 * Personal settings > Appearance and accessibility > Background and color
 */
export class BackgroundFilePickerDialogPage extends FilePickerDialogPage {
	/** Confirm the current selection as the new background. */
	override async confirm(): Promise<void> {
		await super.confirm('Select background')
	}
}
