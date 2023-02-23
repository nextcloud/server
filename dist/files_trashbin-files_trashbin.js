/******/ (function() { // webpackBootstrap
/******/ 	var __webpack_modules__ = ({

/***/ "./apps/files_trashbin/src/app.js":
/*!****************************************!*\
  !*** ./apps/files_trashbin/src/app.js ***!
  \****************************************/
/***/ (function() {

/**
 * Copyright (c) 2014
 *
 * @author Abijeet <abijeetpatro@gmail.com>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Daniel Calviño Sánchez <danxuliu@gmail.com>
 * @author Joas Schilling <coding@schilljs.com>
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Vincent Petry <vincent@nextcloud.com>
 *
 * @license AGPL-3.0-or-later
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

OCA.Trashbin = {};
/**
 * @namespace OCA.Trashbin.App
 */
OCA.Trashbin.App = {
  _initialized: false,
  /** @type {OC.Files.Client} */
  client: null,
  initialize: function initialize($el) {
    if (this._initialized) {
      return;
    }
    this._initialized = true;
    this.client = new OC.Files.Client({
      host: OC.getHost(),
      port: OC.getPort(),
      root: OC.linkToRemoteBase('dav') + '/trashbin/' + OC.getCurrentUser().uid,
      useHTTPS: OC.getProtocol() === 'https'
    });
    var urlParams = OC.Util.History.parseUrlQuery();
    this.fileList = new OCA.Trashbin.FileList($('#app-content-trashbin'), {
      fileActions: this._createFileActions(),
      detailsViewEnabled: false,
      scrollTo: urlParams.scrollto,
      config: OCA.Files.App.getFilesConfig(),
      multiSelectMenu: [{
        name: 'restore',
        displayName: t('files_trashbin', 'Restore'),
        iconClass: 'icon-history'
      }, {
        name: 'delete',
        displayName: t('files_trashbin', 'Delete permanently'),
        iconClass: 'icon-delete'
      }],
      client: this.client,
      // The file list is created when a "show" event is handled, so
      // it should be marked as "shown" like it would have been done
      // if handling the event with the file list already created.
      shown: true
    });
  },
  _createFileActions: function _createFileActions() {
    var client = this.client;
    var fileActions = new OCA.Files.FileActions();
    fileActions.register('dir', 'Open', OC.PERMISSION_READ, '', function (filename, context) {
      var dir = context.fileList.getCurrentDirectory();
      context.fileList.changeDirectory(OC.joinPaths(dir, filename));
    });
    fileActions.setDefault('dir', 'Open');
    fileActions.registerAction({
      name: 'Restore',
      displayName: t('files_trashbin', 'Restore'),
      type: OCA.Files.FileActions.TYPE_INLINE,
      mime: 'all',
      permissions: OC.PERMISSION_READ,
      iconClass: 'icon-history',
      actionHandler: function actionHandler(filename, context) {
        var fileList = context.fileList;
        var tr = fileList.findFileEl(filename);
        fileList.showFileBusyState(tr, true);
        var dir = context.fileList.getCurrentDirectory();
        client.move(OC.joinPaths('trash', dir, filename), OC.joinPaths('restore', filename), true).then(fileList._removeCallback.bind(fileList, [filename]), function () {
          fileList.showFileBusyState(tr, false);
          OC.Notification.show(t('files_trashbin', 'Error while restoring file from trash bin'));
        });
      }
    });
    fileActions.registerAction({
      name: 'Delete',
      displayName: t('files_trashbin', 'Delete permanently'),
      mime: 'all',
      permissions: OC.PERMISSION_READ,
      iconClass: 'icon-delete',
      render: function render(actionSpec, isDefault, context) {
        var $actionLink = fileActions._makeActionLink(actionSpec, context);
        $actionLink.attr('original-title', t('files_trashbin', 'Delete permanently'));
        $actionLink.children('img').attr('alt', t('files_trashbin', 'Delete permanently'));
        context.$file.find('td:last').append($actionLink);
        return $actionLink;
      },
      actionHandler: function actionHandler(filename, context) {
        var fileList = context.fileList;
        $('.tipsy').remove();
        var tr = fileList.findFileEl(filename);
        fileList.showFileBusyState(tr, true);
        var dir = context.fileList.getCurrentDirectory();
        client.remove(OC.joinPaths('trash', dir, filename)).then(fileList._removeCallback.bind(fileList, [filename]), function () {
          fileList.showFileBusyState(tr, false);
          OC.Notification.show(t('files_trashbin', 'Error while removing file from trash bin'));
        });
      }
    });
    return fileActions;
  }
};
window.addEventListener('DOMContentLoaded', function () {
  $('#app-content-trashbin').one('show', function () {
    var App = OCA.Trashbin.App;
    App.initialize($('#app-content-trashbin'));
    // force breadcrumb init
    // App.fileList.changeDirectory(App.fileList.getCurrentDirectory(), false, true);
  });
});

/***/ }),

/***/ "./apps/files_trashbin/src/filelist.js":
/*!*********************************************!*\
  !*** ./apps/files_trashbin/src/filelist.js ***!
  \*********************************************/
/***/ (function() {

/**
 * Copyright (c) 2014
 *
 * @author Azul <azul@riseup.net>
 * @author Gary Kim <gary@garykim.dev>
 * @author Jan C. Borchardt <hey@jancborchardt.net>
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Vincent Petry <vincent@nextcloud.com>
 *
 * @license AGPL-3.0-or-later
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

/* eslint-disable */
(function () {
  var DELETED_REGEXP = new RegExp(/^(.+)\.d[0-9]+$/);
  var FILENAME_PROP = '{http://nextcloud.org/ns}trashbin-filename';
  var DELETION_TIME_PROP = '{http://nextcloud.org/ns}trashbin-deletion-time';
  var TRASHBIN_ORIGINAL_LOCATION = '{http://nextcloud.org/ns}trashbin-original-location';
  var TRASHBIN_TITLE = '{http://nextcloud.org/ns}trashbin-title';

  /**
   * Convert a file name in the format filename.d12345 to the real file name.
   * This will use basename.
   * The name will not be changed if it has no ".d12345" suffix.
   * @param {String} name file name
   * @returns {String} converted file name
   */
  function getDeletedFileName(name) {
    name = OC.basename(name);
    var match = DELETED_REGEXP.exec(name);
    if (match && match.length > 1) {
      name = match[1];
    }
    return name;
  }

  /**
   * @class OCA.Trashbin.FileList
   * @augments OCA.Files.FileList
   * @classdesc List of deleted files
   *
   * @param $el container element with existing markup for the .files-controls
   * and a table
   * @param [options] map of options
   */
  var FileList = function FileList($el, options) {
    this.client = options.client;
    this.initialize($el, options);
  };
  FileList.prototype = _.extend({}, OCA.Files.FileList.prototype, /** @lends OCA.Trashbin.FileList.prototype */{
    id: 'trashbin',
    appName: t('files_trashbin', 'Deleted files'),
    /** @type {OC.Files.Client} */
    client: null,
    /**
     * @private
     */
    initialize: function initialize() {
      this.client.addFileInfoParser(function (response, data) {
        var props = response.propStat[0].properties;
        var path = props[TRASHBIN_ORIGINAL_LOCATION];
        var title = props[TRASHBIN_TITLE];
        return {
          displayName: props[FILENAME_PROP],
          mtime: parseInt(props[DELETION_TIME_PROP], 10) * 1000,
          hasPreview: true,
          path: path,
          extraData: title
        };
      });
      var result = OCA.Files.FileList.prototype.initialize.apply(this, arguments);
      this.$el.find('.undelete').click('click', _.bind(this._onClickRestoreSelected, this));

      // Sort by most recently deleted first
      this.setSort('mtime', 'desc');

      /**
       * Override crumb making to add "Deleted Files" entry
       * and convert files with ".d" extensions to a more
       * user friendly name.
       */
      this.breadcrumb._makeCrumbs = function () {
        var parts = OCA.Files.BreadCrumb.prototype._makeCrumbs.apply(this, [].concat(Array.prototype.slice.call(arguments), ['icon-delete no-hover']));
        for (var i = 1; i < parts.length; i++) {
          parts[i].name = getDeletedFileName(parts[i].name);
        }
        return parts;
      };
      OC.Plugins.attach('OCA.Trashbin.FileList', this);
      return result;
    },
    /**
    * Override to only return read permissions
    */
    getDirectoryPermissions: function getDirectoryPermissions() {
      return OC.PERMISSION_READ | OC.PERMISSION_DELETE;
    },
    _setCurrentDir: function _setCurrentDir(targetDir) {
      OCA.Files.FileList.prototype._setCurrentDir.apply(this, arguments);
      var baseDir = OC.basename(targetDir);
      if (baseDir !== '') {
        this.setPageTitle(getDeletedFileName(baseDir));
      }
    },
    _createRow: function _createRow() {
      // FIXME: MEGAHACK until we find a better solution
      var tr = OCA.Files.FileList.prototype._createRow.apply(this, arguments);
      tr.find('td.filesize').remove();
      return tr;
    },
    getAjaxUrl: function getAjaxUrl(action, params) {
      var q = '';
      if (params) {
        q = '?' + OC.buildQueryString(params);
      }
      return OC.filePath('files_trashbin', 'ajax', action + '.php') + q;
    },
    setupUploadEvents: function setupUploadEvents() {
      // override and do nothing
    },
    linkTo: function linkTo(dir) {
      return OC.linkTo('files', 'index.php') + '?view=trashbin&dir=' + encodeURIComponent(dir).replace(/%2F/g, '/');
    },
    elementToFile: function elementToFile($el) {
      var fileInfo = OCA.Files.FileList.prototype.elementToFile($el);
      if (this.getCurrentDirectory() === '/') {
        fileInfo.displayName = getDeletedFileName(fileInfo.name);
      }
      // no size available
      delete fileInfo.size;
      return fileInfo;
    },
    updateEmptyContent: function updateEmptyContent() {
      var exists = this.$fileList.find('tr:first').exists();
      this.$el.find('.emptyfilelist.emptycontent').toggleClass('hidden', exists);
      this.$el.find('.files-filestable th').toggleClass('hidden', !exists);
    },
    _removeCallback: function _removeCallback(files) {
      var $el;
      for (var i = 0; i < files.length; i++) {
        $el = this.remove(OC.basename(files[i]), {
          updateSummary: false
        });
        this.fileSummary.remove({
          type: $el.attr('data-type'),
          size: $el.attr('data-size')
        });
      }
      this.fileSummary.update();
      this.updateEmptyContent();
    },
    _onClickRestoreSelected: function _onClickRestoreSelected(event) {
      event.preventDefault();
      var self = this;
      var files = _.pluck(this.getSelectedFiles(), 'name');
      for (var i = 0; i < files.length; i++) {
        var tr = this.findFileEl(files[i]);
        this.showFileBusyState(tr, true);
      }
      this.fileMultiSelectMenu.toggleLoading('restore', true);
      var restorePromises = files.map(function (file) {
        return self.client.move(OC.joinPaths('trash', self.getCurrentDirectory(), file), OC.joinPaths('restore', file), true).then(function () {
          self._removeCallback([file]);
        });
      });
      return Promise.all(restorePromises).then(function () {
        self.fileMultiSelectMenu.toggleLoading('restore', false);
      }, function () {
        OC.Notification.show(t('files_trashbin', 'Error while restoring files from trash bin'));
      });
    },
    _onClickDeleteSelected: function _onClickDeleteSelected(event) {
      event.preventDefault();
      var self = this;
      var allFiles = this.$el.find('.select-all').is(':checked');
      var files = _.pluck(this.getSelectedFiles(), 'name');
      for (var i = 0; i < files.length; i++) {
        var tr = this.findFileEl(files[i]);
        this.showFileBusyState(tr, true);
      }
      if (allFiles) {
        return this.client.remove(OC.joinPaths('trash', this.getCurrentDirectory())).then(function () {
          self.hideMask();
          self.setFiles([]);
        }, function () {
          OC.Notification.show(t('files_trashbin', 'Error while emptying trash bin'));
        });
      } else {
        this.fileMultiSelectMenu.toggleLoading('delete', true);
        var deletePromises = files.map(function (file) {
          return self.client.remove(OC.joinPaths('trash', self.getCurrentDirectory(), file)).then(function () {
            self._removeCallback([file]);
          });
        });
        return Promise.all(deletePromises).then(function () {
          self.fileMultiSelectMenu.toggleLoading('delete', false);
        }, function () {
          OC.Notification.show(t('files_trashbin', 'Error while removing files from trash bin'));
        });
      }
    },
    _onClickFile: function _onClickFile(event) {
      var mime = $(this).parent().parent().data('mime');
      if (mime !== 'httpd/unix-directory') {
        event.preventDefault();
      }
      return OCA.Files.FileList.prototype._onClickFile.apply(this, arguments);
    },
    generatePreviewUrl: function generatePreviewUrl(urlSpec) {
      return OC.generateUrl('/apps/files_trashbin/preview?') + $.param(urlSpec);
    },
    getDownloadUrl: function getDownloadUrl() {
      // no downloads
      return '#';
    },
    getDefaultActionUrl: function getDefaultActionUrl() {
      // no default action
      return '#';
    },
    updateStorageStatistics: function updateStorageStatistics() {
      // no op because the trashbin doesn't have
      // storage info like free space / used space
    },
    isSelectedDeletable: function isSelectedDeletable() {
      return true;
    },
    /**
    * Returns list of webdav properties to request
    */
    _getWebdavProperties: function _getWebdavProperties() {
      return [FILENAME_PROP, DELETION_TIME_PROP, TRASHBIN_ORIGINAL_LOCATION, TRASHBIN_TITLE].concat(this.filesClient.getPropfindProperties());
    },
    /**
    * Reloads the file list using ajax call
    *
    * @returns ajax call object
    */
    reload: function reload() {
      var _this$_reloadCall;
      this._selectedFiles = {};
      this._selectionSummary.clear();
      this.$el.find('.select-all').prop('checked', false);
      this.showMask();
      if ((_this$_reloadCall = this._reloadCall) !== null && _this$_reloadCall !== void 0 && _this$_reloadCall.abort) {
        this._reloadCall.abort();
      }
      this._reloadCall = this.client.getFolderContents('trash/' + this.getCurrentDirectory(), {
        includeParent: false,
        properties: this._getWebdavProperties()
      });
      var callBack = this.reloadCallback.bind(this);
      return this._reloadCall.then(callBack, callBack);
    },
    reloadCallback: function reloadCallback(status, result) {
      delete this._reloadCall;
      this.hideMask();
      if (status === 401) {
        return false;
      }

      // Firewall Blocked request?
      if (status === 403) {
        // Go home
        this.changeDirectory('/');
        OC.Notification.show(t('files', 'This operation is forbidden'));
        return false;
      }

      // Did share service die or something else fail?
      if (status === 500) {
        // Go home
        this.changeDirectory('/');
        OC.Notification.show(t('files', 'This directory is unavailable, please check the logs or contact the administrator'));
        return false;
      }
      if (status === 404) {
        // go back home
        this.changeDirectory('/');
        return false;
      }
      // aborted ?
      if (status === 0) {
        return true;
      }
      this.setFiles(result);
      return true;
    }
  });
  OCA.Trashbin.FileList = FileList;
})();

/***/ }),

/***/ "./apps/files_trashbin/src/files_trashbin.js":
/*!***************************************************!*\
  !*** ./apps/files_trashbin/src/files_trashbin.js ***!
  \***************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _app__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./app */ "./apps/files_trashbin/src/app.js");
/* harmony import */ var _app__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_app__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _filelist__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./filelist */ "./apps/files_trashbin/src/filelist.js");
/* harmony import */ var _filelist__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_filelist__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _trash_scss__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./trash.scss */ "./apps/files_trashbin/src/trash.scss");
/**
 * @copyright Copyright (c) 2016 Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @license AGPL-3.0-or-later
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */




window.OCA.Trashbin = OCA.Trashbin;

/***/ }),

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/sass-loader/dist/cjs.js!./apps/files_trashbin/src/trash.scss":
/*!*************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/sass-loader/dist/cjs.js!./apps/files_trashbin/src/trash.scss ***!
  \*************************************************************************************************************************/
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../../../node_modules/css-loader/dist/runtime/noSourceMaps.js */ "./node_modules/css-loader/dist/runtime/noSourceMaps.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../../../node_modules/css-loader/dist/runtime/api.js */ "./node_modules/css-loader/dist/runtime/api.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__);
// Imports


var ___CSS_LOADER_EXPORT___ = _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default()((_node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default()));
// Module
___CSS_LOADER_EXPORT___.push([module.id, "/*\n * Copyright (c) 2014\n *\n * This file is licensed under the Affero General Public License version 3\n * or later.\n *\n * See the COPYING-README file.\n *\n */\n#app-content-trashbin tbody tr[data-type=file] td a.name,\n#app-content-trashbin tbody tr[data-type=file] td a.name span.nametext,\n#app-content-trashbin tbody tr[data-type=file] td a.name span.nametext span {\n  cursor: default;\n}\n\n#app-content-trashbin .summary :last-child {\n  padding: 0;\n}\n\n#app-content-trashbin .files-filestable .summary .filesize {\n  display: none;\n}", ""]);
// Exports
/* harmony default export */ __webpack_exports__["default"] = (___CSS_LOADER_EXPORT___);


/***/ }),

/***/ "./apps/files_trashbin/src/trash.scss":
/*!********************************************!*\
  !*** ./apps/files_trashbin/src/trash.scss ***!
  \********************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! !../../../node_modules/style-loader/dist/runtime/injectStylesIntoStyleTag.js */ "./node_modules/style-loader/dist/runtime/injectStylesIntoStyleTag.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! !../../../node_modules/style-loader/dist/runtime/styleDomAPI.js */ "./node_modules/style-loader/dist/runtime/styleDomAPI.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! !../../../node_modules/style-loader/dist/runtime/insertBySelector.js */ "./node_modules/style-loader/dist/runtime/insertBySelector.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../node_modules/style-loader/dist/runtime/setAttributesWithoutAttributes.js */ "./node_modules/style-loader/dist/runtime/setAttributesWithoutAttributes.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! !../../../node_modules/style-loader/dist/runtime/insertStyleElement.js */ "./node_modules/style-loader/dist/runtime/insertStyleElement.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! !../../../node_modules/style-loader/dist/runtime/styleTagTransform.js */ "./node_modules/style-loader/dist/runtime/styleTagTransform.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_sass_loader_dist_cjs_js_trash_scss__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../../node_modules/css-loader/dist/cjs.js!../../../node_modules/sass-loader/dist/cjs.js!./trash.scss */ "./node_modules/css-loader/dist/cjs.js!./node_modules/sass-loader/dist/cjs.js!./apps/files_trashbin/src/trash.scss");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());

      options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
    
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_sass_loader_dist_cjs_js_trash_scss__WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ __webpack_exports__["default"] = (_node_modules_css_loader_dist_cjs_js_node_modules_sass_loader_dist_cjs_js_trash_scss__WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_sass_loader_dist_cjs_js_trash_scss__WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_sass_loader_dist_cjs_js_trash_scss__WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ })

/******/ 	});
/************************************************************************/
/******/ 	// The module cache
/******/ 	var __webpack_module_cache__ = {};
/******/ 	
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/ 		// Check if module is in cache
/******/ 		var cachedModule = __webpack_module_cache__[moduleId];
/******/ 		if (cachedModule !== undefined) {
/******/ 			return cachedModule.exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = __webpack_module_cache__[moduleId] = {
/******/ 			id: moduleId,
/******/ 			loaded: false,
/******/ 			exports: {}
/******/ 		};
/******/ 	
/******/ 		// Execute the module function
/******/ 		__webpack_modules__[moduleId].call(module.exports, module, module.exports, __webpack_require__);
/******/ 	
/******/ 		// Flag the module as loaded
/******/ 		module.loaded = true;
/******/ 	
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/ 	
/******/ 	// expose the modules object (__webpack_modules__)
/******/ 	__webpack_require__.m = __webpack_modules__;
/******/ 	
/************************************************************************/
/******/ 	/* webpack/runtime/chunk loaded */
/******/ 	!function() {
/******/ 		var deferred = [];
/******/ 		__webpack_require__.O = function(result, chunkIds, fn, priority) {
/******/ 			if(chunkIds) {
/******/ 				priority = priority || 0;
/******/ 				for(var i = deferred.length; i > 0 && deferred[i - 1][2] > priority; i--) deferred[i] = deferred[i - 1];
/******/ 				deferred[i] = [chunkIds, fn, priority];
/******/ 				return;
/******/ 			}
/******/ 			var notFulfilled = Infinity;
/******/ 			for (var i = 0; i < deferred.length; i++) {
/******/ 				var chunkIds = deferred[i][0];
/******/ 				var fn = deferred[i][1];
/******/ 				var priority = deferred[i][2];
/******/ 				var fulfilled = true;
/******/ 				for (var j = 0; j < chunkIds.length; j++) {
/******/ 					if ((priority & 1 === 0 || notFulfilled >= priority) && Object.keys(__webpack_require__.O).every(function(key) { return __webpack_require__.O[key](chunkIds[j]); })) {
/******/ 						chunkIds.splice(j--, 1);
/******/ 					} else {
/******/ 						fulfilled = false;
/******/ 						if(priority < notFulfilled) notFulfilled = priority;
/******/ 					}
/******/ 				}
/******/ 				if(fulfilled) {
/******/ 					deferred.splice(i--, 1)
/******/ 					var r = fn();
/******/ 					if (r !== undefined) result = r;
/******/ 				}
/******/ 			}
/******/ 			return result;
/******/ 		};
/******/ 	}();
/******/ 	
/******/ 	/* webpack/runtime/compat get default export */
/******/ 	!function() {
/******/ 		// getDefaultExport function for compatibility with non-harmony modules
/******/ 		__webpack_require__.n = function(module) {
/******/ 			var getter = module && module.__esModule ?
/******/ 				function() { return module['default']; } :
/******/ 				function() { return module; };
/******/ 			__webpack_require__.d(getter, { a: getter });
/******/ 			return getter;
/******/ 		};
/******/ 	}();
/******/ 	
/******/ 	/* webpack/runtime/define property getters */
/******/ 	!function() {
/******/ 		// define getter functions for harmony exports
/******/ 		__webpack_require__.d = function(exports, definition) {
/******/ 			for(var key in definition) {
/******/ 				if(__webpack_require__.o(definition, key) && !__webpack_require__.o(exports, key)) {
/******/ 					Object.defineProperty(exports, key, { enumerable: true, get: definition[key] });
/******/ 				}
/******/ 			}
/******/ 		};
/******/ 	}();
/******/ 	
/******/ 	/* webpack/runtime/global */
/******/ 	!function() {
/******/ 		__webpack_require__.g = (function() {
/******/ 			if (typeof globalThis === 'object') return globalThis;
/******/ 			try {
/******/ 				return this || new Function('return this')();
/******/ 			} catch (e) {
/******/ 				if (typeof window === 'object') return window;
/******/ 			}
/******/ 		})();
/******/ 	}();
/******/ 	
/******/ 	/* webpack/runtime/hasOwnProperty shorthand */
/******/ 	!function() {
/******/ 		__webpack_require__.o = function(obj, prop) { return Object.prototype.hasOwnProperty.call(obj, prop); }
/******/ 	}();
/******/ 	
/******/ 	/* webpack/runtime/make namespace object */
/******/ 	!function() {
/******/ 		// define __esModule on exports
/******/ 		__webpack_require__.r = function(exports) {
/******/ 			if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 				Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 			}
/******/ 			Object.defineProperty(exports, '__esModule', { value: true });
/******/ 		};
/******/ 	}();
/******/ 	
/******/ 	/* webpack/runtime/node module decorator */
/******/ 	!function() {
/******/ 		__webpack_require__.nmd = function(module) {
/******/ 			module.paths = [];
/******/ 			if (!module.children) module.children = [];
/******/ 			return module;
/******/ 		};
/******/ 	}();
/******/ 	
/******/ 	/* webpack/runtime/jsonp chunk loading */
/******/ 	!function() {
/******/ 		__webpack_require__.b = document.baseURI || self.location.href;
/******/ 		
/******/ 		// object to store loaded and loading chunks
/******/ 		// undefined = chunk not loaded, null = chunk preloaded/prefetched
/******/ 		// [resolve, reject, Promise] = chunk loading, 0 = chunk loaded
/******/ 		var installedChunks = {
/******/ 			"files_trashbin-files_trashbin": 0
/******/ 		};
/******/ 		
/******/ 		// no chunk on demand loading
/******/ 		
/******/ 		// no prefetching
/******/ 		
/******/ 		// no preloaded
/******/ 		
/******/ 		// no HMR
/******/ 		
/******/ 		// no HMR manifest
/******/ 		
/******/ 		__webpack_require__.O.j = function(chunkId) { return installedChunks[chunkId] === 0; };
/******/ 		
/******/ 		// install a JSONP callback for chunk loading
/******/ 		var webpackJsonpCallback = function(parentChunkLoadingFunction, data) {
/******/ 			var chunkIds = data[0];
/******/ 			var moreModules = data[1];
/******/ 			var runtime = data[2];
/******/ 			// add "moreModules" to the modules object,
/******/ 			// then flag all "chunkIds" as loaded and fire callback
/******/ 			var moduleId, chunkId, i = 0;
/******/ 			if(chunkIds.some(function(id) { return installedChunks[id] !== 0; })) {
/******/ 				for(moduleId in moreModules) {
/******/ 					if(__webpack_require__.o(moreModules, moduleId)) {
/******/ 						__webpack_require__.m[moduleId] = moreModules[moduleId];
/******/ 					}
/******/ 				}
/******/ 				if(runtime) var result = runtime(__webpack_require__);
/******/ 			}
/******/ 			if(parentChunkLoadingFunction) parentChunkLoadingFunction(data);
/******/ 			for(;i < chunkIds.length; i++) {
/******/ 				chunkId = chunkIds[i];
/******/ 				if(__webpack_require__.o(installedChunks, chunkId) && installedChunks[chunkId]) {
/******/ 					installedChunks[chunkId][0]();
/******/ 				}
/******/ 				installedChunks[chunkId] = 0;
/******/ 			}
/******/ 			return __webpack_require__.O(result);
/******/ 		}
/******/ 		
/******/ 		var chunkLoadingGlobal = self["webpackChunknextcloud"] = self["webpackChunknextcloud"] || [];
/******/ 		chunkLoadingGlobal.forEach(webpackJsonpCallback.bind(null, 0));
/******/ 		chunkLoadingGlobal.push = webpackJsonpCallback.bind(null, chunkLoadingGlobal.push.bind(chunkLoadingGlobal));
/******/ 	}();
/******/ 	
/******/ 	/* webpack/runtime/nonce */
/******/ 	!function() {
/******/ 		__webpack_require__.nc = undefined;
/******/ 	}();
/******/ 	
/************************************************************************/
/******/ 	
/******/ 	// startup
/******/ 	// Load entry module and return exports
/******/ 	// This entry module depends on other loaded chunks and execution need to be delayed
/******/ 	var __webpack_exports__ = __webpack_require__.O(undefined, ["core-common"], function() { return __webpack_require__("./apps/files_trashbin/src/files_trashbin.js"); })
/******/ 	__webpack_exports__ = __webpack_require__.O(__webpack_exports__);
/******/ 	
/******/ })()
;
//# sourceMappingURL=files_trashbin-files_trashbin.js.map?v=94703c5c7b03b44c4e3a