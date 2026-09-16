/*!
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

// Needed to merge multiple Playwright reports
// when they are ran on self-hosted and github runners (different test directories are used)
export default {
	testDir: 'tests/playwright/e2e',
	reporter: [['html', { open: 'never' }]],
}
