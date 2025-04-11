/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import type { Capabilities } from '../../apps/files/src/types'

export const getCapabilities = (): Capabilities => {
	return {
		files: {
			bigfilechunking: true,
			blacklisted_files: [],
			forbidden_filename_basenames: [],
			forbidden_filename_characters: [],
			forbidden_filename_extensions: [],
			forbidden_filenames: [],
			undelete: true,
			version_deletion: true,
			version_labeling: true,
			versioning: true,
		},
	}
}
