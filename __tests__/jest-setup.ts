/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import '@testing-library/jest-dom'

// Mock `window.location` with Jest spies and extend expect
import 'jest-location-mock'

// Mock `window.fetch` with Jest
import 'jest-fetch-mock'
