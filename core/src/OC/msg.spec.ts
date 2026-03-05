/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { afterAll, describe, expect, it, test, vi } from 'vitest'
import msg from './msg.ts'

describe('start action', () => {
	it('sets the message text content', () => {
		const selector = '#msg-placeholder'
		document.body.innerHTML = '<div id="msg-placeholder"></div>'

		msg.startAction(selector, 'the message')

		const el = document.querySelector(selector)
		expect(el).not.toBeNull()
		expect(el!.textContent).toBe('the message')
	})

	it('removes old classes', () => {
		const selector = '#msg-placeholder'
		document.body.innerHTML = '<div id="msg-placeholder" class="success"></div>'

		const el = document.querySelector(selector)
		expect(el).not.toBeNull()
		expect(el!.classList.contains('success')).toBe(true)

		msg.startAction(selector, 'the message')
		expect(el!.classList.contains('success')).toBe(false)
	})

	it('sets element visible', () => {
		const selector = '#msg-placeholder'
		document.body.innerHTML = '<div id="msg-placeholder" style="display: none;"></div>'

		const el = document.querySelector(selector) as HTMLElement
		expect(el).not.toBeNull()
		expect(el.style.display).toBe('none')

		msg.startAction(selector, 'the message')
		expect(el.style.display).toBe('block')
	})
})

test('start saving message', () => {
	const selector = '#msg-placeholder'
	document.body.innerHTML = '<div id="msg-placeholder"></div>'

	msg.startSaving(selector)

	const el = document.querySelector(selector)
	expect(el).not.toBeNull()
	expect(el!.textContent).toBe('Saving …')
})

describe('finish with error', () => {
	it('sets the message text content', () => {
		const selector = '#msg-placeholder'
		document.body.innerHTML = '<div id="msg-placeholder"></div>'

		const el = document.querySelector(selector)
		msg.startSaving(selector)
		msg.finishedError(selector, 'error message')
		expect(el!.textContent).toBe('error message')
	})

	it('adds error class', () => {
		const selector = '#msg-placeholder'
		document.body.innerHTML = '<div id="msg-placeholder"></div>'

		const el = document.querySelector(selector)
		msg.startSaving(selector)
		msg.finishedError(selector, 'error message')
		expect(el!.classList.contains('error')).toBe(true)
	})

	it('removes old classes', () => {
		const selector = '#msg-placeholder'
		document.body.innerHTML = '<div id="msg-placeholder" class="success"></div>'

		const el = document.querySelector(selector)
		msg.startSaving(selector)
		msg.finishedError(selector, 'error message')
		expect(el!.classList.contains('success')).toBe(false)
	})

	it('sets element visible', () => {
		const selector = '#msg-placeholder'
		document.body.innerHTML = '<div id="msg-placeholder" style="display: none;"></div>'

		const el = document.querySelector(selector) as HTMLElement
		msg.startSaving(selector)
		msg.finishedError(selector, 'error message')
		expect(el.style.display).toBe('block')
	})
})

describe('finish with success', () => {
	afterAll(() => vi.useRealTimers())

	it('sets the message text content', () => {
		const selector = '#msg-placeholder'
		document.body.innerHTML = '<div id="msg-placeholder"></div>'

		const el = document.querySelector(selector)
		msg.startSaving(selector)
		msg.finishedSuccess(selector, 'success message')
		expect(el!.textContent).toBe('success message')
	})

	it('adds success class', () => {
		const selector = '#msg-placeholder'
		document.body.innerHTML = '<div id="msg-placeholder"></div>'

		const el = document.querySelector(selector)
		msg.startSaving(selector)
		msg.finishedSuccess(selector, 'success message')
		expect(el!.classList.contains('success')).toBe(true)
	})

	it('removes old classes', () => {
		const selector = '#msg-placeholder'
		document.body.innerHTML = '<div id="msg-placeholder" class="error"></div>'

		const el = document.querySelector(selector)
		msg.startSaving(selector)
		msg.finishedSuccess(selector, 'success message')
		expect(el!.classList.contains('error')).toBe(false)
	})

	it('sets element visible', () => {
		const selector = '#msg-placeholder'
		document.body.innerHTML = '<div id="msg-placeholder" style="display: none;"></div>'

		const el = document.querySelector(selector) as HTMLElement
		msg.startSaving(selector)
		msg.finishedSuccess(selector, 'success message')
		expect(el.style.display).toBe('block')
	})

	it('fades out element', () => {
		vi.useFakeTimers()

		const selector = '#msg-placeholder'
		document.body.innerHTML = '<div id="msg-placeholder"></div>'

		const el = document.querySelector(selector) as HTMLElement
		msg.startSaving(selector)
		msg.finishedSuccess(selector, 'success message')
		expect(el!.style.display).toBe('block')

		vi.advanceTimersByTime(3900)

		expect(el.style.display).toBe('none')
	})
})

describe('finished action', () => {
	it('calls finishedSuccess on success response', () => {
		const selector = '#msg-placeholder'
		document.body.innerHTML = '<div id="msg-placeholder"></div>'

		const finishedSuccessSpy = vi.spyOn(msg, 'finishedSuccess')
		const response = { data: { message: 'all good' }, status: 'success' }

		msg.finishedAction(selector, response)

		expect(finishedSuccessSpy).toHaveBeenCalledWith(selector, 'all good')
	})

	it('calls finishedError on error response', () => {
		const selector = '#msg-placeholder'
		document.body.innerHTML = '<div id="msg-placeholder"></div>'

		const finishedErrorSpy = vi.spyOn(msg, 'finishedError')
		const response = { data: { message: 'something went wrong' }, status: 'error' }

		msg.finishedAction(selector, response)

		expect(finishedErrorSpy).toHaveBeenCalledWith(selector, 'something went wrong')
	})
})

test('finished saving delegates to finished action', () => {
	const selector = '#msg-placeholder'
	document.body.innerHTML = '<div id="msg-placeholder"></div>'

	const finishedActionSpy = vi.spyOn(msg, 'finishedAction')
	const response = { data: { message: 'done saving' }, status: 'success' }

	msg.finishedSaving(selector, response)

	expect(finishedActionSpy).toHaveBeenCalledWith(selector, response)
})
