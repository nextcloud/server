/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2014 ownCloud Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

(function() {
	OC.Update = {
		_started: false,
		options: {},

		/**
		 * Start the update process.
		 *
		 * @param $el progress list element
		 * @param options
		 */
		start: function($el, options) {
			if (this._started) {
				return
			}

			this.options = options
			let hasWarnings = false

			this.$el = $el

			this._started = true

			const self = this

			$(window).on('beforeunload.inprogress', function() {
				return t('core', 'The update is in progress, leaving this page might interrupt the process in some environments.')
			})

			$('#update-progress-title').html(t(
				'core',
				'Update to {version}',
				{
					version: options.version,
				},
			))

			const updateEventSource = new OC.EventSource(OC.getRootPath() + '/core/ajax/update.php')
			updateEventSource.listen('success', function(message) {
				self.setMessage(message)
			})
			updateEventSource.listen('notice', function(message) {
				self.setPermanentMessage(message)
				hasWarnings = true
			})
			updateEventSource.listen('error', function(message) {
				$('#update-progress-message').hide()
				$('#update-progress-icon')
					.addClass('icon-error-white')
					.removeClass('icon-loading-dark')
				message = message || t('core', 'An error occurred.')
				$(window).off('beforeunload.inprogress')
				self.setErrorMessage(message)
				message = t('core', 'Please reload the page.')
				$('<p>').append('<a href=".">' + message + '</a>').appendTo($el)
				updateEventSource.close()
			})
			updateEventSource.listen('failure', function(message) {
				$(window).off('beforeunload.inprogress')
				$('#update-progress-message').hide()
				$('#update-progress-icon')
					.addClass('icon-error-white')
					.removeClass('icon-loading-dark')

				self.setErrorMessage(message)
				const updateUnsuccessful = $('<p>')
				if (message === 'Exception: Updates between multiple major versions and downgrades are unsupported.') {
					updateUnsuccessful.append(t('core', 'The update was unsuccessful. For more information <a href="{url}">check our forum post</a> covering this issue.', { url: 'https://help.nextcloud.com/t/updates-between-multiple-major-versions-are-unsupported/7094' }))
				} else if (OC.Update.options.productName === 'Nextcloud') {
					updateUnsuccessful.append(t('core', 'The update was unsuccessful. '
					+ 'Please report this issue to the '
					+ '<a href="https://github.com/nextcloud/server/issues" target="_blank">Nextcloud community</a>.'))
				}
				updateUnsuccessful.appendTo($el)
			})
			updateEventSource.listen('done', function() {
				$(window).off('beforeunload.inprogress')

				$('#update-progress-message').hide()

				$('#update-progress-icon')
					.addClass('icon-checkmark-white')
					.removeClass('icon-loading-dark')

				if (hasWarnings) {
					$el.find('.update-show-detailed').before($('<input type="button" class="primary" value="' + t('core', 'Continue to {productName}', OC.Update.options) + '">').on('click', function() {
						window.location.reload()
					}))
				} else {
					$el.find('.update-show-detailed').before($('<p id="redirect-countdown"></p>'))

					for (let i = 0; i <= 4; i++) {
						self.updateCountdown(i, 4)
					}

					setTimeout(function() {
						window.location = window.location.href
						window.location.reload()
					}, 3000)
				}
			})
		},

		updateCountdown: function(i, total) {
			setTimeout(function() {
				$('#redirect-countdown').text(n('core', 'The update was successful. Redirecting you to {productName} in %n second.', 'The update was successful. Redirecting you to {productName} in %n seconds.', i, OC.Update.options))
			}, (total - i) * 1000)
		},

		setMessage: function(message) {
			$('#update-progress-message').html(message)
			$('#update-progress-detailed')
				.append('<p>' + message + '</p>')
		},

		setPermanentMessage: function(message) {
			$('#update-progress-message').html(message)
			$('#update-progress-message-warnings')
				.show()
				.append($('<ul>').append(message))
			$('#update-progress-detailed')
				.append('<p>' + message + '</p>')
		},

		setErrorMessage: function(message) {
			$('#update-progress-message-error')
				.show()
				.html(message)
			$('#update-progress-detailed')
				.append('<p>' + message + '</p>')
		},
	}
})()

window.addEventListener('DOMContentLoaded', function() {
	$('.updateButton').on('click', function() {
		const $updateEl = $('.update')
		const $progressEl = $('.update-progress')
		$progressEl.removeClass('hidden')
		$('.updateOverview').addClass('hidden')
		$('#update-progress-message-error').hide()
		$('#update-progress-message-warnings').hide()
		OC.Update.start($progressEl, {
			productName: $updateEl.attr('data-productname'),
			version: $updateEl.attr('data-version'),
		})
		return false
	})

	$('.update-show-detailed').on('click', function() {
		$('#update-progress-detailed').toggleClass('hidden')
		return false
	})
})
