/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { cleanup, render } from '@testing-library/vue'
import { afterEach, describe, expect, it } from 'vitest'
import Operation from './Operation.vue'

afterEach(() => {
	cleanup()
})

/**
 * CSS modules rename every class, keeping the authored name as a part of it.
 *
 * @param scope - Where to look
 * @param name - The authored class name
 */
function byModuleClass<T extends HTMLElement>(scope: Element, name: string): T | null {
	return scope.querySelector<T>(`[class*="${name}"]`)
}

/**
 * @param element - The element to check
 * @param name - The authored class name
 */
function hasModuleClass(element: Element | null, name: string): boolean {
	return [...(element?.classList ?? [])].some((item) => new RegExp(`(^|_)${name}(_|$)`).test(item))
}

describe('Operation.vue', () => {
	const mockOperation = {
		name: 'Test Operation',
		description: 'This is a test operation',
		iconClass: 'icon-test',
		icon: '',
	}

	it('renders operation with required props', () => {
		const { getByRole } = render(Operation, {
			props: {
				operation: mockOperation,
			},
		})

		expect(getByRole('heading', { level: 3 })).toBeTruthy()
		expect(getByRole('heading', { level: 3 }).textContent).toBe('Test Operation')
	})

	it('displays operation name and description', () => {
		const { getByText } = render(Operation, {
			props: {
				operation: mockOperation,
			},
		})

		expect(getByText('Test Operation')).toBeTruthy()
		expect(getByText('This is a test operation')).toBeTruthy()
	})

	it('renders icon with iconClass', () => {
		const { container } = render(Operation, {
			props: {
				operation: mockOperation,
			},
		})

		const icon = byModuleClass(container, 'icon')
		expect(icon).toBeTruthy()
		expect(icon?.classList.contains('icon-test')).toBe(true)
	})

	it('renders icon with background image when no iconClass', () => {
		const { container } = render(Operation, {
			props: {
				operation: {
					name: 'Test Operation',
					description: 'Description',
					iconClass: '',
					icon: 'data:image/svg+xml;base64,test',
				},
			},
		})

		const icon = byModuleClass<HTMLElement>(container, 'icon')
		expect(icon).toBeTruthy()
		expect(icon?.style.backgroundImage).toContain('data:image/svg+xml;base64,test')
	})

	it('does not show button when colored is false', () => {
		const { queryByRole } = render(Operation, {
			props: {
				operation: mockOperation,
				colored: false,
			},
		})

		expect(queryByRole('button')).toBeFalsy()
	})

	it('shows button with correct text when colored is true', () => {
		const { getByRole } = render(Operation, {
			props: {
				operation: mockOperation,
				colored: true,
			},
		})

		const button = getByRole('button')
		expect(button).toBeTruthy()
		expect(button.textContent).toContain('Add new flow')
	})

	it('applies colored class when colored prop is true', () => {
		const { container } = render(Operation, {
			props: {
				operation: mockOperation,
				colored: true,
			},
		})

		expect(hasModuleClass(container.firstElementChild, 'colored')).toBe(true)
	})

	it('does not apply colored class when colored prop is false', () => {
		const { container } = render(Operation, {
			props: {
				operation: mockOperation,
				colored: false,
			},
		})

		expect(hasModuleClass(container.firstElementChild, 'colored')).toBe(false)
	})

	it('renders slot content', () => {
		const { getByText } = render(Operation, {
			props: {
				operation: mockOperation,
			},
			slots: {
				default: '<div>Slot content</div>',
			},
		})

		expect(getByText('Slot content')).toBeTruthy()
	})
})
