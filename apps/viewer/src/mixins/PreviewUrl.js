/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { getPreviewIfAny } from '../utils/previewUtils.ts'
import { getDavPath } from '../utils/fileUtils.ts'

export default {
	computed: {
		/**
		 * Link to the preview path if the file have a preview
		 *
		 * @return {string}
		 */
		previewPath() {
			return this.getPreviewIfAny({
				fileid: this.fileid,
				filename: this.filename,
				previewUrl: this.previewUrl,
				hasPreview: this.hasPreview,
				davPath: this.davPath,
				etag: this.$attrs.etag,
			})
		},

		/**
		 * Absolute dav remote path of the file
		 *
		 * @return {string}
		 */
		davPath() {
			return getDavPath({
				filename: this.filename,
				basename: this.basename,
			})
		},
	},
	methods: {
		/**
		 * Return the preview url if the file have an existing
		 * preview or the absolute dav remote path if none.
		 *
		 * @param {object} data destructuring object
		 * @param {string} data.fileid the file id
		 * @param {string} [data.previewUrl] URL of the file preview
		 * @param {boolean} data.hasPreview have the file an existing preview ?
		 * @param {string} data.davPath the absolute dav path
		 * @param {string} data.filename the file name
		 * @param {string|null} data.etag the etag of the file
		 * @return {string} the absolute url
		 */
		getPreviewIfAny(data) {
			return getPreviewIfAny(data)
		},
	},
}
