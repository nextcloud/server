/*!
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { ShareContext } from './PublicShareUtils.ts'
import type { ShareOptions } from '../ShareOptionsType.ts'
import { defaultShareOptions } from '../ShareOptionsType.ts'
import { setupData, createLinkShare } from './PublicShareUtils.ts'

describe('files_sharing: Before create checks', () => {

	let shareContext: ShareContext

	before(() => {
		// Setup data for the shared folder once before all tests
		cy.createRandomUser().then((randomUser) => {
			shareContext = {
				user: randomUser,
			}
		})
	})

	afterEach(() => {
		cy.runOccCommand('config:app:delete core shareapi_enable_link_password_by_default')
		cy.runOccCommand('config:app:delete core shareapi_enforce_links_password')
		cy.runOccCommand('config:app:delete core shareapi_default_expire_date')
		cy.runOccCommand('config:app:delete core shareapi_enforce_expire_date')
		cy.runOccCommand('config:app:delete core shareapi_expire_after_n_days')
	})

	const applyShareOptions = (options: ShareOptions = defaultShareOptions): void => {
		cy.runOccCommand(`config:app:set --value ${options.alwaysAskForPassword ? 'yes' : 'no'} core shareapi_enable_link_password_by_default`)
		cy.runOccCommand(`config:app:set --value ${options.enforcePassword ? 'yes' : 'no'} core shareapi_enforce_links_password`)
		cy.runOccCommand(`config:app:set --value ${options.enforceExpirationDate ? 'yes' : 'no'} core shareapi_enforce_expire_date`)
		cy.runOccCommand(`config:app:set --value ${options.defaultExpirationDateSet ? 'yes' : 'no'} core shareapi_default_expire_date`)
		if (options.defaultExpirationDateSet) {
			cy.runOccCommand('config:app:set --value 2 core shareapi_expire_after_n_days')
		}
	}

	it('Checks if user can create share when both password and expiration date are enforced', () => {
		const shareOptions : ShareOptions = {
			alwaysAskForPassword: true,
			enforcePassword: true,
			enforceExpirationDate: true,
			defaultExpirationDateSet: true,
		  }
		applyShareOptions(shareOptions)
		const shareName = 'passwordAndExpireEnforced'
		setupData(shareContext.user, shareName)
		createLinkShare(shareContext, shareName, shareOptions).then((shareUrl) => {
		  shareContext.url = shareUrl
		  cy.log(`Created share with URL: ${shareUrl}`)
		})
	  })

	  it('Checks if user can create share when password is enforced and expiration date has a default set', () => {
		const shareOptions : ShareOptions = {
			alwaysAskForPassword: true,
			enforcePassword: true,
			defaultExpirationDateSet: true,
		}
		applyShareOptions(shareOptions)
		const shareName = 'passwordEnforcedDefaultExpire'
		setupData(shareContext.user, shareName)
		createLinkShare(shareContext, shareName, shareOptions).then((shareUrl) => {
		  shareContext.url = shareUrl
		  cy.log(`Created share with URL: ${shareUrl}`)
		})
	  })

	  it('Checks if user can create share when password is optionally requested and expiration date is enforced', () => {
		const shareOptions : ShareOptions = {
		  alwaysAskForPassword: true,
		  defaultExpirationDateSet: true,
		  enforceExpirationDate: true,
		}
		applyShareOptions(shareOptions)
		const shareName = 'defaultPasswordExpireEnforced'
		setupData(shareContext.user, shareName)
		createLinkShare(shareContext, shareName, shareOptions).then((shareUrl) => {
		  shareContext.url = shareUrl
		  cy.log(`Created share with URL: ${shareUrl}`)
		})
	  })

	  it('Checks if user can create share when password is optionally requested and expiration date have defaults set', () => {
		const shareOptions : ShareOptions = {
			alwaysAskForPassword: true,
			defaultExpirationDateSet: true,
		  }
		  applyShareOptions(shareOptions)
		const shareName = 'defaultPasswordAndExpire'
		setupData(shareContext.user, shareName)
		createLinkShare(shareContext, shareName, shareOptions).then((shareUrl) => {
		  shareContext.url = shareUrl
		  cy.log(`Created share with URL: ${shareUrl}`)
		})
	  })

	  it('Checks if user can create share with password enforced and expiration date set but not enforced', () => {
		const shareOptions : ShareOptions = {
			alwaysAskForPassword: true,
			enforcePassword: true,
			defaultExpirationDateSet: true,
			enforceExpirationDate: false,
		}
		applyShareOptions(shareOptions)
		const shareName = 'passwordEnforcedExpireSetNotEnforced'
		setupData(shareContext.user, shareName)
		createLinkShare(shareContext, shareName, shareOptions).then((shareUrl) => {
		  shareContext.url = shareUrl
		  cy.log(`Created share with URL: ${shareUrl}`)
		})
	  })

	  it('Checks if user can create a share when both password and expiration date have default values but are both not enforced', () => {
		const shareOptions : ShareOptions = {
			alwaysAskForPassword: true,
			enforcePassword: false,
			defaultExpirationDateSet: true,
			enforceExpirationDate: false,
		  }
		applyShareOptions(shareOptions)
		const shareName = 'defaultPasswordAndExpirationNotEnforced'
		setupData(shareContext.user, shareName)
		createLinkShare(shareContext, shareName, shareOptions).then((shareUrl) => {
		  shareContext.url = shareUrl
		  cy.log(`Created share with URL: ${shareUrl}`)
		})
	  })

	  it('Checks if user can create share with password not enforced but expiration date enforced', () => {
		const shareOptions : ShareOptions = {
			alwaysAskForPassword: true,
			enforcePassword: false,
			defaultExpirationDateSet: true,
			enforceExpirationDate: true,
		  }
		applyShareOptions(shareOptions)
		const shareName = 'noPasswordExpireEnforced'
		setupData(shareContext.user, shareName)
		createLinkShare(shareContext, shareName, shareOptions).then((shareUrl) => {
		  shareContext.url = shareUrl
		  cy.log(`Created share with URL: ${shareUrl}`)
		})
	  })

	  it('Checks if user can create share with password not enforced and expiration date has a default set', () => {
		const shareOptions : ShareOptions = {
			alwaysAskForPassword: true,
			enforcePassword: false,
			defaultExpirationDateSet: true,
			enforceExpirationDate: false,
		}
		applyShareOptions(shareOptions)
		const shareName = 'defaultExpireNoPasswordEnforced'
		setupData(shareContext.user, shareName)
		createLinkShare(shareContext, shareName, shareOptions).then((shareUrl) => {
		  shareContext.url = shareUrl
		  cy.log(`Created share with URL: ${shareUrl}`)
		})
	  })

	  it('Checks if user can create share with expiration date set and password not enforced', () => {
		const shareOptions : ShareOptions = {
			alwaysAskForPassword: true,
			enforcePassword: false,
			defaultExpirationDateSet: true,
		  }
		  applyShareOptions(shareOptions)

		const shareName = 'noPasswordExpireDefault'
		setupData(shareContext.user, shareName)
		createLinkShare(shareContext, shareName, shareOptions).then((shareUrl) => {
		  shareContext.url = shareUrl
		  cy.log(`Created share with URL: ${shareUrl}`)
		})
	  })

	  it('Checks if user can create share with password not enforced, expiration date not enforced, and no defaults set', () => {
		applyShareOptions()
		const shareName = 'noPasswordNoExpireNoDefaults'
		setupData(shareContext.user, shareName)
		createLinkShare(shareContext, shareName, null).then((shareUrl) => {
		  shareContext.url = shareUrl
		  cy.log(`Created share with URL: ${shareUrl}`)
		})
	  })

})
