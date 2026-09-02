/*!
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { vi } from 'vitest'

// Shared manual mock for the Nextcloud event bus. Enable per spec with
// `vi.mock('@nextcloud/event-bus')`, then import the spies to assert on them.
// Call history is reset between tests by the global setup (vi.clearAllMocks()).
export const emit = vi.fn()
export const subscribe = vi.fn()
export const unsubscribe = vi.fn()
