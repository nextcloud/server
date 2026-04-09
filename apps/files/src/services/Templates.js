/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import axios from '@nextcloud/axios'
import { generateOcsUrl } from '@nextcloud/router'

/**
 *
 */
export async function getTemplates() {
	const response = await axios.get(generateOcsUrl('apps/files/api/v1/templates'))
	return response.data.ocs.data
}

/**
 *
 * @param fileId
 */
export async function getTemplateFields(fileId) {
	const response = await axios.get(generateOcsUrl(`apps/files/api/v1/templates/fields/${fileId}`))
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
