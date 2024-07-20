/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { generateOcsUrl } from '@nextcloud/router'
import axios from '@nextcloud/axios'

export const getTemplates = async function() {
	const response = await axios.get(generateOcsUrl('apps/files/api/v1/templates'))
	return response.data.ocs.data
}

/**
 * Create a new file from a specified template
 *
 * @param {string} filePath The new file destination path
 * @param {string} templatePath The template source path
 * @param {string} templateType The template type e.g 'user'
 */
export const createFromTemplate = async function(filePath, templatePath, templateType) {
	const response = await axios.post(generateOcsUrl('apps/files/api/v1/templates/create'), {
		filePath,
		templatePath,
		templateType,
	})
	return response.data.ocs.data
}
