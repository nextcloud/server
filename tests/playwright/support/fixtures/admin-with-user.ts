/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { mergeTests } from '@playwright/test'
import { test as adminTest } from './admin-session.ts'
import { test as randomUserTest } from './random-user.ts'

/**
 * Admin session combined with a freshly-created random `user` fixture.
 * The page is logged in as admin; the user is available via the `user` fixture.
 */
export const test = mergeTests(adminTest, randomUserTest)
