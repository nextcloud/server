/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { OCSResponse } from '@nextcloud/typings/ocs'

import axios from '@nextcloud/axios'
import { loadState } from '@nextcloud/initial-state'
import { generateOcsUrl } from '@nextcloud/router'
import { reactive } from 'vue'

interface TemplateDirectory {
	template_path: string
	available: boolean
}

const initialPath = loadState<string | false>('files', 'templates_path', false)
export const templateDirectory = reactive<TemplateDirectory>({
	template_path: initialPath || '',
	available: !!initialPath,
})

/** Load the configured folder, including folders that are no longer available. */
export async function loadTemplateDirectory(): Promise<void> {
	const { data } = await axios.get<OCSResponse<TemplateDirectory>>(generateOcsUrl('apps/files/api/v1/templates/path'))
	Object.assign(templateDirectory, data.ocs.data)
}

/**
 * Select an existing folder without creating or copying files.
 *
 * @param templatePath User-relative folder path, or empty to clear the selection
 */
export async function setTemplateDirectory(templatePath: string): Promise<void> {
	const { data } = await axios.put<OCSResponse<TemplateDirectory>>(generateOcsUrl('apps/files/api/v1/templates/path'), { templatePath })
	Object.assign(templateDirectory, data.ocs.data)
}
