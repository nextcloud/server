/******/ (function() { // webpackBootstrap
/******/ 	var __webpack_modules__ = ({

/***/ "./core/src/systemtags/merged-systemtags.js":
/*!**************************************************!*\
  !*** ./core/src/systemtags/merged-systemtags.js ***!
  \**************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _systemtags_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./systemtags.js */ "./core/src/systemtags/systemtags.js");
/* harmony import */ var _systemtagmodel_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./systemtagmodel.js */ "./core/src/systemtags/systemtagmodel.js");
/* harmony import */ var _systemtagmodel_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_systemtagmodel_js__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _systemtagsmappingcollection_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./systemtagsmappingcollection.js */ "./core/src/systemtags/systemtagsmappingcollection.js");
/* harmony import */ var _systemtagscollection_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./systemtagscollection.js */ "./core/src/systemtags/systemtagscollection.js");
/* harmony import */ var _systemtagscollection_js__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_systemtagscollection_js__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _systemtagsinputfield_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./systemtagsinputfield.js */ "./core/src/systemtags/systemtagsinputfield.js");
/* harmony import */ var _css_systemtags_scss__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ../../css/systemtags.scss */ "./core/css/systemtags.scss");
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








/***/ }),

/***/ "./core/src/systemtags/systemtagmodel.js":
/*!***********************************************!*\
  !*** ./core/src/systemtags/systemtagmodel.js ***!
  \***********************************************/
/***/ (function() {

/**
 * Copyright (c) 2015
 *
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 * @author Michael Jobst <mjobst+github@tecratech.de>
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

(function (OC, _OC$Files) {
  if (OC !== null && OC !== void 0 && (_OC$Files = OC.Files) !== null && _OC$Files !== void 0 && _OC$Files.Client) {
    _.extend(OC.Files.Client, {
      PROPERTY_FILEID: '{' + OC.Files.Client.NS_OWNCLOUD + '}id',
      PROPERTY_CAN_ASSIGN: '{' + OC.Files.Client.NS_OWNCLOUD + '}can-assign',
      PROPERTY_DISPLAYNAME: '{' + OC.Files.Client.NS_OWNCLOUD + '}display-name',
      PROPERTY_USERVISIBLE: '{' + OC.Files.Client.NS_OWNCLOUD + '}user-visible',
      PROPERTY_USERASSIGNABLE: '{' + OC.Files.Client.NS_OWNCLOUD + '}user-assignable'
    });

    /**
     * @class OCA.SystemTags.SystemTagsCollection
     * @classdesc
     *
     * System tag
     *
     */
    var SystemTagModel = OC.Backbone.Model.extend( /** @lends OCA.SystemTags.SystemTagModel.prototype */{
      sync: OC.Backbone.davSync,
      defaults: {
        userVisible: true,
        userAssignable: true,
        canAssign: true
      },
      davProperties: {
        id: OC.Files.Client.PROPERTY_FILEID,
        name: OC.Files.Client.PROPERTY_DISPLAYNAME,
        userVisible: OC.Files.Client.PROPERTY_USERVISIBLE,
        userAssignable: OC.Files.Client.PROPERTY_USERASSIGNABLE,
        // read-only, effective permissions computed by the server,
        canAssign: OC.Files.Client.PROPERTY_CAN_ASSIGN
      },
      parse: function parse(data) {
        return {
          id: data.id,
          name: data.name,
          userVisible: data.userVisible === true || data.userVisible === 'true',
          userAssignable: data.userAssignable === true || data.userAssignable === 'true',
          canAssign: data.canAssign === true || data.canAssign === 'true'
        };
      }
    });
    OC.SystemTags = OC.SystemTags || {};
    OC.SystemTags.SystemTagModel = SystemTagModel;
  }
})(OC);

/***/ }),

/***/ "./core/src/systemtags/systemtags.js":
/*!*******************************************!*\
  !*** ./core/src/systemtags/systemtags.js ***!
  \*******************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var escape_html__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! escape-html */ "./node_modules/escape-html/index.js");
/* harmony import */ var escape_html__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(escape_html__WEBPACK_IMPORTED_MODULE_0__);
/**
 * Copyright (c) 2016
 *
 * @author Gary Kim <gary@garykim.dev>
 * @author Joas Schilling <coding@schilljs.com>
 * @author John Molakvoæ <skjnldsv@protonmail.com>
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

/* eslint-disable */

(function (OC) {
  /**
   * @namespace
   */
  OC.SystemTags = {
    /**
     *
     * @param {OC.SystemTags.SystemTagModel|Object|String} tag
     * @returns {HTMLElement}
     */
    getDescriptiveTag: function getDescriptiveTag(tag) {
      if (_.isUndefined(tag.name) && !_.isUndefined(tag.toJSON)) {
        tag = tag.toJSON();
      }
      var $span = document.createElement('span');
      if (_.isUndefined(tag.name)) {
        $span.classList.add('non-existing-tag');
        $span.textContent = t('core', 'Non-existing tag #{tag}', {
          tag: tag
        });
        return $span;
      }
      $span.textContent = escape_html__WEBPACK_IMPORTED_MODULE_0___default()(tag.name);
      var scope;
      if (!tag.userAssignable) {
        scope = t('core', 'Restricted');
      }
      if (!tag.userVisible) {
        // invisible also implicitly means not assignable
        scope = t('core', 'Invisible');
      }
      if (scope) {
        var $scope = document.createElement('em');
        $scope.textContent = ' (' + scope + ')';
        $span.appendChild($scope);
      }
      return $span;
    }
  };
})(OC);

/***/ }),

/***/ "./core/src/systemtags/systemtagscollection.js":
/*!*****************************************************!*\
  !*** ./core/src/systemtags/systemtagscollection.js ***!
  \*****************************************************/
/***/ (function() {

/**
 * Copyright (c) 2015
 *
 * @author John Molakvoæ <skjnldsv@protonmail.com>
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
(function (OC) {
  function filterFunction(model, term) {
    return model.get('name').substr(0, term.length).toLowerCase() === term.toLowerCase();
  }

  /**
   * @class OCA.SystemTags.SystemTagsCollection
   * @classdesc
   *
   * Collection of tags assigned to a file
   *
   */
  var SystemTagsCollection = OC.Backbone.Collection.extend( /** @lends OC.SystemTags.SystemTagsCollection.prototype */{
    sync: OC.Backbone.davSync,
    model: OC.SystemTags.SystemTagModel,
    url: function url() {
      return OC.linkToRemote('dav') + '/systemtags/';
    },
    filterByName: function filterByName(name) {
      return this.filter(function (model) {
        return filterFunction(model, name);
      });
    },
    reset: function reset() {
      this.fetched = false;
      return OC.Backbone.Collection.prototype.reset.apply(this, arguments);
    },
    /**
    * Lazy fetch.
    * Only fetches once, subsequent calls will directly call the success handler.
    *
    * @param {any} options -
    * @param [options.force] true to force fetch even if cached entries exist
    *
    * @see Backbone.Collection#fetch
    */
    fetch: function fetch(options) {
      var self = this;
      options = options || {};
      if (this.fetched || this.working || options.force) {
        // directly call handler
        if (options.success) {
          options.success(this, null, options);
        }
        // trigger sync event
        this.trigger('sync', this, null, options);
        return Promise.resolve();
      }
      this.working = true;
      var success = options.success;
      options = _.extend({}, options);
      options.success = function () {
        self.fetched = true;
        self.working = false;
        if (success) {
          return success.apply(this, arguments);
        }
      };
      return OC.Backbone.Collection.prototype.fetch.call(this, options);
    }
  });
  OC.SystemTags = OC.SystemTags || {};
  OC.SystemTags.SystemTagsCollection = SystemTagsCollection;

  /**
   * @type OC.SystemTags.SystemTagsCollection
   */
  OC.SystemTags.collection = new OC.SystemTags.SystemTagsCollection();
})(OC);

/***/ }),

/***/ "./core/src/systemtags/systemtagsinputfield.js":
/*!*****************************************************!*\
  !*** ./core/src/systemtags/systemtagsinputfield.js ***!
  \*****************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _templates_result_handlebars__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./templates/result.handlebars */ "./core/src/systemtags/templates/result.handlebars");
/* harmony import */ var _templates_result_handlebars__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_templates_result_handlebars__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _templates_result_form_handlebars__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./templates/result_form.handlebars */ "./core/src/systemtags/templates/result_form.handlebars");
/* harmony import */ var _templates_result_form_handlebars__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_templates_result_form_handlebars__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _templates_selection_handlebars__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./templates/selection.handlebars */ "./core/src/systemtags/templates/selection.handlebars");
/* harmony import */ var _templates_selection_handlebars__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_templates_selection_handlebars__WEBPACK_IMPORTED_MODULE_2__);
/**
 * Copyright (c) 2015
 *
 * @author Joas Schilling <coding@schilljs.com>
 * @author John Molakvoæ <skjnldsv@protonmail.com>
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

/* eslint-disable */



(function (OC) {
  /**
   * @class OC.SystemTags.SystemTagsInputField
   * @classdesc
   *
   * Displays a file's system tags
   *
   */
  var SystemTagsInputField = OC.Backbone.View.extend( /** @lends OC.SystemTags.SystemTagsInputField.prototype */{
    _rendered: false,
    _newTag: null,
    _lastUsedTags: [],
    className: 'systemTagsInputFieldContainer',
    template: function template(data) {
      return '<input class="systemTagsInputField" type="hidden" name="tags" value=""/>';
    },
    /**
    * Creates a new SystemTagsInputField
    *
    * @param {Object} [options]
    * @param {string} [options.objectType=files] object type for which tags are assigned to
    * @param {boolean} [options.multiple=false] whether to allow selecting multiple tags
    * @param {boolean} [options.allowActions=true] whether tags can be renamed/delete within the dropdown
    * @param {boolean} [options.allowCreate=true] whether new tags can be created
    * @param {boolean} [options.isAdmin=true] whether the user is an administrator
    * @param {Function} options.initSelection function to convert selection to data
    */
    initialize: function initialize(options) {
      options = options || {};
      this._multiple = !!options.multiple;
      this._allowActions = _.isUndefined(options.allowActions) || !!options.allowActions;
      this._allowCreate = _.isUndefined(options.allowCreate) || !!options.allowCreate;
      this._isAdmin = !!options.isAdmin;
      if (_.isFunction(options.initSelection)) {
        this._initSelection = options.initSelection;
      }
      this.collection = options.collection || OC.SystemTags.collection;
      var self = this;
      this.collection.on('change:name remove', function () {
        // refresh selection
        _.defer(self._refreshSelection);
      });
      _.defer(_.bind(this._getLastUsedTags, this));
      _.bindAll(this, '_refreshSelection', '_onClickRenameTag', '_onClickDeleteTag', '_onSelectTag', '_onDeselectTag', '_onSubmitRenameTag');
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
    /**
    * Refreshes the selection, triggering a call to
    * select2's initSelection
    */
    _refreshSelection: function _refreshSelection() {
      this.$tagsField.select2('val', this.$tagsField.val());
    },
    /**
    * Event handler whenever the user clicked the "rename" action.
    * This will display the rename field.
    */
    _onClickRenameTag: function _onClickRenameTag(ev) {
      var $item = $(ev.target).closest('.systemtags-item');
      var tagId = $item.attr('data-id');
      var tagModel = this.collection.get(tagId);
      var oldName = tagModel.get('name');
      var $renameForm = $(_templates_result_form_handlebars__WEBPACK_IMPORTED_MODULE_1___default()({
        cid: this.cid,
        name: oldName,
        deleteTooltip: t('core', 'Delete'),
        renameLabel: t('core', 'Rename'),
        isAdmin: this._isAdmin
      }));
      $item.find('.label').after($renameForm);
      $item.find('.label, .systemtags-actions').addClass('hidden');
      $item.closest('.select2-result').addClass('has-form');
      $renameForm.find('[title]').tooltip({
        placement: 'bottom',
        container: 'body'
      });
      $renameForm.find('input').focus().selectRange(0, oldName.length);
      return false;
    },
    /**
    * Event handler whenever the rename form has been submitted after
    * the user entered a new tag name.
    * This will submit the change to the server.
    *
    * @param {Object} ev event
    */
    _onSubmitRenameTag: function _onSubmitRenameTag(ev) {
      ev.preventDefault();
      var $form = $(ev.target);
      var $item = $form.closest('.systemtags-item');
      var tagId = $item.attr('data-id');
      var tagModel = this.collection.get(tagId);
      var newName = $(ev.target).find('input').val().trim();
      if (newName && newName !== tagModel.get('name')) {
        tagModel.save({
          'name': newName
        });
        // TODO: spinner, and only change text after finished saving
        $item.find('.label').text(newName);
      }
      $item.find('.label, .systemtags-actions').removeClass('hidden');
      $form.remove();
      $item.closest('.select2-result').removeClass('has-form');
    },
    /**
    * Event handler whenever a tag must be deleted
    *
    * @param {Object} ev event
    */
    _onClickDeleteTag: function _onClickDeleteTag(ev) {
      var $item = $(ev.target).closest('.systemtags-item');
      var tagId = $item.attr('data-id');
      this.collection.get(tagId).destroy();
      $(ev.target).tooltip('hide');
      $item.closest('.select2-result').remove();
      // TODO: spinner
      return false;
    },
    _addToSelect2Selection: function _addToSelect2Selection(selection) {
      var data = this.$tagsField.select2('data');
      data.push(selection);
      this.$tagsField.select2('data', data);
    },
    /**
    * Event handler whenever a tag is selected.
    * Also called whenever tag creation is requested through the dummy tag object.
    *
    * @param {Object} e event
    */
    _onSelectTag: function _onSelectTag(e) {
      var self = this;
      var tag;
      if (e.object && e.object.isNew) {
        // newly created tag, check if existing
        // create a new tag
        tag = this.collection.create({
          name: e.object.name.trim(),
          userVisible: true,
          userAssignable: true,
          canAssign: true
        }, {
          success: function success(model) {
            self._addToSelect2Selection(model.toJSON());
            self._lastUsedTags.unshift(model.id);
            self.trigger('select', model);
          },
          error: function error(model, xhr) {
            if (xhr.status === 409) {
              // re-fetch collection to get the missing tag
              self.collection.reset();
              self.collection.fetch({
                success: function success(collection) {
                  // find the tag in the collection
                  var model = collection.where({
                    name: e.object.name.trim(),
                    userVisible: true,
                    userAssignable: true
                  });
                  if (model.length) {
                    model = model[0];
                    // the tag already exists or was already assigned,
                    // add it to the list anyway
                    self._addToSelect2Selection(model.toJSON());
                    self.trigger('select', model);
                  }
                }
              });
            }
          }
        });
        this.$tagsField.select2('close');
        e.preventDefault();
        return false;
      } else {
        tag = this.collection.get(e.object.id);
        this._lastUsedTags.unshift(tag.id);
      }
      this._newTag = null;
      this.trigger('select', tag);
    },
    /**
    * Event handler whenever a tag gets deselected.
    *
    * @param {Object} e event
    */
    _onDeselectTag: function _onDeselectTag(e) {
      this.trigger('deselect', e.choice.id);
    },
    /**
    * Autocomplete function for dropdown results
    *
    * @param {Object} query select2 query object
    */
    _queryTagsAutocomplete: function _queryTagsAutocomplete(query) {
      var self = this;
      this.collection.fetch({
        success: function success(collection) {
          var tagModels = collection.filterByName(query.term.trim());
          if (!self._isAdmin) {
            tagModels = _.filter(tagModels, function (tagModel) {
              return tagModel.get('canAssign');
            });
          }
          query.callback({
            results: _.invoke(tagModels, 'toJSON')
          });
        }
      });
    },
    _preventDefault: function _preventDefault(e) {
      e.stopPropagation();
    },
    /**
    * Formats a single dropdown result
    *
    * @param {Object} data data to format
    * @returns {string} HTML markup
    */
    _formatDropDownResult: function _formatDropDownResult(data) {
      return _templates_result_handlebars__WEBPACK_IMPORTED_MODULE_0___default()(_.extend({
        renameTooltip: t('core', 'Rename'),
        allowActions: this._allowActions,
        tagMarkup: this._isAdmin ? OC.SystemTags.getDescriptiveTag(data).innerHTML : null,
        isAdmin: this._isAdmin
      }, data));
    },
    /**
    * Formats a single selection item
    *
    * @param {Object} data data to format
    * @returns {string} HTML markup
    */
    _formatSelection: function _formatSelection(data) {
      return _templates_selection_handlebars__WEBPACK_IMPORTED_MODULE_2___default()(_.extend({
        tagMarkup: this._isAdmin ? OC.SystemTags.getDescriptiveTag(data).innerHTML : null,
        isAdmin: this._isAdmin
      }, data));
    },
    /**
    * Create new dummy choice for select2 when the user
    * types an arbitrary string
    *
    * @param {string} term entered term
    * @returns {Object} dummy tag
    */
    _createSearchChoice: function _createSearchChoice(term) {
      term = term.trim();
      if (this.collection.filter(function (entry) {
        return entry.get('name') === term;
      }).length) {
        return;
      }
      if (!this._newTag) {
        this._newTag = {
          id: -1,
          name: term,
          userAssignable: true,
          userVisible: true,
          canAssign: true,
          isNew: true
        };
      } else {
        this._newTag.name = term;
      }
      return this._newTag;
    },
    _initSelection: function _initSelection(element, callback) {
      var self = this;
      var ids = $(element).val().split(',');
      function modelToSelection(model) {
        var data = model.toJSON();
        if (!self._isAdmin && !data.canAssign) {
          // lock static tags for non-admins
          data.locked = true;
        }
        return data;
      }
      function findSelectedObjects(ids) {
        var selectedModels = self.collection.filter(function (model) {
          return ids.indexOf(model.id) >= 0 && (self._isAdmin || model.get('userVisible'));
        });
        return _.map(selectedModels, modelToSelection);
      }
      this.collection.fetch({
        success: function success() {
          callback(findSelectedObjects(ids));
        }
      });
    },
    /**
    * Renders this details view
    */
    render: function render() {
      var self = this;
      this.$el.html(this.template());
      this.$el.find('[title]').tooltip({
        placement: 'bottom'
      });
      this.$tagsField = this.$el.find('[name=tags]');
      this.$tagsField.select2({
        placeholder: t('core', 'Collaborative tags'),
        containerCssClass: 'systemtags-select2-container',
        dropdownCssClass: 'systemtags-select2-dropdown',
        closeOnSelect: false,
        allowClear: false,
        multiple: this._multiple,
        toggleSelect: this._multiple,
        query: _.bind(this._queryTagsAutocomplete, this),
        id: function id(tag) {
          return tag.id;
        },
        initSelection: _.bind(this._initSelection, this),
        formatResult: _.bind(this._formatDropDownResult, this),
        formatSelection: _.bind(this._formatSelection, this),
        createSearchChoice: this._allowCreate ? _.bind(this._createSearchChoice, this) : undefined,
        sortResults: function sortResults(results) {
          var selectedItems = _.pluck(self.$tagsField.select2('data'), 'id');
          results.sort(function (a, b) {
            var aSelected = selectedItems.indexOf(a.id) >= 0;
            var bSelected = selectedItems.indexOf(b.id) >= 0;
            if (aSelected === bSelected) {
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
              }

              // Both not found
              return OC.Util.naturalSortCompare(a.name, b.name);
            }
            if (aSelected && !bSelected) {
              return -1;
            }
            return 1;
          });
          return results;
        },
        formatNoMatches: function formatNoMatches() {
          return t('core', 'No tags found');
        }
      }).on('select2-selecting', this._onSelectTag).on('select2-removing', this._onDeselectTag);
      var $dropDown = this.$tagsField.select2('dropdown');
      // register events for inside the dropdown
      $dropDown.on('mouseup', '.rename', this._onClickRenameTag);
      $dropDown.on('mouseup', '.delete', this._onClickDeleteTag);
      $dropDown.on('mouseup', '.select2-result-selectable.has-form', this._preventDefault);
      $dropDown.on('submit', '.systemtags-rename-form', this._onSubmitRenameTag);
      this.delegateEvents();
    },
    remove: function remove() {
      if (this.$tagsField) {
        this.$tagsField.select2('destroy');
      }
    },
    getValues: function getValues() {
      this.$tagsField.select2('val');
    },
    setValues: function setValues(values) {
      this.$tagsField.select2('val', values);
    },
    setData: function setData(data) {
      this.$tagsField.select2('data', data);
    }
  });
  OC.SystemTags = OC.SystemTags || {};
  OC.SystemTags.SystemTagsInputField = SystemTagsInputField;
})(OC);

/***/ }),

/***/ "./core/src/systemtags/systemtagsmappingcollection.js":
/*!************************************************************!*\
  !*** ./core/src/systemtags/systemtagsmappingcollection.js ***!
  \************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _nextcloud_router__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/router */ "./node_modules/@nextcloud/router/dist/index.js");
/**
 * Copyright (c) 2015
 *
 * @author John Molakvoæ <skjnldsv@protonmail.com>
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


(function (OC) {
  /**
   * @class OC.SystemTags.SystemTagsMappingCollection
   * @classdesc
   *
   * Collection of tags assigned to a an object
   *
   */
  var SystemTagsMappingCollection = OC.Backbone.Collection.extend( /** @lends OC.SystemTags.SystemTagsMappingCollection.prototype */{
    sync: OC.Backbone.davSync,
    /**
     * Use PUT instead of PROPPATCH
     */
    usePUT: true,
    /**
     * Id of the file for which to filter activities by
     *
     * @member int
     */
    _objectId: null,
    /**
     * Type of the object to filter by
     *
     * @member string
     */
    _objectType: 'files',
    model: OC.SystemTags.SystemTagModel,
    url: function url() {
      return (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_0__.generateRemoteUrl)('dav') + '/systemtags-relations/' + this._objectType + '/' + this._objectId;
    },
    /**
     * Sets the object id to filter by or null for all.
     *
     * @param {number} objectId file id or null
     */
    setObjectId: function setObjectId(objectId) {
      this._objectId = objectId;
    },
    /**
     * Sets the object type to filter by or null for all.
     *
     * @param {number} objectType file id or null
     */
    setObjectType: function setObjectType(objectType) {
      this._objectType = objectType;
    },
    initialize: function initialize(models, options) {
      options = options || {};
      if (!_.isUndefined(options.objectId)) {
        this._objectId = options.objectId;
      }
      if (!_.isUndefined(options.objectType)) {
        this._objectType = options.objectType;
      }
    },
    getTagIds: function getTagIds() {
      return this.map(function (model) {
        return model.id;
      });
    }
  });
  OC.SystemTags = OC.SystemTags || {};
  OC.SystemTags.SystemTagsMappingCollection = SystemTagsMappingCollection;
})(OC);

/***/ }),

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/sass-loader/dist/cjs.js!./core/css/systemtags.scss":
/*!***************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/sass-loader/dist/cjs.js!./core/css/systemtags.scss ***!
  \***************************************************************************************************************/
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../../node_modules/css-loader/dist/runtime/noSourceMaps.js */ "./node_modules/css-loader/dist/runtime/noSourceMaps.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../../node_modules/css-loader/dist/runtime/api.js */ "./node_modules/css-loader/dist/runtime/api.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__);
// Imports


var ___CSS_LOADER_EXPORT___ = _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default()((_node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default()));
// Module
___CSS_LOADER_EXPORT___.push([module.id, "@charset \"UTF-8\";\n/**\n * @copyright Copyright (c) 2016, John Molakvoæ <skjnldsv@protonmail.com>\n * @copyright Copyright (c) 2016, Robin Appelman <robin@icewind.nl>\n * @copyright Copyright (c) 2016, Jan-Christoph Borchardt <hey@jancborchardt.net>\n * @copyright Copyright (c) 2016, Vincent Petry <pvince81@owncloud.com>\n * @copyright Copyright (c) 2016, Erik Pellikka <erik@pellikka.org>\n * @copyright Copyright (c) 2015, Vincent Petry <pvince81@owncloud.com>\n *\n * @license GNU AGPL version 3 or any later version\n *\n */\n.systemtags-select2-dropdown .select2-result-label .checkmark {\n  visibility: hidden;\n  margin-left: -5px;\n  margin-right: 5px;\n  padding: 4px;\n}\n.systemtags-select2-dropdown .select2-result-label .new-item .systemtags-actions {\n  display: none;\n}\n.systemtags-select2-dropdown .select2-selected .select2-result-label .checkmark {\n  visibility: visible;\n}\n.systemtags-select2-dropdown .select2-result-label .icon {\n  display: inline-block;\n  opacity: 0.5;\n}\n.systemtags-select2-dropdown .select2-result-label .icon.rename {\n  padding: 4px;\n}\n.systemtags-select2-dropdown .systemtags-actions {\n  position: absolute;\n  right: 5px;\n}\n.systemtags-select2-dropdown .systemtags-rename-form {\n  display: inline-block;\n  width: calc(100% - 20px);\n  top: -6px;\n  position: relative;\n}\n.systemtags-select2-dropdown .systemtags-rename-form input {\n  display: inline-block;\n  height: 30px;\n  width: calc(100% - 40px);\n}\n.systemtags-select2-dropdown .label {\n  width: 85%;\n  display: inline-block;\n  overflow: hidden;\n  text-overflow: ellipsis;\n}\n.systemtags-select2-dropdown .label.hidden {\n  display: none;\n}\n.systemtags-select2-dropdown span {\n  line-height: 25px;\n}\n.systemtags-select2-dropdown .systemtags-item {\n  display: inline-block;\n  height: 25px;\n  width: 100%;\n}\n.systemtags-select2-dropdown .select2-result-label {\n  height: 25px;\n}\n\n.systemtags-select2-container {\n  width: 100%;\n}\n.systemtags-select2-container .select2-choices {\n  flex-wrap: nowrap !important;\n  max-height: 44px;\n}\n.systemtags-select2-container .select2-choices .select2-search-choice.select2-locked .label {\n  opacity: 0.5;\n}\n\n#select2-drop.systemtags-select2-dropdown .select2-results li.select2-result {\n  padding: 5px;\n}", ""]);
// Exports
/* harmony default export */ __webpack_exports__["default"] = (___CSS_LOADER_EXPORT___);


/***/ }),

/***/ "./core/src/systemtags/templates/result.handlebars":
/*!*********************************************************!*\
  !*** ./core/src/systemtags/templates/result.handlebars ***!
  \*********************************************************/
/***/ (function(module, __unused_webpack_exports, __webpack_require__) {

var Handlebars = __webpack_require__(/*! ../../../../node_modules/handlebars/runtime.js */ "./node_modules/handlebars/runtime.js");
function __default(obj) { return obj && (obj.__esModule ? obj["default"] : obj); }
module.exports = (Handlebars["default"] || Handlebars).template({"1":function(container,depth0,helpers,partials,data) {
    return " new-item";
},"3":function(container,depth0,helpers,partials,data) {
    var stack1, helper, lookupProperty = container.lookupProperty || function(parent, propertyName) {
        if (Object.prototype.hasOwnProperty.call(parent, propertyName)) {
          return parent[propertyName];
        }
        return undefined
    };

  return "		<span class=\"label\">"
    + ((stack1 = ((helper = (helper = lookupProperty(helpers,"tagMarkup") || (depth0 != null ? lookupProperty(depth0,"tagMarkup") : depth0)) != null ? helper : container.hooks.helperMissing),(typeof helper === "function" ? helper.call(depth0 != null ? depth0 : (container.nullContext || {}),{"name":"tagMarkup","hash":{},"data":data,"loc":{"start":{"line":4,"column":22},"end":{"line":4,"column":37}}}) : helper))) != null ? stack1 : "")
    + "</span>\n";
},"5":function(container,depth0,helpers,partials,data) {
    var helper, lookupProperty = container.lookupProperty || function(parent, propertyName) {
        if (Object.prototype.hasOwnProperty.call(parent, propertyName)) {
          return parent[propertyName];
        }
        return undefined
    };

  return "		<span class=\"label\">"
    + container.escapeExpression(((helper = (helper = lookupProperty(helpers,"name") || (depth0 != null ? lookupProperty(depth0,"name") : depth0)) != null ? helper : container.hooks.helperMissing),(typeof helper === "function" ? helper.call(depth0 != null ? depth0 : (container.nullContext || {}),{"name":"name","hash":{},"data":data,"loc":{"start":{"line":6,"column":22},"end":{"line":6,"column":30}}}) : helper)))
    + "</span>\n";
},"7":function(container,depth0,helpers,partials,data) {
    var helper, lookupProperty = container.lookupProperty || function(parent, propertyName) {
        if (Object.prototype.hasOwnProperty.call(parent, propertyName)) {
          return parent[propertyName];
        }
        return undefined
    };

  return "		<span class=\"systemtags-actions\">\n			<a href=\"#\" class=\"rename icon icon-rename\" title=\""
    + container.escapeExpression(((helper = (helper = lookupProperty(helpers,"renameTooltip") || (depth0 != null ? lookupProperty(depth0,"renameTooltip") : depth0)) != null ? helper : container.hooks.helperMissing),(typeof helper === "function" ? helper.call(depth0 != null ? depth0 : (container.nullContext || {}),{"name":"renameTooltip","hash":{},"data":data,"loc":{"start":{"line":10,"column":54},"end":{"line":10,"column":71}}}) : helper)))
    + "\"></a>\n		</span>\n";
},"compiler":[8,">= 4.3.0"],"main":function(container,depth0,helpers,partials,data) {
    var stack1, helper, options, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=container.hooks.helperMissing, alias3="function", lookupProperty = container.lookupProperty || function(parent, propertyName) {
        if (Object.prototype.hasOwnProperty.call(parent, propertyName)) {
          return parent[propertyName];
        }
        return undefined
    }, buffer = 
  "<span class=\"systemtags-item"
    + ((stack1 = lookupProperty(helpers,"if").call(alias1,(depth0 != null ? lookupProperty(depth0,"isNew") : depth0),{"name":"if","hash":{},"fn":container.program(1, data, 0),"inverse":container.noop,"data":data,"loc":{"start":{"line":1,"column":28},"end":{"line":1,"column":57}}})) != null ? stack1 : "")
    + "\" data-id=\""
    + container.escapeExpression(((helper = (helper = lookupProperty(helpers,"id") || (depth0 != null ? lookupProperty(depth0,"id") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"id","hash":{},"data":data,"loc":{"start":{"line":1,"column":68},"end":{"line":1,"column":74}}}) : helper)))
    + "\">\n<span class=\"checkmark icon icon-checkmark\"></span>\n"
    + ((stack1 = lookupProperty(helpers,"if").call(alias1,(depth0 != null ? lookupProperty(depth0,"isAdmin") : depth0),{"name":"if","hash":{},"fn":container.program(3, data, 0),"inverse":container.program(5, data, 0),"data":data,"loc":{"start":{"line":3,"column":1},"end":{"line":7,"column":8}}})) != null ? stack1 : "");
  stack1 = ((helper = (helper = lookupProperty(helpers,"allowActions") || (depth0 != null ? lookupProperty(depth0,"allowActions") : depth0)) != null ? helper : alias2),(options={"name":"allowActions","hash":{},"fn":container.program(7, data, 0),"inverse":container.noop,"data":data,"loc":{"start":{"line":8,"column":1},"end":{"line":12,"column":18}}}),(typeof helper === alias3 ? helper.call(alias1,options) : helper));
  if (!lookupProperty(helpers,"allowActions")) { stack1 = container.hooks.blockHelperMissing.call(depth0,stack1,options)}
  if (stack1 != null) { buffer += stack1; }
  return buffer + "</span>\n";
},"useData":true});

/***/ }),

/***/ "./core/src/systemtags/templates/result_form.handlebars":
/*!**************************************************************!*\
  !*** ./core/src/systemtags/templates/result_form.handlebars ***!
  \**************************************************************/
/***/ (function(module, __unused_webpack_exports, __webpack_require__) {

var Handlebars = __webpack_require__(/*! ../../../../node_modules/handlebars/runtime.js */ "./node_modules/handlebars/runtime.js");
function __default(obj) { return obj && (obj.__esModule ? obj["default"] : obj); }
module.exports = (Handlebars["default"] || Handlebars).template({"1":function(container,depth0,helpers,partials,data) {
    var helper, lookupProperty = container.lookupProperty || function(parent, propertyName) {
        if (Object.prototype.hasOwnProperty.call(parent, propertyName)) {
          return parent[propertyName];
        }
        return undefined
    };

  return "		<a href=\"#\" class=\"delete icon icon-delete\" title=\""
    + container.escapeExpression(((helper = (helper = lookupProperty(helpers,"deleteTooltip") || (depth0 != null ? lookupProperty(depth0,"deleteTooltip") : depth0)) != null ? helper : container.hooks.helperMissing),(typeof helper === "function" ? helper.call(depth0 != null ? depth0 : (container.nullContext || {}),{"name":"deleteTooltip","hash":{},"data":data,"loc":{"start":{"line":5,"column":53},"end":{"line":5,"column":70}}}) : helper)))
    + "\"></a>\n";
},"compiler":[8,">= 4.3.0"],"main":function(container,depth0,helpers,partials,data) {
    var stack1, helper, alias1=depth0 != null ? depth0 : (container.nullContext || {}), alias2=container.hooks.helperMissing, alias3="function", alias4=container.escapeExpression, lookupProperty = container.lookupProperty || function(parent, propertyName) {
        if (Object.prototype.hasOwnProperty.call(parent, propertyName)) {
          return parent[propertyName];
        }
        return undefined
    };

  return "<form class=\"systemtags-rename-form\">\n	 <label class=\"hidden-visually\" for=\""
    + alias4(((helper = (helper = lookupProperty(helpers,"cid") || (depth0 != null ? lookupProperty(depth0,"cid") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"cid","hash":{},"data":data,"loc":{"start":{"line":2,"column":38},"end":{"line":2,"column":45}}}) : helper)))
    + "-rename-input\">"
    + alias4(((helper = (helper = lookupProperty(helpers,"renameLabel") || (depth0 != null ? lookupProperty(depth0,"renameLabel") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"renameLabel","hash":{},"data":data,"loc":{"start":{"line":2,"column":60},"end":{"line":2,"column":75}}}) : helper)))
    + "</label>\n	<input id=\""
    + alias4(((helper = (helper = lookupProperty(helpers,"cid") || (depth0 != null ? lookupProperty(depth0,"cid") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"cid","hash":{},"data":data,"loc":{"start":{"line":3,"column":12},"end":{"line":3,"column":19}}}) : helper)))
    + "-rename-input\" type=\"text\" value=\""
    + alias4(((helper = (helper = lookupProperty(helpers,"name") || (depth0 != null ? lookupProperty(depth0,"name") : depth0)) != null ? helper : alias2),(typeof helper === alias3 ? helper.call(alias1,{"name":"name","hash":{},"data":data,"loc":{"start":{"line":3,"column":53},"end":{"line":3,"column":61}}}) : helper)))
    + "\">\n"
    + ((stack1 = lookupProperty(helpers,"if").call(alias1,(depth0 != null ? lookupProperty(depth0,"isAdmin") : depth0),{"name":"if","hash":{},"fn":container.program(1, data, 0),"inverse":container.noop,"data":data,"loc":{"start":{"line":4,"column":1},"end":{"line":6,"column":8}}})) != null ? stack1 : "")
    + "</form>\n";
},"useData":true});

/***/ }),

/***/ "./core/src/systemtags/templates/selection.handlebars":
/*!************************************************************!*\
  !*** ./core/src/systemtags/templates/selection.handlebars ***!
  \************************************************************/
/***/ (function(module, __unused_webpack_exports, __webpack_require__) {

var Handlebars = __webpack_require__(/*! ../../../../node_modules/handlebars/runtime.js */ "./node_modules/handlebars/runtime.js");
function __default(obj) { return obj && (obj.__esModule ? obj["default"] : obj); }
module.exports = (Handlebars["default"] || Handlebars).template({"1":function(container,depth0,helpers,partials,data) {
    var stack1, helper, lookupProperty = container.lookupProperty || function(parent, propertyName) {
        if (Object.prototype.hasOwnProperty.call(parent, propertyName)) {
          return parent[propertyName];
        }
        return undefined
    };

  return "	<span class=\"label\">"
    + ((stack1 = ((helper = (helper = lookupProperty(helpers,"tagMarkup") || (depth0 != null ? lookupProperty(depth0,"tagMarkup") : depth0)) != null ? helper : container.hooks.helperMissing),(typeof helper === "function" ? helper.call(depth0 != null ? depth0 : (container.nullContext || {}),{"name":"tagMarkup","hash":{},"data":data,"loc":{"start":{"line":2,"column":21},"end":{"line":2,"column":36}}}) : helper))) != null ? stack1 : "")
    + "</span>\n";
},"3":function(container,depth0,helpers,partials,data) {
    var helper, lookupProperty = container.lookupProperty || function(parent, propertyName) {
        if (Object.prototype.hasOwnProperty.call(parent, propertyName)) {
          return parent[propertyName];
        }
        return undefined
    };

  return "	<span class=\"label\">"
    + container.escapeExpression(((helper = (helper = lookupProperty(helpers,"name") || (depth0 != null ? lookupProperty(depth0,"name") : depth0)) != null ? helper : container.hooks.helperMissing),(typeof helper === "function" ? helper.call(depth0 != null ? depth0 : (container.nullContext || {}),{"name":"name","hash":{},"data":data,"loc":{"start":{"line":4,"column":21},"end":{"line":4,"column":29}}}) : helper)))
    + "</span>\n";
},"compiler":[8,">= 4.3.0"],"main":function(container,depth0,helpers,partials,data) {
    var stack1, lookupProperty = container.lookupProperty || function(parent, propertyName) {
        if (Object.prototype.hasOwnProperty.call(parent, propertyName)) {
          return parent[propertyName];
        }
        return undefined
    };

  return ((stack1 = lookupProperty(helpers,"if").call(depth0 != null ? depth0 : (container.nullContext || {}),(depth0 != null ? lookupProperty(depth0,"isAdmin") : depth0),{"name":"if","hash":{},"fn":container.program(1, data, 0),"inverse":container.program(3, data, 0),"data":data,"loc":{"start":{"line":1,"column":0},"end":{"line":5,"column":7}}})) != null ? stack1 : "");
},"useData":true});

/***/ }),

/***/ "./core/css/systemtags.scss":
/*!**********************************!*\
  !*** ./core/css/systemtags.scss ***!
  \**********************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! !../../node_modules/style-loader/dist/runtime/injectStylesIntoStyleTag.js */ "./node_modules/style-loader/dist/runtime/injectStylesIntoStyleTag.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! !../../node_modules/style-loader/dist/runtime/styleDomAPI.js */ "./node_modules/style-loader/dist/runtime/styleDomAPI.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! !../../node_modules/style-loader/dist/runtime/insertBySelector.js */ "./node_modules/style-loader/dist/runtime/insertBySelector.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../node_modules/style-loader/dist/runtime/setAttributesWithoutAttributes.js */ "./node_modules/style-loader/dist/runtime/setAttributesWithoutAttributes.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! !../../node_modules/style-loader/dist/runtime/insertStyleElement.js */ "./node_modules/style-loader/dist/runtime/insertStyleElement.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! !../../node_modules/style-loader/dist/runtime/styleTagTransform.js */ "./node_modules/style-loader/dist/runtime/styleTagTransform.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_sass_loader_dist_cjs_js_systemtags_scss__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../node_modules/css-loader/dist/cjs.js!../../node_modules/sass-loader/dist/cjs.js!./systemtags.scss */ "./node_modules/css-loader/dist/cjs.js!./node_modules/sass-loader/dist/cjs.js!./core/css/systemtags.scss");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());

      options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
    
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_sass_loader_dist_cjs_js_systemtags_scss__WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ __webpack_exports__["default"] = (_node_modules_css_loader_dist_cjs_js_node_modules_sass_loader_dist_cjs_js_systemtags_scss__WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_sass_loader_dist_cjs_js_systemtags_scss__WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_sass_loader_dist_cjs_js_systemtags_scss__WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


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
/******/ 			"core-systemtags": 0
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
/******/ 	var __webpack_exports__ = __webpack_require__.O(undefined, ["core-common"], function() { return __webpack_require__("./core/src/systemtags/merged-systemtags.js"); })
/******/ 	__webpack_exports__ = __webpack_require__.O(__webpack_exports__);
/******/ 	
/******/ })()
;
//# sourceMappingURL=core-systemtags.js.map?v=4ec6d9f80cdbe586cf95