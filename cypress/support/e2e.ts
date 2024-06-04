/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import 'cypress-axe'
import './commands.ts'

// Fix ResizeObserver loop limit exceeded happening in Cypress only
// @see https://github.com/cypress-io/cypress/issues/20341
Cypress.on('uncaught:exception', err => !err.message.includes('ResizeObserver loop limit exceeded'))
Cypress.on('uncaught:exception', err => !err.message.includes('ResizeObserver loop completed with undelivered notifications'))
