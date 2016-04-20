/* global OC */

/**
 * Copyright (c) 2016, Christoph Wurst <christoph@owncloud.com>
 *
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING-README file.
 */

(function() {
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
				this._config = new OC.Backbone.Model()
			}

			this._inputFields = [
				'displayname',
				'phone',
				'email',
				'website',
				'address'
			];

			this._registerEvents();
		},

		render: function() {
			var self = this;
			_.each(this._inputFields, function(field) {
				var $heading = self.$('#' + field + 'form > h2');
				var $icon = self.$('#' + field + 'form > h2 > span');
				var scopeMenu = new OC.Settings.FederationScopeMenu();

				self.listenTo(scopeMenu, 'select:scope', function(scope) {
					self._onScopeChanged(field, scope);
				});
				$heading.append(scopeMenu.$el);
				$icon.on('click', _.bind(scopeMenu.show, scopeMenu));

				// Fix absolute position according to the heading text length
				// TODO: fix position without magic numbers
				var pos = ($heading.width() - $heading.find('label').width()) - 68;
				scopeMenu.$el.css('right', pos);
			});
		},

		_registerEvents: function() {
			var self = this;
			_.each(this._inputFields, function(field) {
				self.$('#' + field).keyUpDelayedOrEnter(_.bind(self._onInputChanged, self));
			});
		},

		_onInputChanged: function(e) {
			OC.msg.startSaving('#personal-settings-container .msg');
			var $target = $(e.target);
			var value = $target.val();
			var field = $target.attr('id');
			console.log(field + ' changed to ' + value);
			this._config.set(field, value);
			console.log(this._config.toJSON());
			// TODO: this._config.save();
			// TODO: OC.msg.finishedSaving('#personal-settings-container .msg', result);
			// TODO: call _updateDisplayName after successful update
		},

		_updateDisplayName: function(displayName) {
			// update displayName on the top right expand button
			$('#expandDisplayName').text($('#displayName').val());
			// update avatar if avatar is available
			if(!$('#removeavatar').hasClass('hidden')) {
				updateAvatar();
			}
		},

		_onScopeChanged: function(field, scope) {
			// TODO: save changes to the server
			console.log(field + ' changed to ' + scope);

			this._setFieldScopeIcon(field, scope);
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
})();