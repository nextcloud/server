/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import axios from '@nextcloud/axios'
import { generateOcsUrl } from '@nextcloud/router'

/**
 *
 * @param {string} targetPath Destination folder relative to the user root
 */
export async function getTemplates(targetPath) {
	const response = await axios.get(generateOcsUrl('apps/files/api/v1/templates'), { params: { targetPath } })
	return response.data.ocs.data
}

/**
 *
 * @param {number} fileId Template file ID
 * @param {string} targetPath Destination folder relative to the user root
 */
export async function getTemplateFields(fileId, targetPath) {
	const response = await axios.get(generateOcsUrl(`apps/files/api/v1/templates/fields/${fileId}`), { params: { targetPath } })
	return response.data.ocs.data
}

/**
 * Create a new file from a specified template
 *
 * @param {string} filePath The new file destination path
 * @param {string} templatePath The template source path
 * @param {string} templateType The template type e.g 'user'
 * @param {object} templateFields The template fields to fill in (if any)
 */
export async function createFromTemplate(filePath, templatePath, templateType, templateFields) {
	const response = await axios.post(generateOcsUrl('apps/files/api/v1/templates/create'), {
		filePath,
		templatePath,
		templateType,
		templateFields,
	})
	return response.data.ocs.data
}
