/* global OC, result, _ */

/**
 * Copyright (c) 2016, Christoph Wurst <christoph@owncloud.com>
 *
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING-README file.
 */

(function(_, $, OC) {
	'use strict';

	var FederationSettingsView = OC.Backbone.View.extend({
		_inputFields: undefined,

		/** @var Backbone.Model */
		_config: undefined,

		initialize: function(options) {
			options = options || {};

			if (options.config) {
				this._config = options.config;
			} else {
				this._config = new OC.Settings.UserSettings();
			}

			this._inputFields = [
				'displayname',
				'phone',
				'email',
				'website',
				'twitter',
				'address',
				'avatar'
			];

			var self = this;
			_.each(this._inputFields, function(field) {
				var scopeOnly = field === 'avatar';

				// Initialize config model
				if (!scopeOnly) {
					self._config.set(field, $('#' + field).val());
				}
				self._config.set(field + 'Scope', $('#' + field + 'scope').val());

				// Set inputs whenever model values change
				if (!scopeOnly) {
					self.listenTo(self._config, 'change:' + field, function() {
						self.$('#' + field).val(self._config.get(field));
					});
				}
				self.listenTo(self._config, 'change:' + field + 'Scope', function() {
					self._setFieldScopeIcon(field, self._config.get(field + 'Scope'));
				});
			});

			this._registerEvents();
		},

		render: function() {
			var self = this;
			_.each(this._inputFields, function(field) {
				var $heading = self.$('#' + field + 'form h2');
				var $icon = self.$('#' + field + 'form h2 > span');
				var scopeMenu = new OC.Settings.FederationScopeMenu({field: field});

				self.listenTo(scopeMenu, 'select:scope', function(scope) {
					self._onScopeChanged(field, scope);
				});
				$heading.append(scopeMenu.$el);
				$icon.on('click', _.bind(scopeMenu.show, scopeMenu));

				// Restore initial state
				self._setFieldScopeIcon(field, self._config.get(field + 'Scope'));
			});
		},

		_registerEvents: function() {
			var self = this;
			_.each(this._inputFields, function(field) {
				if (field === 'avatar') {
					return;
				}
				self.$('#' + field).keyUpDelayedOrEnter(_.bind(self._onInputChanged, self), true);
			});
		},

		_onInputChanged: function(e) {
			var self = this;

			var $dialog = $('.oc-dialog:visible');
			if (OC.PasswordConfirmation.requiresPasswordConfirmation()) {
				if($dialog.length === 0) {
					OC.PasswordConfirmation.requirePasswordConfirmation(_.bind(this._onInputChanged, this, e));
				}
				return;
			}
			var $target = $(e.target);
			var value = $target.val();
			var field = $target.attr('id');
			this._config.set(field, value);

			var savingData = this._config.save({
				error: function(jqXHR) {
					OC.msg.finishedSaving('#personal-settings-container .msg', jqXHR);
				}
			});

			$.when(savingData).done(function() {
				//OC.msg.finishedSaving('#personal-settings-container .msg', result)
				self._showInputChangeSuccess(field);
				if (field === 'displayname') {
					self._updateDisplayName(value);
				}
			});
		},

		_updateDisplayName: function(displayName) {
			// update displayName on the top right expand button
			$('#expandDisplayName').text(displayName);
			// update avatar if avatar is available
			if (!$('#removeavatar').hasClass('hidden')) {
				updateAvatar();
			}
		},

		_onScopeChanged: function(field, scope) {
			var $dialog = $('.oc-dialog:visible');
			if (OC.PasswordConfirmation.requiresPasswordConfirmation()) {
				if($dialog.length === 0) {
					OC.PasswordConfirmation.requirePasswordConfirmation(_.bind(this._onScopeChanged, this, field, scope));
				}
				return;
			}

			this._config.set(field + 'Scope', scope);

			$('#' + field + 'scope').val(scope);

			// TODO: user loading/success feedback
			this._config.save();
			this._setFieldScopeIcon(field, scope);
		},

		_showInputChangeSuccess: function(field) {
			var $icon = this.$('#' + field + 'form > span');
			$icon.fadeIn(200);
			setTimeout(function() {
				$icon.fadeOut(300);
			}, 2000);
		},

		_setFieldScopeIcon: function(field, scope) {
			var $icon = this.$('#' + field + 'form > h2 > span');
			$icon.removeClass('icon-password');
			$icon.removeClass('icon-contacts-dark');
			$icon.removeClass('icon-link');
			switch (scope) {
				case 'private':
					$icon.addClass('icon-password');
					break;
				case 'contacts':
					$icon.addClass('icon-contacts-dark');
					break;
				case 'public':
					$icon.addClass('icon-link');
					break;
			}
		}
	});

	OC.Settings = OC.Settings || {};
	OC.Settings.FederationSettingsView = FederationSettingsView;
})(_, $, OC);
