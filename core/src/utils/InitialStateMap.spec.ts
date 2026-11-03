/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { loadState } from '@nextcloud/initial-state'
import { afterEach, describe, expect, it, vi } from 'vitest'
import { InitialStateMap } from './InitialStateMap.ts'

const states = {
	core: {
		config: { version: '36.0.0' },
		'min-search-length': 1,
	},
	'unified-search': {
		'live-search': true,
	},
	files: {
		html: '</script><!--',
	},
}

/**
 * Render the initial states like the server does.
 *
 * @param content - The JSON document
 */
function renderInitialState(content = JSON.stringify(states)): void {
	const element = document.createElement('script')
	element.type = 'application/json'
	element.id = 'initial-state'
	element.textContent = content
	document.body.appendChild(element)
}

afterEach(() => {
	document.getElementById('initial-state')?.remove()
	delete window._nc_initial_state
	vi.restoreAllMocks()
})

describe('InitialStateMap', () => {
	it('parses the initial states on first access', () => {
		const map = new InitialStateMap()
		renderInitialState()

		expect(map.getState('core', 'config')).toEqual({ version: '36.0.0' })
		expect(map.getState('unified-search', 'live-search')).toBe(true)
		expect(map.getState('files', 'html')).toBe('</script><!--')
	})

	it('parses the initial states only once', () => {
		renderInitialState()
		const parse = vi.spyOn(JSON, 'parse')
		const map = new InitialStateMap()

		map.getState('core', 'config')
		map.has('#initial-state-core-config')
		map.get('#initial-state-files-html')

		expect(parse).toHaveBeenCalledOnce()
	})

	it('checks for provided initial states', () => {
		renderInitialState()
		const map = new InitialStateMap()

		expect(map.hasState('core', 'config')).toBe(true)
		expect(map.hasState('core', 'missing')).toBe(false)
		expect(map.hasState('missing', 'config')).toBe(false)
		expect(map.hasState('core', 'constructor')).toBe(false)
		expect(map.hasState('constructor', 'name')).toBe(false)
		expect(map.getState('core', 'missing')).toBeUndefined()
	})

	it('provides the initial states by their legacy keys', () => {
		renderInitialState()
		const map = new InitialStateMap()

		expect(map.has('#initial-state-core-config')).toBe(true)
		expect(map.get('#initial-state-core-config')).toBe(map.getState('core', 'config'))
		expect(map.get('#initial-state-unified-search-live-search')).toBe(true)
		expect(map.has('#initial-state-core-missing')).toBe(false)
	})

	it('keeps values cached before the first access', () => {
		renderInitialState()
		const map = new InitialStateMap([['#initial-state-core-config', 'cached'], ['#initial-state-other-key', 1]])

		expect(map.get('#initial-state-core-config')).toBe('cached')
		expect(map.get('#initial-state-other-key')).toBe(1)
		expect(map.getState('core', 'config')).toEqual({ version: '36.0.0' })
	})

	it('is empty without rendered initial states', () => {
		const map = new InitialStateMap()

		expect(map.hasState('core', 'config')).toBe(false)
		expect(map.has('#initial-state-core-config')).toBe(false)
	})

	it('reports invalid initial states and stays empty', () => {
		renderInitialState('{"core":')
		const reportError = vi.fn()
		vi.stubGlobal('reportError', reportError)
		const map = new InitialStateMap()

		expect(map.has('#initial-state-core-config')).toBe(false)
		expect(map.hasState('core', 'config')).toBe(false)
		expect(reportError).toHaveBeenCalledOnce()
		vi.unstubAllGlobals()
	})
})

describe('@nextcloud/initial-state 3.x compatibility', () => {
	it('loads initial states', () => {
		renderInitialState()
		window._nc_initial_state = new InitialStateMap()

		expect(loadState('core', 'config')).toEqual({ version: '36.0.0' })
		expect(loadState('unified-search', 'live-search')).toBe(true)
	})

	it('uses the fallback for missing initial states', () => {
		renderInitialState()
		window._nc_initial_state = new InitialStateMap()

		expect(loadState('core', 'missing', 'fallback')).toBe('fallback')
		expect(() => loadState('core', 'missing')).toThrow()
	})
})
