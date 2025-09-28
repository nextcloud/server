/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { createAppConfig } from '@nextcloud/vite-config'
import { join } from 'node:path'

export default createAppConfig({
	main: join(import.meta.dirname, 'src/main.ts'),
}, {
	emptyOutputDirectory: {
		additionalDirectories: ['css'],
	},
})
