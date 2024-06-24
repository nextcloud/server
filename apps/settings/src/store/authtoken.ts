/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { showError } from '@nextcloud/dialogs'
import { loadState } from '@nextcloud/initial-state'
import { translate as t } from '@nextcloud/l10n'
import { confirmPassword } from '@nextcloud/password-confirmation'
import { generateUrl } from '@nextcloud/router'
import { defineStore } from 'pinia'

import axios from '@nextcloud/axios'
import logger from '../logger'

import '@nextcloud/password-confirmation/dist/style.css'

const BASE_URL = generateUrl('/settings/personal/authtokens')

const confirm = () => {
	return new Promise(resolve => {
		window.OC.dialogs.confirm(
			t('settings', 'Do you really want to wipe your data from this device?'),
			t('settings', 'Confirm wipe'),
			resolve,
			true,
		)
	})
}

export enum TokenType {
	TEMPORARY_TOKEN = 0,
	PERMANENT_TOKEN = 1,
	WIPING_TOKEN = 2,
}

export interface IToken {
	id: number
	canDelete: boolean
	canRename: boolean
	current?: true
	/**
	 * Last activity as UNIX timestamp (in seconds)
	 */
	lastActivity: number
	name: string
	type: TokenType
	scope: Record<string, boolean>
}

export interface ITokenResponse {
	/**
	 * The device token created
	 */
	deviceToken: IToken
	/**
	 * User who is assigned with this token
	 */
	loginName: string
	/**
	 * The token for authentication
	 */
	token: string
}

export const useAuthTokenStore = defineStore('auth-token', {
	state() {
		return {
			tokens: loadState<IToken[]>('settings', 'app_tokens', []),
		}
	},
	actions: {
		/**
		 * Update a token on server
		 * @param token Token to update
		 */
		async updateToken(token: IToken) {
			const { data } = await axios.put(`${BASE_URL}/${token.id}`, token)
			return data
		},

		/**
		 * Add a new token
		 * @param name The token name
		 */
		async addToken(name: string) {
			logger.debug('Creating a new app token')

			try {
				await confirmPassword()

				const { data } = await axios.post<ITokenResponse>(BASE_URL, { name })
				this.tokens.push(data.deviceToken)
				logger.debug('App token created')
				return data
			} catch (error) {
				return null
			}
		},

		/**
		 * Delete a given app token
		 * @param token Token to delete
		 */
		async deleteToken(token: IToken) {
			logger.debug('Deleting app token', { token })

			this.tokens = this.tokens.filter(({ id }) => id !== token.id)

			try {
				await axios.delete(`${BASE_URL}/${token.id}`)
				logger.debug('App token deleted')
				return true
			} catch (error) {
				logger.error('Could not delete app token', { error })
				showError(t('settings', 'Could not delete the app token'))
				// Restore
				this.tokens.push(token)
			}
			return false
		},

		/**
		 * Wipe a token and the connected device
		 * @param token Token to wipe
		 */
		async wipeToken(token: IToken) {
			logger.debug('Wiping app token', { token })

			try {
				await confirmPassword()

				if (!(await confirm())) {
					logger.debug('Wipe aborted by user')
					return
				}

				await axios.post(`${BASE_URL}/wipe/${token.id}`)
				logger.debug('App token marked for wipe', { token })

				token.type = TokenType.WIPING_TOKEN
				token.canRename = false // wipe tokens can not be renamed
				return true
			} catch (error) {
				logger.error('Could not wipe app token', { error })
				showError(t('settings', 'Error while wiping the device with the token'))
			}
			return false
		},

		/**
		 * Rename an existing token
		 * @param token The token to rename
		 * @param newName The new name to set
		 */
		async renameToken(token: IToken, newName: string) {
			logger.debug(`renaming app token ${token.id} from ${token.name} to '${newName}'`)

			const oldName = token.name
			token.name = newName

			try {
				await this.updateToken(token)
				logger.debug('App token name updated')
				return true
			} catch (error) {
				logger.error('Could not update app token name', { error })
				showError(t('settings', 'Error while updating device token name'))
				// Restore
				token.name = oldName
			}
			return false
		},

		/**
		 * Set scope of the token
		 * @param token Token to set scope
		 * @param scope scope to set
		 * @param value value to set
		 */
		async setTokenScope(token: IToken, scope: string, value: boolean) {
			logger.debug('Updating app token scope', { token, scope, value })

			const oldVal = token.scope[scope]
			token.scope[scope] = value

			try {
				await this.updateToken(token)
				logger.debug('app token scope updated')
				return true
			} catch (error) {
				logger.error('could not update app token scope', { error })
				showError(t('settings', 'Error while updating device token scope'))
				// Restore
				token.scope[scope] = oldVal
			}
			return false
		},
	},

})
