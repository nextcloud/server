/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { InjectionKey } from 'vue'
import type { FormData } from './userFormUtils.ts'

/**
 * Typed key for the reactive `formData` object that the user dialogs
 * (`NewUserDialog`, `EditUserDialog`) provide and the form sub-components
 * inject. Using an `InjectionKey` instead of a string makes a provide/inject
 * shape mismatch a compile error rather than a silent reactivity loss.
 */
export const formDataKey: InjectionKey<FormData> = Symbol('userFormData')
