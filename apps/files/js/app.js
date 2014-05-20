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

/* global dragOptions, folderDropOptions */
(function() {

	if (!OCA.Files) {
		OCA.Files = {};
	}

	var App = {
		navigation: null,

		initialize: function() {
			this.navigation = new OCA.Files.Navigation($('#app-navigation'));

			var fileActions = new OCA.Files.FileActions();
			// default actions
			fileActions.registerDefaultActions();
			// legacy actions
			fileActions.merge(window.FileActions);
			// regular actions
			fileActions.merge(OCA.Files.fileActions);

			this.files = OCA.Files.Files;

			// TODO: ideally these should be in a separate class / app (the embedded "all files" app)
			this.fileList = new OCA.Files.FileList(
				$('#app-content-files'), {
					scrollContainer: $('#app-content'),
					dragOptions: dragOptions,
					folderDropOptions: folderDropOptions,
					fileActions: fileActions,
					allowLegacyActions: true
				}
			);
			this.files.initialize();

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
		 * Sets the currently active view
		 * @param viewId view id
		 */
		setActiveView: function(viewId, options) {
			this.navigation.setActiveItem(viewId, options);
		},

		/**
		 * Setup events based on URL changes
		 */
		_setupEvents: function() {
			OC.Util.History.addOnPopStateHandler(_.bind(this._onPopState, this));

			// detect when app changed their current directory
			$('#app-content').delegate('>div', 'changeDirectory', _.bind(this._onDirectoryChanged, this));
			$('#app-content').delegate('>div', 'changeViewerMode', _.bind(this._onChangeViewerMode, this));

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
		 * Event handler for when an app notifies that it needs space
		 * for viewer mode.
		 */
		_onChangeViewerMode: function(e) {
			var state = !!e.viewerModeEnabled;
			$('#app-navigation').toggleClass('hidden', state);
			$('.app-files').toggleClass('viewer-mode no-sidebar', state);
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
			if (!this.navigation.itemExists(params.view)) {
				params.view = 'files';
			}
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
	// wait for other apps/extensions to register their event handlers and file actions
	// in the "ready" clause
	_.defer(function() {
		OCA.Files.App.initialize();
	});
});

