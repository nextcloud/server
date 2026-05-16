/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { getDavPath } from '../utils/fileUtils.ts'

/**
 * @param {object} fileInfo a FileInfo object
 * @param {string} mime the file mime type
 * @param {object} component the component to render
 */
export default function(fileInfo, mime, component) {
	const data = {
		mime,
		modal: component,
		failed: false,
		loaded: false,
		davPath: getDavPath(fileInfo),
		source: fileInfo.source ?? getDavPath(fileInfo),
	}

	return Object.assign({}, fileInfo, data)
}
