/*!
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { vi } from 'vitest'

// Shared manual mock for the viewer singleton. Enable per spec with
// `vi.mock('../../src/api_package/viewer.ts')`, then import `viewer` to assert.
export const viewer = {
	open: vi.fn(),
	openFolder: vi.fn(),
	compare: vi.fn(),
	goTo: vi.fn(),
	close: vi.fn(),
	setEditing: vi.fn(),
}

export function getViewer() {
	return viewer
}
