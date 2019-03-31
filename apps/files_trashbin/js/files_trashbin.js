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
/******/ 	return __webpack_require__(__webpack_require__.s = "./apps/files_trashbin/src/files_trashbin.js");
/******/ })
/************************************************************************/
/******/ ({

/***/ "./apps/files_trashbin/src/app.js":
/*!****************************************!*\
  !*** ./apps/files_trashbin/src/app.js ***!
  \****************************************/
/*! no static exports found */
/***/ (function(module, exports) {

/*
 * Copyright (c) 2014
 *
 * This file is licensed under the Affero General Public License version 3
 * or later.
 *
 * See the COPYING-README file.
 *
 */

/**
 * @namespace OCA.Trashbin
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
          OC.Notification.show(t('files_trashbin', 'Error while restoring file from trashbin'));
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
          OC.Notification.show(t('files_trashbin', 'Error while removing file from trashbin'));
        });
      }
    });
    return fileActions;
  }
};
$(document).ready(function () {
  $('#app-content-trashbin').one('show', function () {
    var App = OCA.Trashbin.App;
    App.initialize($('#app-content-trashbin')); // force breadcrumb init
    // App.fileList.changeDirectory(App.fileList.getCurrentDirectory(), false, true);
  });
});

/***/ }),

/***/ "./apps/files_trashbin/src/filelist.js":
/*!*********************************************!*\
  !*** ./apps/files_trashbin/src/filelist.js ***!
  \*********************************************/
/*! no static exports found */
/***/ (function(module, exports) {

/*
 * Copyright (c) 2014
 *
 * This file is licensed under the Affero General Public License version 3
 * or later.
 *
 * See the COPYING-README file.
 *
 */
(function () {
  var DELETED_REGEXP = new RegExp(/^(.+)\.d[0-9]+$/);
  var FILENAME_PROP = '{http://nextcloud.org/ns}trashbin-filename';
  var DELETION_TIME_PROP = '{http://nextcloud.org/ns}trashbin-deletion-time';
  var TRASHBIN_ORIGINAL_LOCATION = '{http://nextcloud.org/ns}trashbin-original-location';
  /**
   * Convert a file name in the format filename.d12345 to the real file name.
   * This will use basename.
   * The name will not be changed if it has no ".d12345" suffix.
   * @param {String} name file name
   * @return {String} converted file name
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
   * @param $el container element with existing markup for the #controls
   * and a table
   * @param [options] map of options
   */


  var FileList = function FileList($el, options) {
    this.client = options.client;
    this.initialize($el, options);
  };

  FileList.prototype = _.extend({}, OCA.Files.FileList.prototype,
  /** @lends OCA.Trashbin.FileList.prototype */
  {
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
        return {
          displayName: props[FILENAME_PROP],
          mtime: parseInt(props[DELETION_TIME_PROP], 10) * 1000,
          hasPreview: true,
          path: path,
          extraData: path
        };
      });
      var result = OCA.Files.FileList.prototype.initialize.apply(this, arguments);
      this.$el.find('.undelete').click('click', _.bind(this._onClickRestoreSelected, this));
      this.setSort('mtime', 'desc');
      /**
       * Override crumb making to add "Deleted Files" entry
       * and convert files with ".d" extensions to a more
       * user friendly name.
       */

      this.breadcrumb._makeCrumbs = function () {
        var parts = OCA.Files.BreadCrumb.prototype._makeCrumbs.apply(this, arguments);

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
    setupUploadEvents: function setupUploadEvents() {// override and do nothing
    },
    linkTo: function linkTo(dir) {
      return OC.linkTo('files', 'index.php') + "?view=trashbin&dir=" + encodeURIComponent(dir).replace(/%2F/g, '/');
    },
    elementToFile: function elementToFile($el) {
      var fileInfo = OCA.Files.FileList.prototype.elementToFile($el);

      if (this.getCurrentDirectory() === '/') {
        fileInfo.displayName = getDeletedFileName(fileInfo.name);
      } // no size available


      delete fileInfo.size;
      return fileInfo;
    },
    updateEmptyContent: function updateEmptyContent() {
      var exists = this.$fileList.find('tr:first').exists();
      this.$el.find('#emptycontent').toggleClass('hidden', exists);
      this.$el.find('#filestable th').toggleClass('hidden', !exists);
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
        OC.Notification.show(t('files_trashbin', 'Error while restoring files from trashbin'));
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
          OC.Notification.show(t('files_trashbin', 'Error while emptying trashbin'));
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
          OC.Notification.show(t('files_trashbin', 'Error while removing files from trashbin'));
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
    updateStorageStatistics: function updateStorageStatistics() {// no op because the trashbin doesn't have
      // storage info like free space / used space
    },
    isSelectedDeletable: function isSelectedDeletable() {
      return true;
    },

    /**
     * Returns list of webdav properties to request
     */
    _getWebdavProperties: function _getWebdavProperties() {
      return [FILENAME_PROP, DELETION_TIME_PROP, TRASHBIN_ORIGINAL_LOCATION].concat(this.filesClient.getPropfindProperties());
    },

    /**
     * Reloads the file list using ajax call
     *
     * @return ajax call object
     */
    reload: function reload() {
      this._selectedFiles = {};

      this._selectionSummary.clear();

      this.$el.find('.select-all').prop('checked', false);
      this.showMask();

      if (this._reloadCall) {
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
      } // Firewall Blocked request?


      if (status === 403) {
        // Go home
        this.changeDirectory('/');
        OC.Notification.show(t('files', 'This operation is forbidden'));
        return false;
      } // Did share service die or something else fail?


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
      } // aborted ?


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
/*! no exports provided */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _app__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./app */ "./apps/files_trashbin/src/app.js");
/* harmony import */ var _app__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_app__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _filelist__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./filelist */ "./apps/files_trashbin/src/filelist.js");
/* harmony import */ var _filelist__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_filelist__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _trash_scss__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./trash.scss */ "./apps/files_trashbin/src/trash.scss");
/* harmony import */ var _trash_scss__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_trash_scss__WEBPACK_IMPORTED_MODULE_2__);



window.OCA.Trashbin = OCA.Trashbin;

/***/ }),

/***/ "./apps/files_trashbin/src/trash.scss":
/*!********************************************!*\
  !*** ./apps/files_trashbin/src/trash.scss ***!
  \********************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {


var content = __webpack_require__(/*! !../../../node_modules/css-loader/dist/cjs.js!../../../node_modules/sass-loader/lib/loader.js!./trash.scss */ "./node_modules/css-loader/dist/cjs.js!./node_modules/sass-loader/lib/loader.js!./apps/files_trashbin/src/trash.scss");

if(typeof content === 'string') content = [[module.i, content, '']];

var transform;
var insertInto;



var options = {"hmr":true}

options.transform = transform
options.insertInto = undefined;

var update = __webpack_require__(/*! ../../../node_modules/style-loader/lib/addStyles.js */ "./node_modules/style-loader/lib/addStyles.js")(content, options);

if(content.locals) module.exports = content.locals;

if(false) {}

/***/ }),

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/sass-loader/lib/loader.js!./apps/files_trashbin/src/trash.scss":
/*!***************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/sass-loader/lib/loader.js!./apps/files_trashbin/src/trash.scss ***!
  \***************************************************************************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

exports = module.exports = __webpack_require__(/*! ../../../node_modules/css-loader/dist/runtime/api.js */ "./node_modules/css-loader/dist/runtime/api.js")(false);
// Module
exports.push([module.i, "/*\n * Copyright (c) 2014\n *\n * This file is licensed under the Affero General Public License version 3\n * or later.\n *\n * See the COPYING-README file.\n *\n */\n#app-content-trashbin tbody tr[data-type=\"file\"] td a.name,\n#app-content-trashbin tbody tr[data-type=\"file\"] td a.name span.nametext,\n#app-content-trashbin tbody tr[data-type=\"file\"] td a.name span.nametext span {\n  cursor: default; }\n\n#app-content-trashbin .summary :last-child {\n  padding: 0; }\n\n#app-content-trashbin #filestable .summary .filesize {\n  display: none; }\n", ""]);



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
//# sourceMappingURL=files_trashbin.js.map