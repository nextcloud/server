/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2014-2016 ownCloud Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

(function() {
	OC.SetupChecks = {

		/* Message types */
		MESSAGE_TYPE_INFO:0,
		MESSAGE_TYPE_WARNING:1,
		MESSAGE_TYPE_ERROR:2,

		/**
		 * Runs setup checks on the server side
		 *
		 * @return $.Deferred object resolved with an array of error messages
		 */
		checkSetup: function() {
			var deferred = $.Deferred();
			var afterCall = function(data, statusText, xhr) {
				var messages = [];
				if (xhr.status === 200 && data) {
					if (Object.keys(data.generic).length > 0) {
						Object.keys(data.generic).forEach(function(key){
							Object.keys(data.generic[key]).forEach(function(title){
								if (data.generic[key][title].severity != 'success') {
									data.generic[key][title].pass = false;
									OC.SetupChecks.addGenericSetupCheck(data.generic[key], title, messages);
								}
							});
						});
					}
				} else {
					messages.push({
						msg: t('core', 'Error occurred while checking server setup'),
						type: OC.SetupChecks.MESSAGE_TYPE_ERROR
					});
				}
				deferred.resolve(messages);
			};

			$.ajax({
				type: 'GET',
				url: OC.generateUrl('settings/ajax/checksetup'),
				allowAuthErrors: true
			}).then(afterCall, afterCall);
			return deferred.promise();
		},

		escapeHTML: function(text) {
			return text.toString()
				.split('&').join('&amp;')
				.split('<').join('&lt;')
				.split('>').join('&gt;')
				.split('"').join('&quot;')
				.split('\'').join('&#039;')
		},

		/**
		* @param message      The message string containing placeholders.
		* @param parameters   An object with keys as placeholders and values as their replacements.
		*
		* @return The message with placeholders replaced by values.
		*/
		richToParsed: function (message, parameters) {
			for (var [placeholder, parameter] of Object.entries(parameters)) {
				var replacement;
				if (parameter.type === 'user') {
					replacement = '@' + this.escapeHTML(parameter.name);
				} else if (parameter.type === 'file') {
					replacement = this.escapeHTML(parameter.path) || this.escapeHTML(parameter.name);
				} else if (parameter.type === 'highlight') {
					replacement = '<a href="' + encodeURI(parameter.link) + '">' + this.escapeHTML(parameter.name) + '</a>';
				} else {
					replacement = this.escapeHTML(parameter.name);
				}
				message = message.replace('{' + placeholder + '}', replacement);
			}

			return message;
		},

		addGenericSetupCheck: function(data, check, messages) {
			var setupCheck = data[check] || { pass: true, description: '', severity: 'info', linkToDoc: null}

			var type = OC.SetupChecks.MESSAGE_TYPE_INFO
			if (setupCheck.severity === 'warning') {
				type = OC.SetupChecks.MESSAGE_TYPE_WARNING
			} else if (setupCheck.severity === 'error') {
				type = OC.SetupChecks.MESSAGE_TYPE_ERROR
			}

			var message = setupCheck.description;
			if (message) {
				message = this.escapeHTML(message)
			}
			if (setupCheck.descriptionParameters) {
				message = this.richToParsed(message, setupCheck.descriptionParameters);
			}
			if (setupCheck.linkToDoc) {
				message += ' ' + t('core', 'For more details see the {linkstart}documentation ↗{linkend}.')
					.replace('{linkstart}', '<a target="_blank" rel="noreferrer noopener" class="external" href="' + setupCheck.linkToDoc + '">')
					.replace('{linkend}', '</a>');
			}
			if (setupCheck.elements) {
				message += '<br><ul>'
				setupCheck.elements.forEach(function(element){
					message += '<li>';
					message += element
					message += '</li>';
				});
				message += '</ul>'
			}

			if (!setupCheck.pass) {
				messages.push({
					msg: message,
					type: type,
				})
			}
		},
	};
})();
