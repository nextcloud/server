/*
 * Copyright (c) 2014
 *
 * @author Vincent Petry
 * @copyright 2014 Vincent Petry <pvince81@owncloud.com>
 *
 * This file is licensed under the Affero General Public License version 3
 * or later.
 *
 * See the COPYING-README file.
 *
 */

(function() {

	if (!OCA.Files) {
		OCA.Files = {};
	}

	var App = {
		navigation: null,

		initialize: function() {
			this.navigation = new OCA.Files.Navigation($('#app-navigation'));

			// TODO: ideally these should be in a separate class / app (the embedded "all files" app)
			this.fileList = OCA.Files.FileList;
			this.fileActions = OCA.Files.FileActions;
			this.files = OCA.Files.Files;

			this.fileList = new OCA.Files.FileList($('#app-content-files'));
			this.files.initialize();
			this.fileActions.registerDefaultActions(this.fileList);
			this.fileList.setFileActions(this.fileActions);

			// for backward compatibility, the global FileList will
			// refer to the one of the "files" view
			window.FileList = this.fileList;

			this._setupEvents();
			// trigger URL change event handlers
			this._onPopState(OC.Util.History.parseUrlQuery());
		},

		/**
		 * Returns the container of the currently visible app.
		 *
		 * @return app container
		 */
		getCurrentAppContainer: function() {
			return this.navigation.getActiveContainer();
		},

		/**
		 * Setup events based on URL changes
		 */
		_setupEvents: function() {
			OC.Util.History.addOnPopStateHandler(_.bind(this._onPopState, this));

			// detect when app changed their current directory
			$('#app-content>div').on('changeDirectory', _.bind(this._onDirectoryChanged, this));

			$('#app-navigation').on('itemChanged', _.bind(this._onNavigationChanged, this));
		},

		/**
		 * Event handler for when the current navigation item has changed
		 */
		_onNavigationChanged: function(e) {
			var params;
			if (e && e.itemId) {
				params = {
					view: e.itemId,
					dir: '/'
				};
				this._changeUrl(params.view, params.dir);
				this.navigation.getActiveContainer().trigger(new $.Event('urlChanged', params));
			}
		},

		/**
		 * Event handler for when an app notified that its directory changed
		 */
		_onDirectoryChanged: function(e) {
			if (e.dir) {
				this._changeUrl(this.navigation.getActiveItem(), e.dir);
			}
		},

		/**
		 * Event handler for when the URL changed
		 */
		_onPopState: function(params) {
			params = _.extend({
				dir: '/',
				view: 'files'
			}, params);
			var lastId = this.navigation.getActiveItem();
			this.navigation.setActiveItem(params.view, {silent: true});
			if (lastId !== this.navigation.getActiveItem()) {
				this.navigation.getActiveContainer().trigger(new $.Event('show'));
			}
			this.navigation.getActiveContainer().trigger(new $.Event('urlChanged', params));
		},

		/**
		 * Change the URL to point to the given dir and view
		 */
		_changeUrl: function(view, dir) {
			var params = {dir: dir};
			if (view !== 'files') {
				params.view = view;
			}
			OC.Util.History.pushState(params);
		}
	};
	OCA.Files.App = App;
})();

$(document).ready(function() {
	// wait for other apps/extensions to register their event handlers
	// in the "ready" clause
	_.defer(function() {
		OCA.Files.App.initialize();
	});
});

