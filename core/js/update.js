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
		 * Start the upgrade process.
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

			$(window).on('beforeunload.inprogress', function () {
				return t('core', 'The upgrade is in progress, leaving this page might interrupt the process in some environments.');
			});

			this.addMessage(t(
				'core',
				'Updating {productName} to version {version}, this may take a while.', {
					productName: options.productName || 'ownCloud',
					version: options.version
				}),
				'bold'
			).append('<br />'); // FIXME: these should be ul/li with CSS paddings!

			var updateEventSource = new OC.EventSource(OC.webroot+'/core/ajax/update.php');
			updateEventSource.listen('success', function(message) {
				$('<span>').append(message).append('<br />').appendTo($el);
			});
			updateEventSource.listen('notice', function(message) {
				$('<span>').addClass('error').append(message).append('<br />').appendTo($el);
				hasWarnings = true;
			});
			updateEventSource.listen('error', function(message) {
				message = message || t('core', 'An error occurred.');
				$(window).off('beforeunload.inprogress');
				$('<span>').addClass('error').append(message).append('<br />').appendTo($el);
				message = t('core', 'Please reload the page.');
				$('<span>').addClass('error').append('<a href=".">'+message+'</a><br />').appendTo($el);
				updateEventSource.close();
			});
			updateEventSource.listen('failure', function(message) {
				$(window).off('beforeunload.inprogress');
				$('<span>').addClass('error').append(message).append('<br />').appendTo($el);
				var span = $('<span>')
					.addClass('bold');
				if(message === 'Exception: Updates between multiple major versions and downgrades are unsupported.') {
					span.append(t('core', 'The update was unsuccessful. For more information <a href="{url}">check our forum post</a> covering this issue.', {'url': 'https://forum.owncloud.org/viewtopic.php?f=17&t=32087'}));
				} else {
					span.append(t('core', 'The update was unsuccessful. ' +
						'Please report this issue to the ' +
						'<a href="https://github.com/owncloud/core/issues" target="_blank">ownCloud community</a>.'));
				}
				span.appendTo($el);
			});
			updateEventSource.listen('done', function() {
				$(window).off('beforeunload.inprogress');

				if (hasWarnings) {
					$('<span>').addClass('bold')
						.append('<br />')
						.append(t('core', 'The update was successful. There were warnings.'))
						.appendTo($el);
					var message = t('core', 'Please reload the page.');
					$('<span>').append('<br />').append(message).append('<br />').appendTo($el);
				} else {
					// FIXME: use product name
					$('<span>').addClass('bold')
						.append('<br />')
						.append(t('core', 'The update was successful. Redirecting you to ownCloud now.'))
						.appendTo($el);
					setTimeout(function () {
						OC.redirect(OC.webroot + '/');
					}, 3000);
				}
			});
		},

		addMessage: function(message, className) {
			var $span = $('<span>');
			$span.addClass(className).append(message).append('<br />').appendTo(this.$el);
			return $span;
		}
	};

})();

$(document).ready(function() {
	$('.updateButton').on('click', function() {
		var $updateEl = $('.update');
		var $progressEl = $('.updateProgress');
		$progressEl.removeClass('hidden');
		$('.updateOverview').addClass('hidden');
		OC.Update.start($progressEl, {
			productName: $updateEl.attr('data-productname'),
			version: $updateEl.attr('data-version'),
		});
		return false;
	});
});
