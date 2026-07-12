/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * [login-diag] THROWAWAY reproduction spec for the flaky session-validation 401
 * seen in version_deletion.cy.ts ("Delete versions of shared file with delete
 * permission"). It hammers the exact failing path — a freshly created user
 * logging in (fresh cy.session setup + validate) — many times against a single
 * CPU-throttled container, so we can catch the intermittent
 * `GET /apps/files -> 401` and correlate it with the [login-diag] server log.
 *
 * Run: E2E_SERVER_CPUS=0.2 npx cypress run --e2e \
 *        --spec cypress/e2e/files_versions/login_flake_repro.cy.ts
 */

import { randomString } from '../../support/utils/randomString.ts'
import { setupTestSharedFileFromUser, uploadThreeVersions } from './filesVersionsUtils.ts'

describe('[login-diag] login flake repro', () => {
	const folderName = 'shared_folder'

	for (let i = 0; i < 25; i++) {
		it(`fresh-user login+share cycle ${i}`, () => {
			// Mirror version_deletion's beforeEach + setupTestSharedFileFromUser:
			// two fresh users each doing a fresh-session login, with some DAV
			// load in between (uploads + a share), all under CPU starvation.
			cy.createRandomUser().then((user) => {
				const randomFilePath = `/${folderName}/${randomString(10)}.txt`
				cy.mkdir(user, `/${folderName}`)
				uploadThreeVersions(user, randomFilePath)
				cy.login(user)
				cy.visit('/apps/files')
				// This inner call logs in a *freshly created recipient* — the
				// login that failed on CI.
				setupTestSharedFileFromUser(user, folderName, { delete: true })
			})
		})
	}
})
