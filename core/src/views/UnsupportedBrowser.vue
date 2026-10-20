<!--
  - SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { mdiWeb } from '@mdi/js'
import { t } from '@nextcloud/l10n'
import { generateUrl, getRootUrl } from '@nextcloud/router'
import { agents } from 'caniuse-lite/dist/unpacker/agents.js'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcEmptyContent from '@nextcloud/vue/components/NcEmptyContent'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import { supportedBrowsers } from '../services/BrowsersListService.ts'
import browserStorage from '../services/BrowserStorageService.ts'
import { logger } from '../utils/logger.ts'
import { browserStorageKey } from '../utils/RedirectUnsupportedBrowsers.js'

logger.debug('Supported browsers', { supportedBrowsers })

const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent)

// Lowest supported version per browser, restricted to the kind of device in use
const lowestVersions = new Map<string, number>()
for (const browser of supportedBrowsers) {
	if (!browser || isMobileBrowser(browser) !== isMobile) {
		continue
	}
	const [id, version] = browser.split(' ') as [string, string]
	const lowest = lowestVersions.get(id)
	if (lowest === undefined || lowest > parseFloat(version)) {
		lowestVersions.set(id, parseFloat(version))
	}
}

const formattedBrowsersList = [...lowestVersions]
	.filter(([id]) => agents[id]?.browser)
	.map(([id, version]) => t('core', '{name} version {version} and above', { name: agents[id]!.browser, version }))

/**
 * Remember to allow this browser and continue to the page the user was
 * redirected from, or to the start page.
 */
function forceBrowsing() {
	browserStorage.setItem(browserStorageKey, 'true')

	const redirectUrl = new URLSearchParams(window.location.search).get('redirect_url')
	if (redirectUrl) {
		const redirectPath = atob(redirectUrl)
			.replace('index.php', '')
			.replace(getRootUrl(), '')
			.replace(/\/\//g, '/')

		if (redirectPath.startsWith('/')) {
			window.location.href = generateUrl(redirectPath)
			return
		}
	}

	window.location.href = generateUrl('/')
}

/**
 * Detect if the browserslist browser is a mobile one
 *
 * @see https://github.com/browserslist/browserslist#query-composition
 * @param browser - A browserslist browser, e.g. `and_chr 90`
 */
function isMobileBrowser(browser: string): boolean {
	browser = browser.toLowerCase()
	return browser.includes('and_')
		|| browser.includes('android')
		|| browser.includes('ios_')
		|| browser.includes('mobile')
		|| browser.includes('_mob')
		|| browser.includes('samsung')
}
</script>

<template>
	<div class="guest-box" :class="$style.unsupportedBrowser">
		<NcEmptyContent :name="t('core', 'This browser is not supported')">
			<template #icon>
				<NcIconSvgWrapper :path="mdiWeb" />
			</template>
			<template #action>
				<div>
					<h2>
						{{ t('core', 'Your browser is not supported. Please upgrade to a newer version or a supported one.') }}
					</h2>
					<NcButton :class="$style.unsupportedBrowser__continue" variant="primary" @click="forceBrowsing">
						{{ t('core', 'Continue with this unsupported browser') }}
					</NcButton>
				</div>

				<div :class="$style.unsupportedBrowser__list">
					<h3>{{ t('core', 'Supported versions') }}</h3>
					<ul>
						<li v-for="browser in formattedBrowsersList" :key="browser">
							{{ browser }}
						</li>
					</ul>
				</div>
			</template>
		</NcEmptyContent>
	</div>
</template>

<style module lang="scss">
$spacing: 30px;

.unsupportedBrowser {
	display: flex;
	justify-content: center;
	width: 400px;
	max-width: calc(90vw - 2 * $spacing);
	margin: auto;
	padding: $spacing;

	:global(.empty-content) {
		margin: 0;
	}

	:global(.empty-content__icon) {
		opacity: 1;
	}

	&__continue {
		display: block;
		margin: $spacing auto;
	}

	&__list {
		margin-top: 2 * $spacing;
		margin-bottom: $spacing;

		li {
			text-align: start;
		}
	}
}
</style>
