/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { InjectionKey } from 'vue'
import type { FormData } from './userFormUtils.ts'

// Typed key so a provide/inject mismatch is a compile error, not a silent reactivity loss.
export const formDataKey: InjectionKey<FormData> = Symbol('userFormData')
