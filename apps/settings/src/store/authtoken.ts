import axios from '@nextcloud/axios'
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { showError, showSuccess } from '@nextcloud/dialogs'
import { loadState } from '@nextcloud/initial-state'
import { translatePlural as n, translate as t } from '@nextcloud/l10n'
import { addPasswordConfirmationInterceptors, confirmPassword, PwdConfirmationMode } from '@nextcloud/password-confirmation'
import { generateUrl } from '@nextcloud/router'
import { defineStore } from 'pinia'
import logger from '../logger.ts'

const BASE_URL = generateUrl('/settings/personal/authtokens')
addPasswordConfirmationInterceptors(axios)

export enum TokenType {
	TEMPORARY_TOKEN = 0,
	PERMANENT_TOKEN = 1,
	WIPING_TOKEN = 2,
	ONETIME_TOKEN = 3,
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

export interface IRevokeAllResponse {
	revoked: number[]
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
	getters: {
		/**
		 * Must stay in step with `destroyOthers()` server side, or the confirmation
		 * count disagrees with what actually gets revoked.
		 *
		 * @param state Current store state
		 */
		revocableCount(state): number {
			return state.tokens.filter((token) => !token.current && token.type !== TokenType.WIPING_TOKEN).length
		},

		/**
		 * Left alone by a bulk revoke, because cancelling a pending wipe must stay deliberate.
		 *
		 * @param state Current store state
		 */
		wipePendingCount(state): number {
			return state.tokens.filter((token) => !token.current && token.type === TokenType.WIPING_TOKEN).length
		},
	},
	actions: {
		/**
		 * Update a token on server
		 *
		 * @param token Token to update
		 */
		async updateToken(token: IToken) {
			const { data } = await axios.put(`${BASE_URL}/${token.id}`, token, { confirmPassword: PwdConfirmationMode.Strict })
			return data
		},

		/**
		 * Add a new token
		 *
		 * @param name The token name
		 */
		async addToken(name: string) {
			logger.debug('Creating a new app token')

			// Let the failure reach the caller: AuthTokenSetup is the one that
			// reports it, the same way updateToken leaves reporting to its callers.
			const { data } = await axios.post<ITokenResponse>(BASE_URL, { name, oneTime: true }, { confirmPassword: PwdConfirmationMode.Strict })

			this.tokens.push(data.deviceToken)
			logger.debug('App token created')
			return data
		},

		/**
		 * Delete a given app token
		 *
		 * @param token Token to delete
		 */
		async deleteToken(token: IToken) {
			logger.debug('Deleting app token', { token })

			this.tokens = this.tokens.filter(({ id }) => id !== token.id)

			try {
				await axios.delete(`${BASE_URL}/${token.id}`, { confirmPassword: PwdConfirmationMode.Strict })
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
		 * Reconciles from the returned ids rather than clearing optimistically: the
		 * server keeps wipe-pending tokens, so it revokes fewer than we asked.
		 */
		async deleteAllOtherTokens() {
			logger.debug('Revoking all other app tokens')

			try {
				const { data } = await axios.delete<IRevokeAllResponse>(BASE_URL, { confirmPassword: PwdConfirmationMode.Strict })
				const revoked = new Set(data.revoked)
				this.tokens = this.tokens.filter(({ id }) => !revoked.has(id))
				logger.debug('Other app tokens revoked', { count: data.revoked.length })
				showSuccess(n('settings', 'Revoked %n other session', 'Revoked %n other sessions', data.revoked.length))
				return data
			} catch (error) {
				logger.error('Could not revoke the other app tokens', { error })
				showError(t('settings', 'Could not revoke the other sessions'))
			}
			return null
		},

		/**
		 * Wipe a token and the connected device
		 *
		 * @param token Token to wipe
		 */
		async wipeToken(token: IToken) {
			logger.debug('Wiping app token', { token })

			try {
				await confirmPassword()

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
		 *
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
		 *
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
