/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { createApp } from 'vue'
import WebInstaller from './views/WebInstaller.vue'
import { mountInPlace } from './utils/mountInPlace.ts'

// The placeholder carries the layout of an app content area, which would clip the form
mountInPlace(createApp(WebInstaller), document.getElementById('content')!)
