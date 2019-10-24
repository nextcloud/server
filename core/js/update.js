/*
 * Copyright (c) 2014
 *
 * This file is licensed under the Affero General Public License version 3
 * or later.
 *
 * See the COPYING-README file.
 *
 */

(function() {
	OC.Update = {
		_started : false,

		/**
		 * Start the update process.
		 *
		 * @param $el progress list element
		 */
		start: function($el, options) {
			if (this._started) {
				return;
			}

			var hasWarnings = false;

			this.$el = $el;

			this._started = true;

			var self = this;

			$(window).on('beforeunload.inprogress', function () {
				return t('core', 'The update is in progress, leaving this page might interrupt the process in some environments.');
			});

			$('#update-progress-title').html(t(
				'core',
				'Update to {version}', {
					version: options.version
				})
			);

			var updateEventSource = new OC.EventSource(OC.getRootPath()+'/core/ajax/update.php');
			updateEventSource.listen('success', function(message) {
				self.setMessage(message);
			});
			updateEventSource.listen('notice', function(message) {
				self.setPermanentMessage(message);
				hasWarnings = true;
			});
			updateEventSource.listen('error', function(message) {
				$('#update-progress-message').hide();
				$('#update-progress-icon')
					.addClass('icon-error-white')
					.removeClass('icon-loading-dark');
				message = message || t('core', 'An error occurred.');
				$(window).off('beforeunload.inprogress');
				self.setErrorMessage(message);
				message = t('core', 'Please reload the page.');
				$('<p>').append('<a href=".">'+message+'</a>').appendTo($el);
				updateEventSource.close();
			});
			updateEventSource.listen('failure', function(message) {
				$(window).off('beforeunload.inprogress');
				$('#update-progress-message').hide();
				$('#update-progress-icon')
					.addClass('icon-error-white')
					.removeClass('icon-loading-dark');

				self.setErrorMessage(message);
				var updateUnsuccessful = $('<p>');
				if(message === 'Exception: Updates between multiple major versions and downgrades are unsupported.') {
					updateUnsuccessful.append(t('core', 'The update was unsuccessful. For more information <a href="{url}">check our forum post</a> covering this issue.', {'url': 'https://help.nextcloud.com/t/updates-between-multiple-major-versions-are-unsupported/7094'}));
				} else {
					updateUnsuccessful.append(t('core', 'The update was unsuccessful. ' +
						'Please report this issue to the ' +
						'<a href="https://github.com/nextcloud/server/issues" target="_blank">Nextcloud community</a>.'));
				}
				updateUnsuccessful.appendTo($el);
			});
			updateEventSource.listen('done', function() {
				$(window).off('beforeunload.inprogress');

				$('#update-progress-message').hide();

				$('#update-progress-icon')
					.addClass('icon-checkmark-white')
				        .removeClass('icon-loading-dark');

				if (hasWarnings) {
					$el.find('.update-show-detailed').before(
						$('<input type="button" class="update-continue" value="'+t('core', 'Continue to Nextcloud')+'">').on('click', function() {
							window.location.reload();
						})
					);
				} else {
					// FIXME: use product name


					$el.find('.update-show-detailed').before(
						$('<p id="redirect-countdown"></p>')
					);

					for(var i = 0; i <= 4; i++){
						self.updateCountdown(i, 4);
					}

					setTimeout(function () {
						OC.redirect(window.location.href);
					}, 3000);
				}
			});
		},

		updateCountdown: function (i, total) {
			setTimeout(function(){
				 $("#redirect-countdown").text(n('core', 'The update was successful. Redirecting you to Nextcloud in %n second.', 'The update was successful. Redirecting you to Nextcloud in %n seconds.', i));
			}, (total - i) * 1000);
		},

		setMessage: function(message) {
			$('#update-progress-message').html(message);
			$('#update-progress-detailed')
				.append('<p>' + message + '</p>');
		},

		setPermanentMessage: function(message) {
			$('#update-progress-message').html(message);
			$('#update-progress-message-warnings')
				.show()
				.append($('<ul>').append(message));
			$('#update-progress-detailed')
				.append('<p>' + message + '</p>');
		},

		setErrorMessage: function (message) {
			$('#update-progress-message-error')
				.show()
				.html(message);
			$('#update-progress-detailed')
				.append('<p>' + message + '</p>');
		}
	};

})();

$(document).ready(function() {
	$('.updateButton').on('click', function() {
		var $updateEl = $('.update');
		var $progressEl = $('.update-progress');
		$progressEl.removeClass('hidden');
		$('.updateOverview').addClass('hidden');
		$('#update-progress-message-error').hide();
		$('#update-progress-message-warnings').hide();
		OC.Update.start($progressEl, {
			productName: $updateEl.attr('data-productname'),
			version: $updateEl.attr('data-version')
		});
		return false;
	});
	$('.update-show-detailed').on('click', function() {
		$('#update-progress-detailed').toggleClass('hidden');
		return false;
	});
});
