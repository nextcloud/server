/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { resolve } from 'node:path'
import { runOcc } from '@nextcloud/e2e-test-server/docker'
import { createRandomUser, login } from '@nextcloud/e2e-test-server/playwright'
import { expect, test } from '@playwright/test'

const themesToTest = ['light', 'dark', 'light-highcontrast', 'dark-highcontrast']

const testCases = {
	'Main text': {
		foregroundColors: ['color-main-text', 'color-text-maxcontrast'],
		backgroundColors: ['color-main-background', 'color-background-hover', 'color-background-dark'],
	},
	'blurred background': {
		foregroundColors: ['color-main-text', 'color-text-maxcontrast-blur'],
		backgroundColors: ['color-main-background-blur'],
	},
	Primary: {
		foregroundColors: ['color-primary-text'],
		backgroundColors: ['color-primary'],
	},
	'Primary light': {
		foregroundColors: ['color-primary-light-text'],
		backgroundColors: ['color-primary-light', 'color-primary-light-hover'],
	},
	'Primary element': {
		foregroundColors: ['color-primary-element-text', 'color-primary-element-text-dark'],
		backgroundColors: ['color-primary-element', 'color-primary-element-hover'],
	},
	'Primary element light': {
		foregroundColors: ['color-primary-element-light-text'],
		backgroundColors: ['color-primary-element-light', 'color-primary-element-light-hover'],
	},
	'Severity information texts': {
		foregroundColors: ['color-error-text', 'color-warning-text', 'color-success-text', 'color-info-text'],
		backgroundColors: ['color-main-background', 'color-background-hover'],
	},
	'Severity information on blur': {
		foregroundColors: ['color-error-text', 'color-success-text'],
		backgroundColors: ['color-main-background-blur'],
	},
}

for (const theme of themesToTest) {
	test(`Accessibility of Nextcloud theming colors: ${theme}`, async ({ page, context }) => {
		const user = await createRandomUser()
		const failures: string[] = []

		try {
			await runOcc(['user:setting', '--', user.userId, 'theming', 'enabled-themes', `["${theme}"]`])
			await login(context.request, user)
			await page.goto('')

			await page.addScriptTag({ path: resolve(process.cwd(), 'node_modules/axe-core/axe.min.js') })

			for (const [groupName, { foregroundColors, backgroundColors }] of Object.entries(testCases)) {
				for (const foreground of foregroundColors) {
					for (const background of backgroundColors) {
						await page.evaluate(({ foregroundValue, backgroundValue }) => {
							document.body.style.backgroundImage = 'unset'
							const root = document.querySelector('#content')
							if (!root) {
								throw new Error('No test root found')
							}

							root.innerHTML = ''

							const wrapper = document.createElement('div')
							wrapper.style.padding = '14px'
							wrapper.style.color = `var(--${foregroundValue})`
							wrapper.style.backgroundColor = `var(--${backgroundValue})`
							if (backgroundValue.includes('blur')) {
								wrapper.style.backdropFilter = 'var(--filter-background-blur)'
							}

							const testCase = document.createElement('div')
							testCase.innerText = `${foregroundValue} ${backgroundValue}`
							testCase.setAttribute('data-cy-testcase', '')

							wrapper.append(testCase)
							root.append(wrapper)
						}, {
							foregroundValue: foreground,
							backgroundValue: background,
						})

						const axeResult = await page.evaluate(async () => {
							const axe = (window as any).axe
							if (!axe) {
								throw new Error('axe is not loaded')
							}

							return axe.run('[data-cy-testcase]', {
								runOnly: {
									type: 'rule',
									values: ['color-contrast'],
								},
							})
						})

						if (axeResult.violations.length > 0) {
							failures.push(`${groupName}: ${foreground} on ${background}`)
						}
					}
				}
			}
		} finally {
			await runOcc(['user:delete', user.userId])
		}

		expect(failures).toEqual([])
	})
}
