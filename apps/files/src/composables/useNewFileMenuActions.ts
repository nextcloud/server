/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { IFolder, INode, NewMenuEntry } from '@nextcloud/files'
import type { FilePickerItem, FilePickerItemGroup } from '@nextcloud/vue/components/NcUploadPicker'
import type { ComputedRef, MaybeRefOrGetter } from 'vue'

import { getNewFileMenuEntries, NewMenuEntryCategory } from '@nextcloud/files'
import { t } from '@nextcloud/l10n'
import { computed, toValue } from 'vue'

/**
 * Get the registered "new"-menu entries of a folder as actions for the upload picker,
 * grouped by the category of the entries.
 *
 * @param folder - The folder the new nodes are created in
 * @param contents - The current contents of that folder, passed to the entry handlers
 */
export function useNewFileMenuActions(
	folder: MaybeRefOrGetter<IFolder | undefined>,
	contents: MaybeRefOrGetter<INode[]>,
): ComputedRef<FilePickerItemGroup[]> {
	return computed(() => {
		const context = toValue(folder)
		if (context === undefined) {
			return []
		}

		const entries = getNewFileMenuEntries(context)

		/**
		 * Map a menu entry to a picker action.
		 * The folder contents are resolved on click to also pass nodes added after the menu was built.
		 *
		 * @param entry - The menu entry to map
		 */
		const toAction = (entry: NewMenuEntry): FilePickerItem => ({
			label: entry.displayName,
			iconSvg: entry.iconSvgInline ?? '',
			onClick: () => entry.handler(context, toValue(contents)),
		})

		return [
			{ caption: t('files', 'Upload from device'), category: NewMenuEntryCategory.UploadFromDevice },
			{ caption: t('files', 'Create new'), category: NewMenuEntryCategory.CreateNew },
			{ caption: t('files', 'Other'), category: NewMenuEntryCategory.Other },
		].map(({ caption, category }) => ({
			caption,
			actions: entries.filter((entry) => entry.category === category).map(toAction),
		})).filter(({ actions }) => actions.length > 0)
	})
}
