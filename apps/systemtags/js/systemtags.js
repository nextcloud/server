/******/ (function(modules) { // webpackBootstrap
/******/ 	// The module cache
/******/ 	var installedModules = {};
/******/
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/
/******/ 		// Check if module is in cache
/******/ 		if(installedModules[moduleId]) {
/******/ 			return installedModules[moduleId].exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = installedModules[moduleId] = {
/******/ 			i: moduleId,
/******/ 			l: false,
/******/ 			exports: {}
/******/ 		};
/******/
/******/ 		// Execute the module function
/******/ 		modules[moduleId].call(module.exports, module, module.exports, __webpack_require__);
/******/
/******/ 		// Flag the module as loaded
/******/ 		module.l = true;
/******/
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/
/******/
/******/ 	// expose the modules object (__webpack_modules__)
/******/ 	__webpack_require__.m = modules;
/******/
/******/ 	// expose the module cache
/******/ 	__webpack_require__.c = installedModules;
/******/
/******/ 	// define getter function for harmony exports
/******/ 	__webpack_require__.d = function(exports, name, getter) {
/******/ 		if(!__webpack_require__.o(exports, name)) {
/******/ 			Object.defineProperty(exports, name, { enumerable: true, get: getter });
/******/ 		}
/******/ 	};
/******/
/******/ 	// define __esModule on exports
/******/ 	__webpack_require__.r = function(exports) {
/******/ 		if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 			Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 		}
/******/ 		Object.defineProperty(exports, '__esModule', { value: true });
/******/ 	};
/******/
/******/ 	// create a fake namespace object
/******/ 	// mode & 1: value is a module id, require it
/******/ 	// mode & 2: merge all properties of value into the ns
/******/ 	// mode & 4: return value when already ns object
/******/ 	// mode & 8|1: behave like require
/******/ 	__webpack_require__.t = function(value, mode) {
/******/ 		if(mode & 1) value = __webpack_require__(value);
/******/ 		if(mode & 8) return value;
/******/ 		if((mode & 4) && typeof value === 'object' && value && value.__esModule) return value;
/******/ 		var ns = Object.create(null);
/******/ 		__webpack_require__.r(ns);
/******/ 		Object.defineProperty(ns, 'default', { enumerable: true, value: value });
/******/ 		if(mode & 2 && typeof value != 'string') for(var key in value) __webpack_require__.d(ns, key, function(key) { return value[key]; }.bind(null, key));
/******/ 		return ns;
/******/ 	};
/******/
/******/ 	// getDefaultExport function for compatibility with non-harmony modules
/******/ 	__webpack_require__.n = function(module) {
/******/ 		var getter = module && module.__esModule ?
/******/ 			function getDefault() { return module['default']; } :
/******/ 			function getModuleExports() { return module; };
/******/ 		__webpack_require__.d(getter, 'a', getter);
/******/ 		return getter;
/******/ 	};
/******/
/******/ 	// Object.prototype.hasOwnProperty.call
/******/ 	__webpack_require__.o = function(object, property) { return Object.prototype.hasOwnProperty.call(object, property); };
/******/
/******/ 	// __webpack_public_path__
/******/ 	__webpack_require__.p = "/js/";
/******/
/******/
/******/ 	// Load entry module and return exports
/******/ 	return __webpack_require__(__webpack_require__.s = "./apps/systemtags/src/systemtags.js");
/******/ })
/************************************************************************/
/******/ ({

/***/ "./apps/systemtags/src/app.js":
/*!************************************!*\
  !*** ./apps/systemtags/src/app.js ***!
  \************************************/
/*! no static exports found */
/***/ (function(module, exports) {

/*
 * Copyright (c) 2015 Vincent Petry <pvince81@owncloud.com>
 *
 * This file is licensed under the Affero General Public License version 3
 * or later.
 *
 * See the COPYING-README file.
 *
 */
(function () {
  if (!OCA.SystemTags) {
    /**
     * @namespace
     */
    OCA.SystemTags = {};
  }

  OCA.SystemTags.App = {
    initFileList: function initFileList($el) {
      if (this._fileList) {
        return this._fileList;
      }

      this._fileList = new OCA.SystemTags.FileList($el, {
        id: 'systemtags',
        fileActions: this._createFileActions(),
        config: OCA.Files.App.getFilesConfig(),
        // The file list is created when a "show" event is handled,
        // so it should be marked as "shown" like it would have been
        // done if handling the event with the file list already
        // created.
        shown: true
      });
      this._fileList.appName = t('systemtags', 'Tags');
      return this._fileList;
    },
    removeFileList: function removeFileList() {
      if (this._fileList) {
        this._fileList.$fileList.empty();
      }
    },
    _createFileActions: function _createFileActions() {
      // inherit file actions from the files app
      var fileActions = new OCA.Files.FileActions(); // note: not merging the legacy actions because legacy apps are not
      // compatible with the sharing overview and need to be adapted first

      fileActions.registerDefaultActions();
      fileActions.merge(OCA.Files.fileActions);

      if (!this._globalActionsInitialized) {
        // in case actions are registered later
        this._onActionsUpdated = _.bind(this._onActionsUpdated, this);
        OCA.Files.fileActions.on('setDefault.app-systemtags', this._onActionsUpdated);
        OCA.Files.fileActions.on('registerAction.app-systemtags', this._onActionsUpdated);
        this._globalActionsInitialized = true;
      } // when the user clicks on a folder, redirect to the corresponding
      // folder in the files app instead of opening it directly


      fileActions.register('dir', 'Open', OC.PERMISSION_READ, '', function (filename, context) {
        OCA.Files.App.setActiveView('files', {
          silent: true
        });
        OCA.Files.App.fileList.changeDirectory(OC.joinPaths(context.$file.attr('data-path'), filename), true, true);
      });
      fileActions.setDefault('dir', 'Open');
      return fileActions;
    },
    _onActionsUpdated: function _onActionsUpdated(ev) {
      if (!this._fileList) {
        return;
      }

      if (ev.action) {
        this._fileList.fileActions.registerAction(ev.action);
      } else if (ev.defaultAction) {
        this._fileList.fileActions.setDefault(ev.defaultAction.mime, ev.defaultAction.name);
      }
    },

    /**
     * Destroy the app
     */
    destroy: function destroy() {
      OCA.Files.fileActions.off('setDefault.app-systemtags', this._onActionsUpdated);
      OCA.Files.fileActions.off('registerAction.app-systemtags', this._onActionsUpdated);
      this.removeFileList();
      this._fileList = null;
      delete this._globalActionsInitialized;
    }
  };
})();

$(document).ready(function () {
  $('#app-content-systemtagsfilter').on('show', function (e) {
    OCA.SystemTags.App.initFileList($(e.target));
  });
  $('#app-content-systemtagsfilter').on('hide', function () {
    OCA.SystemTags.App.removeFileList();
  });
});

/***/ }),

/***/ "./apps/systemtags/src/css/systemtagsfilelist.scss":
/*!*********************************************************!*\
  !*** ./apps/systemtags/src/css/systemtagsfilelist.scss ***!
  \*********************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {


var content = __webpack_require__(/*! !../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/sass-loader/lib/loader.js!./systemtagsfilelist.scss */ "./node_modules/css-loader/dist/cjs.js!./node_modules/sass-loader/lib/loader.js!./apps/systemtags/src/css/systemtagsfilelist.scss");

if(typeof content === 'string') content = [[module.i, content, '']];

var transform;
var insertInto;



var options = {"hmr":true}

options.transform = transform
options.insertInto = undefined;

var update = __webpack_require__(/*! ../../../../node_modules/style-loader/lib/addStyles.js */ "./node_modules/style-loader/lib/addStyles.js")(content, options);

if(content.locals) module.exports = content.locals;

if(false) {}

/***/ }),

/***/ "./apps/systemtags/src/filesplugin.js":
/*!********************************************!*\
  !*** ./apps/systemtags/src/filesplugin.js ***!
  \********************************************/
/*! no static exports found */
/***/ (function(module, exports) {

/*
 * Copyright (c) 2015 Vincent Petry <pvince81@owncloud.com>
 *
 * This file is licensed under the Affero General Public License version 3
 * or later.
 *
 * See the COPYING-README file.
 *
 */
(function () {
  OCA.SystemTags = _.extend({}, OCA.SystemTags);

  if (!OCA.SystemTags) {
    /**
     * @namespace
     */
    OCA.SystemTags = {};
  }
  /**
   * @namespace
   */


  OCA.SystemTags.FilesPlugin = {
    ignoreLists: ['files_trashbin', 'files.public'],
    attach: function attach(fileList) {
      if (this.ignoreLists.indexOf(fileList.id) >= 0) {
        return;
      }

      var systemTagsInfoView = new OCA.SystemTags.SystemTagsInfoView();
      fileList.registerDetailView(systemTagsInfoView);

      _.each(fileList.getRegisteredDetailViews(), function (detailView) {
        if (detailView instanceof OCA.Files.MainFileInfoDetailView) {
          var systemTagsInfoViewToggleView = new OCA.SystemTags.SystemTagsInfoViewToggleView({
            systemTagsInfoView: systemTagsInfoView
          });
          systemTagsInfoViewToggleView.render(); // The toggle view element is detached before the
          // MainFileInfoDetailView is rendered to prevent its event
          // handlers from being removed.

          systemTagsInfoViewToggleView.listenTo(detailView, 'pre-render', function () {
            systemTagsInfoViewToggleView.$el.detach();
          });
          systemTagsInfoViewToggleView.listenTo(detailView, 'post-render', function () {
            detailView.$el.find('.file-details').append(systemTagsInfoViewToggleView.$el);
          });
          return;
        }
      });
    }
  };
})();

OC.Plugins.register('OCA.Files.FileList', OCA.SystemTags.FilesPlugin);

/***/ }),

/***/ "./apps/systemtags/src/systemtags.js":
/*!*******************************************!*\
  !*** ./apps/systemtags/src/systemtags.js ***!
  \*******************************************/
/*! no exports provided */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _app__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./app */ "./apps/systemtags/src/app.js");
/* harmony import */ var _app__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_app__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _systemtagsfilelist__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./systemtagsfilelist */ "./apps/systemtags/src/systemtagsfilelist.js");
/* harmony import */ var _systemtagsfilelist__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_systemtagsfilelist__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _filesplugin__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./filesplugin */ "./apps/systemtags/src/filesplugin.js");
/* harmony import */ var _filesplugin__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_filesplugin__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _systemtagsinfoview__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./systemtagsinfoview */ "./apps/systemtags/src/systemtagsinfoview.js");
/* harmony import */ var _systemtagsinfoview__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_systemtagsinfoview__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _systemtagsinfoviewtoggleview__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./systemtagsinfoviewtoggleview */ "./apps/systemtags/src/systemtagsinfoviewtoggleview.js");
/* harmony import */ var _systemtagsinfoviewtoggleview__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_systemtagsinfoviewtoggleview__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _css_systemtagsfilelist_scss__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./css/systemtagsfilelist.scss */ "./apps/systemtags/src/css/systemtagsfilelist.scss");
/* harmony import */ var _css_systemtagsfilelist_scss__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_css_systemtagsfilelist_scss__WEBPACK_IMPORTED_MODULE_5__);






window.OCA.SystemTags = OCA.SystemTags;

/***/ }),

/***/ "./apps/systemtags/src/systemtagsfilelist.js":
/*!***************************************************!*\
  !*** ./apps/systemtags/src/systemtagsfilelist.js ***!
  \***************************************************/
/*! no static exports found */
/***/ (function(module, exports) {

/*
 * Copyright (c) 2016 Vincent Petry <pvince81@owncloud.com>
 *
 * This file is licensed under the Affero General Public License version 3
 * or later.
 *
 * See the COPYING-README file.
 *
 */
(function () {
  /**
   * @class OCA.SystemTags.FileList
   * @augments OCA.Files.FileList
   *
   * @classdesc SystemTags file list.
   * Contains a list of files filtered by system tags.
   *
   * @param $el container element with existing markup for the #controls
   * and a table
   * @param [options] map of options, see other parameters
   * @param {Array.<string>} [options.systemTagIds] array of system tag ids to
   * filter by
   */
  var FileList = function FileList($el, options) {
    this.initialize($el, options);
  };

  FileList.prototype = _.extend({}, OCA.Files.FileList.prototype,
  /** @lends OCA.SystemTags.FileList.prototype */
  {
    id: 'systemtagsfilter',
    appName: t('systemtags', 'Tagged files'),

    /**
     * Array of system tag ids to filter by
     *
     * @type Array.<string>
     */
    _systemTagIds: [],
    _lastUsedTags: [],
    _clientSideSort: true,
    _allowSelection: false,
    _filterField: null,

    /**
     * @private
     */
    initialize: function initialize($el, options) {
      OCA.Files.FileList.prototype.initialize.apply(this, arguments);

      if (this.initialized) {
        return;
      }

      if (options && options.systemTagIds) {
        this._systemTagIds = options.systemTagIds;
      }

      OC.Plugins.attach('OCA.SystemTags.FileList', this);
      var $controls = this.$el.find('#controls').empty();

      _.defer(_.bind(this._getLastUsedTags, this));

      this._initFilterField($controls);
    },
    destroy: function destroy() {
      this.$filterField.remove();
      OCA.Files.FileList.prototype.destroy.apply(this, arguments);
    },
    _getLastUsedTags: function _getLastUsedTags() {
      var self = this;
      $.ajax({
        type: 'GET',
        url: OC.generateUrl('/apps/systemtags/lastused'),
        success: function success(response) {
          self._lastUsedTags = response;
        }
      });
    },
    _initFilterField: function _initFilterField($container) {
      var self = this;
      this.$filterField = $('<input type="hidden" name="tags"/>');
      $container.append(this.$filterField);
      this.$filterField.select2({
        placeholder: t('systemtags', 'Select tags to filter by'),
        allowClear: false,
        multiple: true,
        toggleSelect: true,
        separator: ',',
        query: _.bind(this._queryTagsAutocomplete, this),
        id: function id(tag) {
          return tag.id;
        },
        initSelection: function initSelection(element, callback) {
          var val = $(element).val().trim();

          if (val) {
            var tagIds = val.split(','),
                tags = [];
            OC.SystemTags.collection.fetch({
              success: function success() {
                _.each(tagIds, function (tagId) {
                  var tag = OC.SystemTags.collection.get(tagId);

                  if (!_.isUndefined(tag)) {
                    tags.push(tag.toJSON());
                  }
                });

                callback(tags);
              }
            });
          } else {
            callback([]);
          }
        },
        formatResult: function formatResult(tag) {
          return OC.SystemTags.getDescriptiveTag(tag);
        },
        formatSelection: function formatSelection(tag) {
          return OC.SystemTags.getDescriptiveTag(tag)[0].outerHTML;
        },
        sortResults: function sortResults(results) {
          results.sort(function (a, b) {
            var aLastUsed = self._lastUsedTags.indexOf(a.id);

            var bLastUsed = self._lastUsedTags.indexOf(b.id);

            if (aLastUsed !== bLastUsed) {
              if (bLastUsed === -1) {
                return -1;
              }

              if (aLastUsed === -1) {
                return 1;
              }

              return aLastUsed < bLastUsed ? -1 : 1;
            } // Both not found


            return OC.Util.naturalSortCompare(a.name, b.name);
          });
          return results;
        },
        escapeMarkup: function escapeMarkup(m) {
          // prevent double markup escape
          return m;
        },
        formatNoMatches: function formatNoMatches() {
          return t('systemtags', 'No tags found');
        }
      });
      this.$filterField.on('change', _.bind(this._onTagsChanged, this));
      return this.$filterField;
    },

    /**
     * Autocomplete function for dropdown results
     *
     * @param {Object} query select2 query object
     */
    _queryTagsAutocomplete: function _queryTagsAutocomplete(query) {
      OC.SystemTags.collection.fetch({
        success: function success() {
          var results = OC.SystemTags.collection.filterByName(query.term);
          query.callback({
            results: _.invoke(results, 'toJSON')
          });
        }
      });
    },

    /**
     * Event handler for when the URL changed
     */
    _onUrlChanged: function _onUrlChanged(e) {
      if (e.dir) {
        var tags = _.filter(e.dir.split('/'), function (val) {
          return val.trim() !== '';
        });

        this.$filterField.select2('val', tags || []);
        this._systemTagIds = tags;
        this.reload();
      }
    },
    _onTagsChanged: function _onTagsChanged(ev) {
      var val = $(ev.target).val().trim();

      if (val !== '') {
        this._systemTagIds = val.split(',');
      } else {
        this._systemTagIds = [];
      }

      this.$el.trigger(jQuery.Event('changeDirectory', {
        dir: this._systemTagIds.join('/')
      }));
      this.reload();
    },
    updateEmptyContent: function updateEmptyContent() {
      var dir = this.getCurrentDirectory();

      if (dir === '/') {
        // root has special permissions
        if (!this._systemTagIds.length) {
          // no tags selected
          this.$el.find('#emptycontent').html('<div class="icon-systemtags"></div>' + '<h2>' + t('systemtags', 'Please select tags to filter by') + '</h2>');
        } else {
          // tags selected but no results
          this.$el.find('#emptycontent').html('<div class="icon-systemtags"></div>' + '<h2>' + t('systemtags', 'No files found for the selected tags') + '</h2>');
        }

        this.$el.find('#emptycontent').toggleClass('hidden', !this.isEmpty);
        this.$el.find('#filestable thead th').toggleClass('hidden', this.isEmpty);
      } else {
        OCA.Files.FileList.prototype.updateEmptyContent.apply(this, arguments);
      }
    },
    getDirectoryPermissions: function getDirectoryPermissions() {
      return OC.PERMISSION_READ | OC.PERMISSION_DELETE;
    },
    updateStorageStatistics: function updateStorageStatistics() {// no op because it doesn't have
      // storage info like free space / used space
    },
    reload: function reload() {
      // there is only root
      this._setCurrentDir('/', false);

      if (!this._systemTagIds.length) {
        // don't reload
        this.updateEmptyContent();
        this.setFiles([]);
        return $.Deferred().resolve();
      }

      this._selectedFiles = {};

      this._selectionSummary.clear();

      if (this._currentFileModel) {
        this._currentFileModel.off();
      }

      this._currentFileModel = null;
      this.$el.find('.select-all').prop('checked', false);
      this.showMask();
      this._reloadCall = this.filesClient.getFilteredFiles({
        systemTagIds: this._systemTagIds
      }, {
        properties: this._getWebdavProperties()
      });

      if (this._detailsView) {
        // close sidebar
        this._updateDetailsView(null);
      }

      var callBack = this.reloadCallback.bind(this);
      return this._reloadCall.then(callBack, callBack);
    },
    reloadCallback: function reloadCallback(status, result) {
      if (result) {
        // prepend empty dir info because original handler
        result.unshift({});
      }

      return OCA.Files.FileList.prototype.reloadCallback.call(this, status, result);
    }
  });
  OCA.SystemTags.FileList = FileList;
})();

/***/ }),

/***/ "./apps/systemtags/src/systemtagsinfoview.js":
/*!***************************************************!*\
  !*** ./apps/systemtags/src/systemtagsinfoview.js ***!
  \***************************************************/
/*! no static exports found */
/***/ (function(module, exports) {

/*
 * Copyright (c) 2015
 *
 * This file is licensed under the Affero General Public License version 3
 * or later.
 *
 * See the COPYING-README file.
 *
 */
(function (OCA) {
  function modelToSelection(model) {
    var data = model.toJSON();

    if (!OC.isUserAdmin() && !data.canAssign) {
      data.locked = true;
    }

    return data;
  }
  /**
   * @class OCA.SystemTags.SystemTagsInfoView
   * @classdesc
   *
   * Displays a file's system tags
   *
   */


  var SystemTagsInfoView = OCA.Files.DetailFileInfoView.extend(
  /** @lends OCA.SystemTags.SystemTagsInfoView.prototype */
  {
    _rendered: false,
    className: 'systemTagsInfoView hidden',

    /**
     * @type OC.SystemTags.SystemTagsInputField
     */
    _inputView: null,
    initialize: function initialize(options) {
      var self = this;
      options = options || {};
      this._inputView = new OC.SystemTags.SystemTagsInputField({
        multiple: true,
        allowActions: true,
        allowCreate: true,
        isAdmin: OC.isUserAdmin(),
        initSelection: function initSelection(element, callback) {
          callback(self.selectedTagsCollection.map(modelToSelection));
        }
      });
      this.selectedTagsCollection = new OC.SystemTags.SystemTagsMappingCollection([], {
        objectType: 'files'
      });

      this._inputView.collection.on('change:name', this._onTagRenamedGlobally, this);

      this._inputView.collection.on('remove', this._onTagDeletedGlobally, this);

      this._inputView.on('select', this._onSelectTag, this);

      this._inputView.on('deselect', this._onDeselectTag, this);
    },

    /**
     * Event handler whenever a tag was selected
     */
    _onSelectTag: function _onSelectTag(tag) {
      // create a mapping entry for this tag
      this.selectedTagsCollection.create(tag.toJSON());
    },

    /**
     * Event handler whenever a tag gets deselected.
     * Removes the selected tag from the mapping collection.
     *
     * @param {string} tagId tag id
     */
    _onDeselectTag: function _onDeselectTag(tagId) {
      this.selectedTagsCollection.get(tagId).destroy();
    },

    /**
     * Event handler whenever a tag was renamed globally.
     *
     * This will automatically adjust the tag mapping collection to
     * container the new name.
     *
     * @param {OC.Backbone.Model} changedTag tag model that has changed
     */
    _onTagRenamedGlobally: function _onTagRenamedGlobally(changedTag) {
      // also rename it in the selection, if applicable
      var selectedTagMapping = this.selectedTagsCollection.get(changedTag.id);

      if (selectedTagMapping) {
        selectedTagMapping.set(changedTag.toJSON());
      }
    },

    /**
     * Event handler whenever a tag was deleted globally.
     *
     * This will automatically adjust the tag mapping collection to
     * container the new name.
     *
     * @param {OC.Backbone.Model} tagId tag model that has changed
     */
    _onTagDeletedGlobally: function _onTagDeletedGlobally(tagId) {
      // also rename it in the selection, if applicable
      this.selectedTagsCollection.remove(tagId);
    },
    setFileInfo: function setFileInfo(fileInfo) {
      var self = this;

      if (!this._rendered) {
        this.render();
      }

      if (fileInfo) {
        this.selectedTagsCollection.setObjectId(fileInfo.id);
        this.selectedTagsCollection.fetch({
          success: function success(collection) {
            collection.fetched = true;
            var appliedTags = collection.map(modelToSelection);

            self._inputView.setData(appliedTags);

            if (appliedTags.length !== 0) {
              self.show();
            } else {
              self.hide();
            }
          }
        });
      }

      this.hide();
    },

    /**
     * Renders this details view
     */
    render: function render() {
      var self = this;
      this.$el.append(this._inputView.$el);

      this._inputView.render();
    },
    isVisible: function isVisible() {
      return !this.$el.hasClass('hidden');
    },
    show: function show() {
      this.$el.removeClass('hidden');
    },
    hide: function hide() {
      this.$el.addClass('hidden');
    },
    openDropdown: function openDropdown() {
      this.$el.find('.systemTagsInputField').select2('open');
    },
    remove: function remove() {
      this._inputView.remove();
    }
  });
  OCA.SystemTags.SystemTagsInfoView = SystemTagsInfoView;
})(OCA);

/***/ }),

/***/ "./apps/systemtags/src/systemtagsinfoviewtoggleview.js":
/*!*************************************************************!*\
  !*** ./apps/systemtags/src/systemtagsinfoviewtoggleview.js ***!
  \*************************************************************/
/*! no static exports found */
/***/ (function(module, exports) {

/**
 *
 * @copyright Copyright (c) 2017, Daniel Calviño Sánchez (danxuliu@gmail.com)
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */
(function (OCA) {
  /**
   * @class OCA.SystemTags.SystemTagsInfoViewToggleView
   * @classdesc
   *
   * View to toggle the visibility of a SystemTagsInfoView.
   *
   * This toggle view must be explicitly rendered before it is used.
   */
  var SystemTagsInfoViewToggleView = OC.Backbone.View.extend(
  /** @lends OC.Backbone.View.prototype */
  {
    tagName: 'span',
    className: 'tag-label',
    events: {
      'click': 'click'
    },

    /**
     * @type OCA.SystemTags.SystemTagsInfoView
     */
    _systemTagsInfoView: null,
    template: function template(data) {
      return '<span class="icon icon-tag"/>' + t('systemtags', 'Tags');
    },

    /**
     * Initialize this toggle view.
     *
     * The options must provide a systemTagsInfoView parameter that
     * references the SystemTagsInfoView to associate to this toggle view.
     */
    initialize: function initialize(options) {
      var self = this;
      options = options || {};
      this._systemTagsInfoView = options.systemTagsInfoView;

      if (!this._systemTagsInfoView) {
        throw 'Missing required parameter "systemTagsInfoView"';
      }
    },

    /**
     * Toggles the visibility of the associated SystemTagsInfoView.
     *
     * When the systemTagsInfoView is shown its dropdown is also opened.
     */
    click: function click() {
      if (this._systemTagsInfoView.isVisible()) {
        this._systemTagsInfoView.hide();
      } else {
        this._systemTagsInfoView.show();

        this._systemTagsInfoView.openDropdown();
      }
    },

    /**
     * Renders this toggle view.
     *
     * @return OCA.SystemTags.SystemTagsInfoViewToggleView this object.
     */
    render: function render() {
      this.$el.html(this.template());
      return this;
    }
  });
  OCA.SystemTags.SystemTagsInfoViewToggleView = SystemTagsInfoViewToggleView;
})(OCA);

/***/ }),

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/sass-loader/lib/loader.js!./apps/systemtags/src/css/systemtagsfilelist.scss":
/*!****************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/sass-loader/lib/loader.js!./apps/systemtags/src/css/systemtagsfilelist.scss ***!
  \****************************************************************************************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

exports = module.exports = __webpack_require__(/*! ../../../../node_modules/css-loader/dist/runtime/api.js */ "./node_modules/css-loader/dist/runtime/api.js")(false);
// Module
exports.push([module.i, "/*\n * Copyright (c) 2016\n *\n * This file is licensed under the Affero General Public License version 3\n * or later.\n *\n * See the COPYING-README file.\n *\n */\n#app-content-systemtagsfilter .select2-container {\n  width: 30%;\n  margin-left: 10px; }\n\n#app-sidebar .mainFileInfoView .tag-label {\n  cursor: pointer;\n  padding: 13px; }\n\n#app-sidebar .mainFileInfoView .icon-tag {\n  opacity: .5;\n  vertical-align: middle; }\n", ""]);



/***/ }),

/***/ "./node_modules/css-loader/dist/runtime/api.js":
/*!*****************************************************!*\
  !*** ./node_modules/css-loader/dist/runtime/api.js ***!
  \*****************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


/*
  MIT License http://www.opensource.org/licenses/mit-license.php
  Author Tobias Koppers @sokra
*/
// css base code, injected by the css-loader
module.exports = function (useSourceMap) {
  var list = []; // return the list of modules as css string

  list.toString = function toString() {
    return this.map(function (item) {
      var content = cssWithMappingToString(item, useSourceMap);

      if (item[2]) {
        return '@media ' + item[2] + '{' + content + '}';
      } else {
        return content;
      }
    }).join('');
  }; // import a list of modules into the list


  list.i = function (modules, mediaQuery) {
    if (typeof modules === 'string') {
      modules = [[null, modules, '']];
    }

    var alreadyImportedModules = {};

    for (var i = 0; i < this.length; i++) {
      var id = this[i][0];

      if (id != null) {
        alreadyImportedModules[id] = true;
      }
    }

    for (i = 0; i < modules.length; i++) {
      var item = modules[i]; // skip already imported module
      // this implementation is not 100% perfect for weird media query combinations
      // when a module is imported multiple times with different media queries.
      // I hope this will never occur (Hey this way we have smaller bundles)

      if (item[0] == null || !alreadyImportedModules[item[0]]) {
        if (mediaQuery && !item[2]) {
          item[2] = mediaQuery;
        } else if (mediaQuery) {
          item[2] = '(' + item[2] + ') and (' + mediaQuery + ')';
        }

        list.push(item);
      }
    }
  };

  return list;
};

function cssWithMappingToString(item, useSourceMap) {
  var content = item[1] || '';
  var cssMapping = item[3];

  if (!cssMapping) {
    return content;
  }

  if (useSourceMap && typeof btoa === 'function') {
    var sourceMapping = toComment(cssMapping);
    var sourceURLs = cssMapping.sources.map(function (source) {
      return '/*# sourceURL=' + cssMapping.sourceRoot + source + ' */';
    });
    return [content].concat(sourceURLs).concat([sourceMapping]).join('\n');
  }

  return [content].join('\n');
} // Adapted from convert-source-map (MIT)


function toComment(sourceMap) {
  // eslint-disable-next-line no-undef
  var base64 = btoa(unescape(encodeURIComponent(JSON.stringify(sourceMap))));
  var data = 'sourceMappingURL=data:application/json;charset=utf-8;base64,' + base64;
  return '/*# ' + data + ' */';
}

/***/ }),

/***/ "./node_modules/style-loader/lib/addStyles.js":
/*!****************************************************!*\
  !*** ./node_modules/style-loader/lib/addStyles.js ***!
  \****************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

/*
	MIT License http://www.opensource.org/licenses/mit-license.php
	Author Tobias Koppers @sokra
*/

var stylesInDom = {};

var	memoize = function (fn) {
	var memo;

	return function () {
		if (typeof memo === "undefined") memo = fn.apply(this, arguments);
		return memo;
	};
};

var isOldIE = memoize(function () {
	// Test for IE <= 9 as proposed by Browserhacks
	// @see http://browserhacks.com/#hack-e71d8692f65334173fee715c222cb805
	// Tests for existence of standard globals is to allow style-loader
	// to operate correctly into non-standard environments
	// @see https://github.com/webpack-contrib/style-loader/issues/177
	return window && document && document.all && !window.atob;
});

var getTarget = function (target, parent) {
  if (parent){
    return parent.querySelector(target);
  }
  return document.querySelector(target);
};

var getElement = (function (fn) {
	var memo = {};

	return function(target, parent) {
                // If passing function in options, then use it for resolve "head" element.
                // Useful for Shadow Root style i.e
                // {
                //   insertInto: function () { return document.querySelector("#foo").shadowRoot }
                // }
                if (typeof target === 'function') {
                        return target();
                }
                if (typeof memo[target] === "undefined") {
			var styleTarget = getTarget.call(this, target, parent);
			// Special case to return head of iframe instead of iframe itself
			if (window.HTMLIFrameElement && styleTarget instanceof window.HTMLIFrameElement) {
				try {
					// This will throw an exception if access to iframe is blocked
					// due to cross-origin restrictions
					styleTarget = styleTarget.contentDocument.head;
				} catch(e) {
					styleTarget = null;
				}
			}
			memo[target] = styleTarget;
		}
		return memo[target]
	};
})();

var singleton = null;
var	singletonCounter = 0;
var	stylesInsertedAtTop = [];

var	fixUrls = __webpack_require__(/*! ./urls */ "./node_modules/style-loader/lib/urls.js");

module.exports = function(list, options) {
	if (typeof DEBUG !== "undefined" && DEBUG) {
		if (typeof document !== "object") throw new Error("The style-loader cannot be used in a non-browser environment");
	}

	options = options || {};

	options.attrs = typeof options.attrs === "object" ? options.attrs : {};

	// Force single-tag solution on IE6-9, which has a hard limit on the # of <style>
	// tags it will allow on a page
	if (!options.singleton && typeof options.singleton !== "boolean") options.singleton = isOldIE();

	// By default, add <style> tags to the <head> element
        if (!options.insertInto) options.insertInto = "head";

	// By default, add <style> tags to the bottom of the target
	if (!options.insertAt) options.insertAt = "bottom";

	var styles = listToStyles(list, options);

	addStylesToDom(styles, options);

	return function update (newList) {
		var mayRemove = [];

		for (var i = 0; i < styles.length; i++) {
			var item = styles[i];
			var domStyle = stylesInDom[item.id];

			domStyle.refs--;
			mayRemove.push(domStyle);
		}

		if(newList) {
			var newStyles = listToStyles(newList, options);
			addStylesToDom(newStyles, options);
		}

		for (var i = 0; i < mayRemove.length; i++) {
			var domStyle = mayRemove[i];

			if(domStyle.refs === 0) {
				for (var j = 0; j < domStyle.parts.length; j++) domStyle.parts[j]();

				delete stylesInDom[domStyle.id];
			}
		}
	};
};

function addStylesToDom (styles, options) {
	for (var i = 0; i < styles.length; i++) {
		var item = styles[i];
		var domStyle = stylesInDom[item.id];

		if(domStyle) {
			domStyle.refs++;

			for(var j = 0; j < domStyle.parts.length; j++) {
				domStyle.parts[j](item.parts[j]);
			}

			for(; j < item.parts.length; j++) {
				domStyle.parts.push(addStyle(item.parts[j], options));
			}
		} else {
			var parts = [];

			for(var j = 0; j < item.parts.length; j++) {
				parts.push(addStyle(item.parts[j], options));
			}

			stylesInDom[item.id] = {id: item.id, refs: 1, parts: parts};
		}
	}
}

function listToStyles (list, options) {
	var styles = [];
	var newStyles = {};

	for (var i = 0; i < list.length; i++) {
		var item = list[i];
		var id = options.base ? item[0] + options.base : item[0];
		var css = item[1];
		var media = item[2];
		var sourceMap = item[3];
		var part = {css: css, media: media, sourceMap: sourceMap};

		if(!newStyles[id]) styles.push(newStyles[id] = {id: id, parts: [part]});
		else newStyles[id].parts.push(part);
	}

	return styles;
}

function insertStyleElement (options, style) {
	var target = getElement(options.insertInto)

	if (!target) {
		throw new Error("Couldn't find a style target. This probably means that the value for the 'insertInto' parameter is invalid.");
	}

	var lastStyleElementInsertedAtTop = stylesInsertedAtTop[stylesInsertedAtTop.length - 1];

	if (options.insertAt === "top") {
		if (!lastStyleElementInsertedAtTop) {
			target.insertBefore(style, target.firstChild);
		} else if (lastStyleElementInsertedAtTop.nextSibling) {
			target.insertBefore(style, lastStyleElementInsertedAtTop.nextSibling);
		} else {
			target.appendChild(style);
		}
		stylesInsertedAtTop.push(style);
	} else if (options.insertAt === "bottom") {
		target.appendChild(style);
	} else if (typeof options.insertAt === "object" && options.insertAt.before) {
		var nextSibling = getElement(options.insertAt.before, target);
		target.insertBefore(style, nextSibling);
	} else {
		throw new Error("[Style Loader]\n\n Invalid value for parameter 'insertAt' ('options.insertAt') found.\n Must be 'top', 'bottom', or Object.\n (https://github.com/webpack-contrib/style-loader#insertat)\n");
	}
}

function removeStyleElement (style) {
	if (style.parentNode === null) return false;
	style.parentNode.removeChild(style);

	var idx = stylesInsertedAtTop.indexOf(style);
	if(idx >= 0) {
		stylesInsertedAtTop.splice(idx, 1);
	}
}

function createStyleElement (options) {
	var style = document.createElement("style");

	if(options.attrs.type === undefined) {
		options.attrs.type = "text/css";
	}

	if(options.attrs.nonce === undefined) {
		var nonce = getNonce();
		if (nonce) {
			options.attrs.nonce = nonce;
		}
	}

	addAttrs(style, options.attrs);
	insertStyleElement(options, style);

	return style;
}

function createLinkElement (options) {
	var link = document.createElement("link");

	if(options.attrs.type === undefined) {
		options.attrs.type = "text/css";
	}
	options.attrs.rel = "stylesheet";

	addAttrs(link, options.attrs);
	insertStyleElement(options, link);

	return link;
}

function addAttrs (el, attrs) {
	Object.keys(attrs).forEach(function (key) {
		el.setAttribute(key, attrs[key]);
	});
}

function getNonce() {
	if (false) {}

	return __webpack_require__.nc;
}

function addStyle (obj, options) {
	var style, update, remove, result;

	// If a transform function was defined, run it on the css
	if (options.transform && obj.css) {
	    result = typeof options.transform === 'function'
		 ? options.transform(obj.css) 
		 : options.transform.default(obj.css);

	    if (result) {
	    	// If transform returns a value, use that instead of the original css.
	    	// This allows running runtime transformations on the css.
	    	obj.css = result;
	    } else {
	    	// If the transform function returns a falsy value, don't add this css.
	    	// This allows conditional loading of css
	    	return function() {
	    		// noop
	    	};
	    }
	}

	if (options.singleton) {
		var styleIndex = singletonCounter++;

		style = singleton || (singleton = createStyleElement(options));

		update = applyToSingletonTag.bind(null, style, styleIndex, false);
		remove = applyToSingletonTag.bind(null, style, styleIndex, true);

	} else if (
		obj.sourceMap &&
		typeof URL === "function" &&
		typeof URL.createObjectURL === "function" &&
		typeof URL.revokeObjectURL === "function" &&
		typeof Blob === "function" &&
		typeof btoa === "function"
	) {
		style = createLinkElement(options);
		update = updateLink.bind(null, style, options);
		remove = function () {
			removeStyleElement(style);

			if(style.href) URL.revokeObjectURL(style.href);
		};
	} else {
		style = createStyleElement(options);
		update = applyToTag.bind(null, style);
		remove = function () {
			removeStyleElement(style);
		};
	}

	update(obj);

	return function updateStyle (newObj) {
		if (newObj) {
			if (
				newObj.css === obj.css &&
				newObj.media === obj.media &&
				newObj.sourceMap === obj.sourceMap
			) {
				return;
			}

			update(obj = newObj);
		} else {
			remove();
		}
	};
}

var replaceText = (function () {
	var textStore = [];

	return function (index, replacement) {
		textStore[index] = replacement;

		return textStore.filter(Boolean).join('\n');
	};
})();

function applyToSingletonTag (style, index, remove, obj) {
	var css = remove ? "" : obj.css;

	if (style.styleSheet) {
		style.styleSheet.cssText = replaceText(index, css);
	} else {
		var cssNode = document.createTextNode(css);
		var childNodes = style.childNodes;

		if (childNodes[index]) style.removeChild(childNodes[index]);

		if (childNodes.length) {
			style.insertBefore(cssNode, childNodes[index]);
		} else {
			style.appendChild(cssNode);
		}
	}
}

function applyToTag (style, obj) {
	var css = obj.css;
	var media = obj.media;

	if(media) {
		style.setAttribute("media", media)
	}

	if(style.styleSheet) {
		style.styleSheet.cssText = css;
	} else {
		while(style.firstChild) {
			style.removeChild(style.firstChild);
		}

		style.appendChild(document.createTextNode(css));
	}
}

function updateLink (link, options, obj) {
	var css = obj.css;
	var sourceMap = obj.sourceMap;

	/*
		If convertToAbsoluteUrls isn't defined, but sourcemaps are enabled
		and there is no publicPath defined then lets turn convertToAbsoluteUrls
		on by default.  Otherwise default to the convertToAbsoluteUrls option
		directly
	*/
	var autoFixUrls = options.convertToAbsoluteUrls === undefined && sourceMap;

	if (options.convertToAbsoluteUrls || autoFixUrls) {
		css = fixUrls(css);
	}

	if (sourceMap) {
		// http://stackoverflow.com/a/26603875
		css += "\n/*# sourceMappingURL=data:application/json;base64," + btoa(unescape(encodeURIComponent(JSON.stringify(sourceMap)))) + " */";
	}

	var blob = new Blob([css], { type: "text/css" });

	var oldSrc = link.href;

	link.href = URL.createObjectURL(blob);

	if(oldSrc) URL.revokeObjectURL(oldSrc);
}


/***/ }),

/***/ "./node_modules/style-loader/lib/urls.js":
/*!***********************************************!*\
  !*** ./node_modules/style-loader/lib/urls.js ***!
  \***********************************************/
/*! no static exports found */
/***/ (function(module, exports) {


/**
 * When source maps are enabled, `style-loader` uses a link element with a data-uri to
 * embed the css on the page. This breaks all relative urls because now they are relative to a
 * bundle instead of the current page.
 *
 * One solution is to only use full urls, but that may be impossible.
 *
 * Instead, this function "fixes" the relative urls to be absolute according to the current page location.
 *
 * A rudimentary test suite is located at `test/fixUrls.js` and can be run via the `npm test` command.
 *
 */

module.exports = function (css) {
  // get current location
  var location = typeof window !== "undefined" && window.location;

  if (!location) {
    throw new Error("fixUrls requires window.location");
  }

	// blank or null?
	if (!css || typeof css !== "string") {
	  return css;
  }

  var baseUrl = location.protocol + "//" + location.host;
  var currentDir = baseUrl + location.pathname.replace(/\/[^\/]*$/, "/");

	// convert each url(...)
	/*
	This regular expression is just a way to recursively match brackets within
	a string.

	 /url\s*\(  = Match on the word "url" with any whitespace after it and then a parens
	   (  = Start a capturing group
	     (?:  = Start a non-capturing group
	         [^)(]  = Match anything that isn't a parentheses
	         |  = OR
	         \(  = Match a start parentheses
	             (?:  = Start another non-capturing groups
	                 [^)(]+  = Match anything that isn't a parentheses
	                 |  = OR
	                 \(  = Match a start parentheses
	                     [^)(]*  = Match anything that isn't a parentheses
	                 \)  = Match a end parentheses
	             )  = End Group
              *\) = Match anything and then a close parens
          )  = Close non-capturing group
          *  = Match anything
       )  = Close capturing group
	 \)  = Match a close parens

	 /gi  = Get all matches, not the first.  Be case insensitive.
	 */
	var fixedCss = css.replace(/url\s*\(((?:[^)(]|\((?:[^)(]+|\([^)(]*\))*\))*)\)/gi, function(fullMatch, origUrl) {
		// strip quotes (if they exist)
		var unquotedOrigUrl = origUrl
			.trim()
			.replace(/^"(.*)"$/, function(o, $1){ return $1; })
			.replace(/^'(.*)'$/, function(o, $1){ return $1; });

		// already a full url? no change
		if (/^(#|data:|http:\/\/|https:\/\/|file:\/\/\/|\s*$)/i.test(unquotedOrigUrl)) {
		  return fullMatch;
		}

		// convert the url to a full url
		var newUrl;

		if (unquotedOrigUrl.indexOf("//") === 0) {
		  	//TODO: should we add protocol?
			newUrl = unquotedOrigUrl;
		} else if (unquotedOrigUrl.indexOf("/") === 0) {
			// path should be relative to the base url
			newUrl = baseUrl + unquotedOrigUrl; // already starts with '/'
		} else {
			// path should be relative to current directory
			newUrl = currentDir + unquotedOrigUrl.replace(/^\.\//, ""); // Strip leading './'
		}

		// send back the fixed url(...)
		return "url(" + JSON.stringify(newUrl) + ")";
	});

	// send back the fixed css
	return fixedCss;
};


/***/ })

/******/ });
//# sourceMappingURL=systemtags.js.map