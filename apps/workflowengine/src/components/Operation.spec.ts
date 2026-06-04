/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { cleanup, render } from '@testing-library/vue'
import { afterEach, describe, expect, it } from 'vitest'
import { nextTick } from 'vue'
import Operation from './Operation.vue'

afterEach(() => {
	cleanup()
})

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

		const icon = container.querySelector('.icon')
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

		const icon = container.querySelector<HTMLElement>('.icon')
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

		const item = container.querySelector('.actions__item')
		expect(item?.classList.contains('colored')).toBe(true)
	})

	it('does not apply colored class when colored prop is false', () => {
		const { container } = render(Operation, {
			props: {
				operation: mockOperation,
				colored: false,
			},
		})

		const item = container.querySelector('.actions__item')
		expect(item?.classList.contains('colored')).toBe(false)
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

	it('applies background color when colored is true and color is provided', async () => {
		const { container } = render(Operation, {
			props: {
				operation: {
					...mockOperation,
					color: '#ff0000',
				},
				colored: true,
			},
		})

		await nextTick()

		expect(getComponentStyles(container).backgroundColor).toBe('#ff0000')
	})

	it('updates styles when operation color changes', async () => {
		const { updateProps, container } = render(Operation, {
			props: {
				operation: mockOperation,
				colored: false,
			},
		})

		await nextTick()

		expect(getComponentStyles(container).backgroundColor).toBe('transparent')

		await updateProps({
			operation: { ...mockOperation, color: '#00ff00' },
			colored: true,
		})

		expect(getComponentStyles(container).backgroundColor).not.toBe('transparent')
	})

	describe('backgroundColor watcher', () => {
		it('sets text color to var(--color-main-text) when background is transparent', async () => {
			const { container } = render(Operation, {
				props: {
					operation: mockOperation,
					colored: false,
				},
			})

			await nextTick()

			expect(getComponentStyles(container).color).toBe('var(--color-main-text)')
		})

		it('sets text color to var(--color-primary-element-text) when background is var(--color-primary-element)', async () => {
			const { container } = render(Operation, {
				props: {
					operation: mockOperation,
					colored: true,
				},
			})

			await nextTick()

			expect(getComponentStyles(container).color).toBe('var(--color-primary-element-text)')
		})

		it('sets text color to white (#ffffff) for dark background colors (high contrast)', async () => {
			const { container } = render(Operation, {
				props: {
					operation: {
						...mockOperation,
						color: '#000000',
					},
					colored: true,
				},
			})

			await nextTick()

			expect(getComponentStyles(container).color).toBe('#ffffff')
		})

		it('sets text color to black (#000000) for light background colors (low contrast)', async () => {
			const { container } = render(Operation, {
				props: {
					operation: {
						...mockOperation,
						color: '#ffffff',
					},
					colored: true,
				},
			})

			await nextTick()

			expect(getComponentStyles(container).color).toBe('#000000')
		})

		it('calculates color based on contrast for gray backgrounds', async () => {
			const { container } = render(Operation, {
				props: {
					operation: {
						...mockOperation,
						color: '#808080',
					},
					colored: true,
				},
			})

			await nextTick()

			// Gray has contrast ratio < 4.5 with white, so should use black
			expect(getComponentStyles(container).color).toBe('#000000')
		})

		it('applies invert filter to icon when text color is black', async () => {
			const { container } = render(Operation, {
				props: {
					operation: {
						...mockOperation,
						color: '#ffffff',
					},
					colored: true,
				},
			})

			await nextTick()

			expect(getComponentStyles(container).filter).toBe('invert(100%)')
		})

		it('does not apply invert filter to icon when text color is not black', async () => {
			const { container } = render(Operation, {
				props: {
					operation: {
						...mockOperation,
						color: '#000000',
					},
					colored: true,
				},
			})

			await nextTick()

			expect(getComponentStyles(container).filter).toBe('none')
		})

		it('does not apply invert filter when using CSS variable colors', async () => {
			const { container } = render(Operation, {
				props: {
					operation: mockOperation,
					colored: true,
				},
			})

			await nextTick()

			expect(getComponentStyles(container).filter).toBe('none')
		})

		it('handles contrast calculation error gracefully', async () => {
			const { container } = render(Operation, {
				props: {
					operation: {
						...mockOperation,
						color: 'invalid-color',
					},
					colored: true,
				},
			})

			await nextTick()

			// Should fallback to var(--color-main-text) on error
			expect(getComponentStyles(container).color).toBe('var(--color-main-text)')
		})

		it('updates text color reactively when operation color changes', async () => {
			const { container, updateProps } = render(Operation, {
				props: {
					operation: {
						...mockOperation,
						color: '#ffffff',
					},
					colored: true,
				},
			})

			await nextTick()

			expect(getComponentStyles(container).color).toBe('#000000')

			await updateProps({
				operation: {
					...mockOperation,
					color: '#000000',
				},
				colored: true,
			})

			expect(getComponentStyles(container).color).toBe('#ffffff')
		})

		it('transitions from colored to uncolored updates text color', async () => {
			const { container, updateProps } = render(Operation, {
				props: {
					operation: {
						...mockOperation,
						color: '#000000',
					},
					colored: true,
				},
			})

			await nextTick()

			expect(getComponentStyles(container).color).toBe('#ffffff')

			await updateProps({
				operation: mockOperation,
				colored: false,
			})

			expect(getComponentStyles(container).color).toBe('var(--color-main-text)')
		})

		it('uses computed style for non-hex background colors', async () => {
			const { container } = render(Operation, {
				props: {
					operation: mockOperation,
					colored: true,
				},
			})

			await nextTick()

			// When colored=true and no color property, uses var(--color-primary-element)
			// which is a CSS variable, so it uses the computed style path
			expect(getComponentStyles(container).color).toBe('var(--color-primary-element-text)')
		})

		it('watcher runs with immediate:true on mount', async () => {
			const { container } = render(Operation, {
				props: {
					operation: {
						...mockOperation,
						color: '#000000',
					},
					colored: true,
				},
			})

			// The watcher should have already run with immediate: true
			// so color should be calculated even before nextTick
			expect(getComponentStyles(container).color).toBeTruthy()
		})
	})
})

/**
 * Get the computed styles of the component for testing purposes
 *
 * @param container - The container element
 */
function getComponentStyles(container: Element) {
	const element = container.querySelector<HTMLElement>('.actions__item')
	const styles = Object.values({ ...element!.style }) // variables are exposed as --HASH-VARNAME
	console.error(styles)
	const color = element?.style.getPropertyValue(styles.find((key) => (key as string).endsWith('-color'))!.toString())
	const backgroundColor = element?.style.getPropertyValue(styles.find((key) => (key as string).endsWith('-backgroundColor'))!.toString())
	const filter = element?.style.getPropertyValue(styles.find((key) => (key as string).endsWith('-iconFilter'))!.toString())
	return {
		color,
		backgroundColor,
		filter,
	}
}
