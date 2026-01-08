/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: CC0-1.0
 */

import { defineConfig } from 'vitest/config'

// stub - for the moment see build/frontend/vitest.config.ts
export default defineConfig({
	test: {
		projects: ['build/frontend*'],
	},
})
