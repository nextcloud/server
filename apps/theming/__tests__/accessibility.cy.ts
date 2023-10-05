// eslint-disable-next-line import/no-webpack-loader-syntax, import/no-unresolved
import style from '!raw-loader!../css/default.css'

const testCases = {
	'Generic combinations': {
		foregroundColors: [
			'color-main-text',
			// 'color-text-light', deprecated
			// 'color-text-lighter', deprecated
			'color-text-maxcontrast',
			'color-text-maxcontrast-default',
			'color-error-text',
			'color-warning-text',
			'color-success-text',
			'color-info-text',
		],
		backgroundColors: [
			'color-background-main',
			'color-background-hover',
			'color-background-dark',
			'color-background-darker',
		],
	},
	Primary: {
		foregroundColors: [
			'color-primary-text',
		],
		backgroundColors: [
			'color-primary-default',
			'color-primary',
			'color-primary-hover',
		],
	},
	'Primary light': {
		foregroundColors: [
			'color-primary-light-text',
		],
		backgroundColors: [
			'color-primary-light',
			'color-primary-light-hover',
		],
	},
	'Primary element': {
		foregroundColors: [
			'color-primary-element-text',
			'color-primary-element-text-dark',
		],
		backgroundColors: [
			'color-primary-element',
			'color-primary-element-hover',
		],
	},
	'Primary element light': {
		foregroundColors: [
			'color-primary-element-light-text',
		],
		backgroundColors: [
			'color-primary-element-light',
			'color-primary-element-light-hover',
		],
	},
}

before(() => {
	cy.injectAxe()

	const el = document.createElement('style')
	el.innerText = style
	document.head.appendChild(el)
})

/**
 * Create a wrapper element with color and background set
 *
 * @param foreground The foreground color (css variable without leading --)
 * @param background The background color
 */
function createTestCase(foreground: string, background: string) {
	const wrapper = document.createElement('div')
	wrapper.innerText = `${foreground} ${background}`
	wrapper.style.color = `var(--${foreground})`
	wrapper.style.backgroundColor = `var(--${background})`
	wrapper.style.padding = '4px'
	wrapper.setAttribute('data-cy-testcase', '')
	return wrapper
}

for (const [name, { backgroundColors, foregroundColors }] of Object.entries(testCases)) {
	describe(`Accessibility of CSS color variables for ${name}`, () => {
		afterEach(() => {
			cy.document().then(doc => {
				const root = doc.querySelector('[data-cy-root]')
				if (root === null) {
					throw new Error('No test root found')
				}
				for (const child of root.children) {
					root.removeChild(child)
				}
			})
		})

		for (const foreground of foregroundColors) {
			for (const background of backgroundColors) {
				it(`color contrast of ${foreground} on ${background}`, () => {
					const element = createTestCase(foreground, background)
					cy.document().then(doc => {
						const root = doc.querySelector('[data-cy-root]')
						console.warn(root)
						root?.appendChild(element)
						cy.checkA11y('[data-cy-testcase]')
					})
				})
			}
		}
	})
}
