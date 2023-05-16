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

/* global dragOptions, folderDropOptions, OC */
(function() {

	if (!OCA.Files) {
		/**
		 * Namespace for the files app
		 * @namespace OCA.Files
		 */
		OCA.Files = {};
	}

	/**
	 * @namespace OCA.Files.App
	 */
	OCA.Files.App = {
		/**
		 * Navigation instance
		 *
		 * @member {OCP.Files.Navigation}
		 */
		navigation: null,

		/**
		 * File list for the "All files" section.
		 *
		 * @member {OCA.Files.FileList}
		 */
		fileList: null,

		currentFileList: null,

		/**
		 * Backbone model for storing files preferences
		 */
		_filesConfig: null,

		/**
		 * Initializes the files app
		 */
		initialize: function() {
			this.navigation = OCP.Files.Navigation;
			this.$showHiddenFiles = $('input#showhiddenfilesToggle');
			var showHidden = $('#showHiddenFiles').val() === "1";
			this.$showHiddenFiles.prop('checked', showHidden);

			// Toggle for grid view
			this.$showGridView = $('input#showgridview');
			this.$showGridView.on('change', _.bind(this._onGridviewChange, this));

			if ($('#fileNotFound').val() === "1") {
				OC.Notification.show(t('files', 'File could not be found'), {type: 'error'});
			}

			this._filesConfig = OCP.InitialState.loadState('files', 'config', {})

			var { fileid, scrollto, openfile } = OC.Util.History.parseUrlQuery();
			var fileActions = new OCA.Files.FileActions();
			// default actions
			fileActions.registerDefaultActions();
			// regular actions
			fileActions.merge(OCA.Files.fileActions);

			this._onActionsUpdated = _.bind(this._onActionsUpdated, this);
			OCA.Files.fileActions.on('setDefault.app-files', this._onActionsUpdated);
			OCA.Files.fileActions.on('registerAction.app-files', this._onActionsUpdated);

			this.files = OCA.Files.Files;

			// TODO: ideally these should be in a separate class / app (the embedded "all files" app)
			this.fileList = new OCA.Files.FileList(
				$('#app-content-files'), {
					dragOptions: dragOptions,
					folderDropOptions: folderDropOptions,
					fileActions: fileActions,
					allowLegacyActions: true,
					scrollTo: scrollto,
					openFile: openfile,
					filesClient: OC.Files.getClient(),
					multiSelectMenu: [
						{
							name: 'copyMove',
							displayName:  t('files', 'Move or copy'),
							iconClass: 'icon-external',
							order: 10,
						},
						{
							name: 'download',
							displayName:  t('files', 'Download'),
							iconClass: 'icon-download',
							order: 10,
						},
						OCA.Files.FileList.MultiSelectMenuActions.ToggleSelectionModeAction,
						{
							name: 'delete',
							displayName:  t('files', 'Delete'),
							iconClass: 'icon-delete',
							order: 99,
						},
						...(
							OCA?.SystemTags === undefined ? [] : ([{
								name: 'tags',
								displayName:  t('files', 'Tags'),
								iconClass: 'icon-tag',
								order: 100,
							}])
						),
					],
					sorting: {
						mode: $('#defaultFileSorting').val(),
						direction: $('#defaultFileSortingDirection').val()
					},
					config: this._filesConfig,
					enableUpload: true,
					maxChunkSize: OC.appConfig.files && OC.appConfig.files.max_chunk_size
				}
			);
			this.updateCurrentFileList(this.fileList)
			this.files.initialize();

			// for backward compatibility, the global FileList will
			// refer to the one of the "files" view
			window.FileList = this.fileList;

			OC.Plugins.attach('OCA.Files.App', this);

			this._setupEvents();
			// trigger URL change event handlers
			this._onPopState({ ...OC.Util.History.parseUrlQuery(), view: this.navigation?.active?.id });

			this._debouncedPersistShowHiddenFilesState = _.debounce(this._persistShowHiddenFilesState, 1200);
			this._debouncedPersistCropImagePreviewsState = _.debounce(this._persistCropImagePreviewsState, 1200);

			if (sessionStorage.getItem('WhatsNewServerCheck') < (Date.now() - 3600*1000)) {
				OCP.WhatsNew.query(); // for Nextcloud server
				sessionStorage.setItem('WhatsNewServerCheck', Date.now());
			}
		},

		/**
		 * Destroy the app
		 */
		destroy: function() {
			this.fileList.destroy();
			this.fileList = null;
			this.files = null;
			OCA.Files.fileActions.off('setDefault.app-files', this._onActionsUpdated);
			OCA.Files.fileActions.off('registerAction.app-files', this._onActionsUpdated);
		},

		_onActionsUpdated: function(ev) {
			// forward new action to the file list
			if (ev.action) {
				this.fileList.fileActions.registerAction(ev.action);
			} else if (ev.defaultAction) {
				this.fileList.fileActions.setDefault(
					ev.defaultAction.mime,
					ev.defaultAction.name
				);
			}
		},

		/**
		 * Set the currently active file list
		 *
		 * Due to the file list implementations being registered after clicking the
		 * navigation item for the first time, OCA.Files.App is not aware of those until
		 * they have initialized themselves. Therefore the files list needs to call this
		 * method manually
		 *
		 * @param {OCA.Files.FileList} newFileList -
		 */
		updateCurrentFileList: function(newFileList) {
			if (this.currentFileList === newFileList) {
				return
			}

			this.currentFileList = newFileList;
			if (this.currentFileList !== null) {
				// update grid view to the current value
				const isGridView = this.$showGridView.is(':checked');
				this.currentFileList.setGridView(isGridView);
			}
		},

		/**
		 * Return the currently active file list
		 * @return {?OCA.Files.FileList}
		 */
		getCurrentFileList: function () {
			return this.currentFileList;
		},

		/**
		 * Returns the container of the currently visible app.
		 *
		 * @return app container
		 */
		getCurrentAppContainer: function() {
			var viewId = this.getActiveView();
			return $('#app-content-' + viewId);
		},

		/**
		 * Sets the currently active view
		 * @param viewId view id
		 */
		setActiveView: function(viewId) {
			// The Navigation API will handle the final event
			window._nc_event_bus.emit('files:legacy-navigation:changed', { id: viewId })
		},

		/**
		 * Returns the view id of the currently active view
		 * @return view id
		 */
		getActiveView: function() {
			return this.navigation.active
				&& this.navigation.active.id;
		},

		/**
		 *
		 * @returns {Backbone.Model}
		 */
		getFilesConfig: function() {
			return this._filesConfig;
		},

		/**
		 * Setup events based on URL changes
		 */
		_setupEvents: function() {
			OC.Util.History.addOnPopStateHandler(_.bind(this._onPopState, this));

			// detect when app changed their current directory
			$('#app-content').delegate('>div', 'changeDirectory', _.bind(this._onDirectoryChanged, this));
			$('#app-content').delegate('>div', 'afterChangeDirectory', _.bind(this._onAfterDirectoryChanged, this));
			$('#app-content').delegate('>div', 'changeViewerMode', _.bind(this._onChangeViewerMode, this));
		},

		/**
		 * Event handler for when the current navigation item has changed
		 */
		_onNavigationChanged: function(view) {
			var params;
			if (view && (view.itemId || view.id)) {
				if (view.id) {
					params = {
						view: view.id,
						dir: '/',
					}
				} else {
					// Legacy handling
					params = {
						view: typeof view.view === 'string' && view.view !== '' ? view.view : view.itemId,
						dir: view.dir ? view.dir : '/'
					}
				}
				this._changeUrl(params.view, params.dir);
				OCA.Files.Sidebar.close();
				this.getCurrentAppContainer().trigger(new $.Event('urlChanged', params));
				window._nc_event_bus.emit('files:navigation:changed')
			}
		},

		/**
		 * Event handler for when an app notified that its directory changed
		 */
		_onDirectoryChanged: function(e) {
			if (e.dir && !e.changedThroughUrl) {
				this._changeUrl(this.getActiveView(), e.dir, e.fileId);
			}
		},

		/**
		 * Event handler for when an app notified that its directory changed
		 */
		_onAfterDirectoryChanged: function(e) {
			if (e.dir && e.fileId) {
				this._changeUrl(this.getActiveView(), e.dir, e.fileId);
			}
		},

		/**
		 * Event handler for when an app notifies that it needs space
		 * for viewer mode.
		 */
		_onChangeViewerMode: function(e) {
			var state = !!e.viewerModeEnabled;
			if (e.viewerModeEnabled) {
				OCA.Files.Sidebar.close();
			}
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

			var lastId = this.navigation.active;
			if (!this.navigation.views.find(view => view.id === params.view)) {
				params.view = 'files';
			}

			this.setActiveView(params.view, {silent: true});
			if (lastId !== this.getActiveView()) {
				this.getCurrentAppContainer().trigger(new $.Event('show', params));
				window._nc_event_bus.emit('files:navigation:changed')
			}

			this.getCurrentAppContainer().trigger(new $.Event('urlChanged', params));

		},

		/**
		 * Encode URL params into a string, except for the "dir" attribute
		 * that gets encoded as path where "/" is not encoded
		 *
		 * @param {Object.<string>} params
		 * @return {string} encoded params
		 */
		_makeUrlParams: function(params) {
			var dir = params.dir;
			delete params.dir;
			return 'dir=' + OC.encodePath(dir) + '&' + OC.buildQueryString(params);
		},

		/**
		 * Change the URL to point to the given dir and view
		 */
		_changeUrl: function(view, dir, fileId) {
			var params = { dir: dir };
			if (view !== 'files') {
				params.view = view;
			} else if (fileId) {
				params.fileid = fileId;
			}
			var currentParams = OC.Util.History.parseUrlQuery();
			if (currentParams.dir === params.dir && currentParams.view === params.view) {
				if (currentParams.fileid !== params.fileid) {
					// if only fileid changed or was added, replace instead of push
					OC.Util.History.replaceState(this._makeUrlParams(params));
					return
				}
			} else {
				OC.Util.History.pushState(this._makeUrlParams(params));
				return
			}
		},

		/**
		 * Toggle showing gridview by default or not
		 *
		 * @returns {undefined}
		 */
		_onGridviewChange: function() {
			const isGridView = this.$showGridView.is(':checked');
			// only save state if user is logged in
			if (OC.currentUser) {
				$.post(OC.generateUrl('/apps/files/api/v1/showgridview'), {
					show: isGridView,
				});
			}
			this.$showGridView.next('#view-toggle')
				.removeClass('icon-toggle-filelist icon-toggle-pictures')
				.addClass(isGridView ? 'icon-toggle-filelist' : 'icon-toggle-pictures')
			this.$showGridView.next('#view-toggle')
				.attr('title', isGridView ? t('files', 'Show list view') : t('files', 'Show grid view'))
			this.$showGridView.attr('aria-label', isGridView ? t('files', 'Show list view') : t('files', 'Show grid view'))

			if (this.currentFileList) {
				this.currentFileList.setGridView(isGridView);
			}
		},

	};
})();

window.addEventListener('DOMContentLoaded', function() {
	// wait for other apps/extensions to register their event handlers and file actions
	// in the "ready" clause
	_.defer(function() {
		OCA.Files.App.initialize();
	});
});
