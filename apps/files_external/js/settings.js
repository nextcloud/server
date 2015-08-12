/*
 * Copyright (c) 2014
 *
 * This file is licensed under the Affero General Public License version 3
 * or later.
 *
 * See the COPYING-README file.
 *
 */

(function(){

// TODO: move to a separate file
var MOUNT_OPTIONS_DROPDOWN_TEMPLATE =
	'<div class="drop dropdown mountOptionsDropdown">' +
	// FIXME: options are hard-coded for now
	'	<div class="optionRow">' +
	'		<label for="mountOptionsEncrypt">{{t "files_external" "Enable encryption"}}</label>' +
	'		<input id="mountOptionsEncrypt" name="encrypt" type="checkbox" value="true" checked="checked"/>' +
	'	</div>' +
	'	<div class="optionRow">' +
	'		<label for="mountOptionsPreviews">{{t "files_external" "Enable previews"}}</label>' +
	'		<input id="mountOptionsPreviews" name="previews" type="checkbox" value="true" checked="checked"/>' +
	'	</div>' +
	'	<div class="optionRow">' +
	'		<label for="mountOptionsFilesystemCheck">{{t "files_external" "Check for changes"}}</label>' +
	'		<select id="mountOptionsFilesystemCheck" name="filesystem_check_changes" data-type="int">' +
	'			<option value="0">{{t "files_external" "Never"}}</option>' +
	'			<option value="1" selected="selected">{{t "files_external" "Once every direct access"}}</option>' +
	'			<option value="2">{{t "files_external" "Every time the filesystem is used"}}</option>' +
	'		</select>' +
	'	</div>' +
	'</div>';

	/* TODO the current l10n extrator can't handle JS functions within handlebar
	   templates therefore they are duplicated here
	t("files_external", "Enable encryption")
	t("files_external", "Enable previews")
	t("files_external", "Check for changes")
	t("files_external", "Never")
	t("files_external", "Once every direct access")
	t("files_external", "Every time the filesystem is used")
	 */

/**
 * Returns the selection of applicable users in the given configuration row
 *
 * @param $row configuration row
 * @return array array of user names
 */
function getSelection($row) {
	var values = $row.find('.applicableUsers').select2('val');
	if (!values || values.length === 0) {
		values = [];
	}
	return values;
}

function highlightBorder($element, highlight) {
	$element.toggleClass('warning-input', highlight);
	return highlight;
}

function highlightInput($input) {
	if ($input.attr('type') === 'text' || $input.attr('type') === 'password') {
		return highlightBorder($input,
			($input.val() === '' && !$input.hasClass('optional')));
	}
}

/**
 * Initialize select2 plugin on the given elements
 *
 * @param {Array<Object>} array of jQuery elements
 * @param {int} userListLimit page size for result list
 */
function addSelect2 ($elements, userListLimit) {
	if (!$elements.length) {
		return;
	}
	$elements.select2({
		placeholder: t('files_external', 'All users. Type to select user or group.'),
		allowClear: true,
		multiple: true,
		//minimumInputLength: 1,
		ajax: {
			url: OC.generateUrl('apps/files_external/applicable'),
			dataType: 'json',
			quietMillis: 100,
			data: function (term, page) { // page is the one-based page number tracked by Select2
				return {
					pattern: term, //search term
					limit: userListLimit, // page size
					offset: userListLimit*(page-1) // page number starts with 0
				};
			},
			results: function (data) {
				if (data.status === 'success') {

					var results = [];
					var userCount = 0; // users is an object

					// add groups
					$.each(data.groups, function(i, group) {
						results.push({name:group+'(group)', displayname:group, type:'group' });
					});
					// add users
					$.each(data.users, function(id, user) {
						userCount++;
						results.push({name:id, displayname:user, type:'user' });
					});


					var more = (userCount >= userListLimit) || (data.groups.length >= userListLimit);
					return {results: results, more: more};
				} else {
					//FIXME add error handling
				}
			}
		},
		initSelection: function(element, callback) {
			var users = {};
			users['users'] = [];
			var toSplit = element.val().split(",");
			for (var i = 0; i < toSplit.length; i++) {
				users['users'].push(toSplit[i]);
			}

			$.ajax(OC.generateUrl('displaynames'), {
				type: 'POST',
				contentType: 'application/json',
				data: JSON.stringify(users),
				dataType: 'json'
			}).done(function(data) {
				var results = [];
				if (data.status === 'success') {
					$.each(data.users, function(user, displayname) {
						if (displayname !== false) {
							results.push({name:user, displayname:displayname, type:'user'});
						}
					});
					callback(results);
				} else {
					//FIXME add error handling
				}
			});
		},
		id: function(element) {
			return element.name;
		},
		formatResult: function (element) {
			var $result = $('<span><div class="avatardiv"/><span>'+escapeHTML(element.displayname)+'</span></span>');
			var $div = $result.find('.avatardiv')
				.attr('data-type', element.type)
				.attr('data-name', element.name)
				.attr('data-displayname', element.displayname);
			if (element.type === 'group') {
				var url = OC.imagePath('core','places/contacts-dark'); // TODO better group icon
				$div.html('<img width="32" height="32" src="'+url+'">');
			}
			return $result.get(0).outerHTML;
		},
		formatSelection: function (element) {
			if (element.type === 'group') {
				return '<span title="'+escapeHTML(element.name)+'" class="group">'+escapeHTML(element.displayname+' '+t('files_external', '(group)'))+'</span>';
			} else {
				return '<span title="'+escapeHTML(element.name)+'" class="user">'+escapeHTML(element.displayname)+'</span>';
			}
		},
		escapeMarkup: function (m) { return m; } // we escape the markup in formatResult and formatSelection
	}).on('select2-loaded', function() {
		$.each($('.avatardiv'), function(i, div) {
			var $div = $(div);
			if ($div.data('type') === 'user') {
				$div.avatar($div.data('name'),32);
			}
		});
	});
}

/**
 * @class OCA.External.Settings.StorageConfig
 *
 * @classdesc External storage config
 */
var StorageConfig = function(id) {
	this.id = id;
	this.backendOptions = {};
};
// Keep this in sync with \OC_Mount_Config::STATUS_*
StorageConfig.Status = {
	IN_PROGRESS: -1,
	SUCCESS: 0,
	ERROR: 1,
	INDETERMINATE: 2
};
/**
 * @memberof OCA.External.Settings
 */
StorageConfig.prototype = {
	_url: null,

	/**
	 * Storage id
	 *
	 * @type int
	 */
	id: null,

	/**
	 * Mount point
	 *
	 * @type string
	 */
	mountPoint: '',

	/**
	 * Backend
	 *
	 * @type string
	 */
	backend: null,

	/**
	 * Authentication mechanism
	 *
	 * @type string
	 */
	authMechanism: null,

	/**
	 * Backend-specific configuration
	 *
	 * @type Object.<string,object>
	 */
	backendOptions: null,

	/**
	 * Mount-specific options
	 *
	 * @type Object.<string,object>
	 */
	mountOptions: null,

	/**
	 * Creates or saves the storage.
	 *
	 * @param {Function} [options.success] success callback, receives result as argument
	 * @param {Function} [options.error] error callback
	 */
	save: function(options) {
		var self = this;
		var url = OC.generateUrl(this._url);
		var method = 'POST';
		if (_.isNumber(this.id)) {
			method = 'PUT';
			url = OC.generateUrl(this._url + '/{id}', {id: this.id});
		}

		$.ajax({
			type: method,
			url: url,
			contentType: 'application/json',
			data: JSON.stringify(this.getData()),
			success: function(result) {
				self.id = result.id;
				if (_.isFunction(options.success)) {
					options.success(result);
				}
			},
			error: options.error
		});
	},

	/**
	 * Returns the data from this object
	 *
	 * @return {Array} JSON array of the data
	 */
	getData: function() {
		var data = {
			mountPoint: this.mountPoint,
			backend: this.backend,
			authMechanism: this.authMechanism,
			backendOptions: this.backendOptions
		};
		if (this.id) {
			data.id = this.id;
		}
		if (this.mountOptions) {
			data.mountOptions = this.mountOptions;
		}
		return data;
	},

	/**
	 * Recheck the storage
	 *
	 * @param {Function} [options.success] success callback, receives result as argument
	 * @param {Function} [options.error] error callback
	 */
	recheck: function(options) {
		if (!_.isNumber(this.id)) {
			if (_.isFunction(options.error)) {
				options.error();
			}
			return;
		}
		$.ajax({
			type: 'GET',
			url: OC.generateUrl(this._url + '/{id}', {id: this.id}),
			success: options.success,
			error: options.error
		});
	},

	/**
	 * Deletes the storage
	 *
	 * @param {Function} [options.success] success callback
	 * @param {Function} [options.error] error callback
	 */
	destroy: function(options) {
		if (!_.isNumber(this.id)) {
			// the storage hasn't even been created => success
			if (_.isFunction(options.success)) {
				options.success();
			}
			return;
		}
		$.ajax({
			type: 'DELETE',
			url: OC.generateUrl(this._url + '/{id}', {id: this.id}),
			success: options.success,
			error: options.error
		});
	},

	/**
	 * Validate this model
	 *
	 * @return {boolean} false if errors exist, true otherwise
	 */
	validate: function() {
		if (this.mountPoint === '') {
			return false;
		}
		if (this.errors) {
			return false;
		}
		return true;
	}
};

/**
 * @class OCA.External.Settings.GlobalStorageConfig
 * @augments OCA.External.Settings.StorageConfig
 *
 * @classdesc Global external storage config
 */
var GlobalStorageConfig = function(id) {
	this.id = id;
	this.applicableUsers = [];
	this.applicableGroups = [];
};
/**
 * @memberOf OCA.External.Settings
 */
GlobalStorageConfig.prototype = _.extend({}, StorageConfig.prototype,
	/** @lends OCA.External.Settings.GlobalStorageConfig.prototype */ {
	_url: 'apps/files_external/globalstorages',

	/**
	 * Applicable users
	 *
	 * @type Array.<string>
	 */
	applicableUsers: null,

	/**
	 * Applicable groups
	 *
	 * @type Array.<string>
	 */
	applicableGroups: null,

	/**
	 * Storage priority
	 *
	 * @type int
	 */
	priority: null,

	/**
	 * Returns the data from this object
	 *
	 * @return {Array} JSON array of the data
	 */
	getData: function() {
		var data = StorageConfig.prototype.getData.apply(this, arguments);
		return _.extend(data, {
			applicableUsers: this.applicableUsers,
			applicableGroups: this.applicableGroups,
			priority: this.priority,
		});
	}
});

/**
 * @class OCA.External.Settings.UserStorageConfig
 * @augments OCA.External.Settings.StorageConfig
 *
 * @classdesc User external storage config
 */
var UserStorageConfig = function(id) {
	this.id = id;
};
UserStorageConfig.prototype = _.extend({}, StorageConfig.prototype,
	/** @lends OCA.External.Settings.UserStorageConfig.prototype */ {
	_url: 'apps/files_external/userstorages'
});

/**
 * @class OCA.External.Settings.MountOptionsDropdown
 *
 * @classdesc Dropdown for mount options
 *
 * @param {Object} $container container DOM object
 */
var MountOptionsDropdown = function() {
};
/**
 * @memberof OCA.External.Settings
 */
MountOptionsDropdown.prototype = {
	/**
	 * Dropdown element
	 *
	 * @var Object
	 */
	$el: null,

	/**
	 * Show dropdown
	 *
	 * @param {Object} $container container
	 * @param {Object} mountOptions mount options
	 * @param {Array} enabledOptions enabled mount options
	 */
	show: function($container, mountOptions, enabledOptions) {
		if (MountOptionsDropdown._last) {
			MountOptionsDropdown._last.hide();
		}

		var template = MountOptionsDropdown._template;
		if (!template) {
			template = Handlebars.compile(MOUNT_OPTIONS_DROPDOWN_TEMPLATE);
			MountOptionsDropdown._template = template;
		}

		var $el = $(template());
		this.$el = $el;
		$el.addClass('hidden');

		this.setOptions(mountOptions, enabledOptions);

		this.$el.appendTo($container);
		MountOptionsDropdown._last = this;

		this.$el.trigger('show');
	},

	hide: function() {
		if (this.$el) {
			this.$el.trigger('hide');
			this.$el.remove();
			this.$el = null;
			MountOptionsDropdown._last = null;
		}
	},

	/**
	 * Returns the mount options from the dropdown controls
	 *
	 * @return {Object} options mount options
	 */
	getOptions: function() {
		var options = {};

		this.$el.find('input, select').each(function() {
			var $this = $(this);
			var key = $this.attr('name');
			var value = null;
			if ($this.attr('type') === 'checkbox') {
				value = $this.prop('checked');
			} else {
				value = $this.val();
			}
			if ($this.attr('data-type') === 'int') {
				value = parseInt(value, 10);
			}
			options[key] = value;
		});
		return options;
	},

	/**
	 * Sets the mount options to the dropdown controls
	 *
	 * @param {Object} options mount options
	 * @param {Array} enabledOptions enabled mount options
	 */
	setOptions: function(options, enabledOptions) {
		var $el = this.$el;
		_.each(options, function(value, key) {
			var $optionEl = $el.find('input, select').filterAttr('name', key);
			if ($optionEl.attr('type') === 'checkbox') {
				if (_.isString(value)) {
					value = (value === 'true');
				}
				$optionEl.prop('checked', !!value);
			} else {
				$optionEl.val(value);
			}
		});
		$el.find('.optionRow').each(function(i, row){
			var $row = $(row);
			var optionId = $row.find('input, select').attr('name');
			if (enabledOptions.indexOf(optionId) === -1) {
				$row.hide();
			} else {
				$row.show();
			}
		});
	}
};

/**
 * @class OCA.External.Settings.MountConfigListView
 *
 * @classdesc Mount configuration list view
 *
 * @param {Object} $el DOM object containing the list
 * @param {Object} [options]
 * @param {int} [options.userListLimit] page size in applicable users dropdown
 */
var MountConfigListView = function($el, options) {
	this.initialize($el, options);
};
/**
 * @memberOf OCA.External.Settings
 */
MountConfigListView.prototype = {

	/**
	 * jQuery element containing the config list
	 *
	 * @type Object
	 */
	$el: null,

	/**
	 * Storage config class
	 *
	 * @type Class
	 */
	_storageConfigClass: null,

	/**
	 * Flag whether the list is about user storage configs (true)
	 * or global storage configs (false)
	 *
	 * @type bool
	 */
	_isPersonal: false,

	/**
	 * Page size in applicable users dropdown
	 *
	 * @type int
	 */
	_userListLimit: 30,

	/**
	 * List of supported backends
	 *
	 * @type Object.<string,Object>
	 */
	_allBackends: null,

	/**
	 * List of all supported authentication mechanisms
	 *
	 * @type Object.<string,Object>
	 */
	_allAuthMechanisms: null,

	_encryptionEnabled: false,

	/**
	 * @param {Object} $el DOM object containing the list
	 * @param {Object} [options]
	 * @param {int} [options.userListLimit] page size in applicable users dropdown
	 */
	initialize: function($el, options) {
		var self = this;
		this.$el = $el;
		this._isPersonal = ($el.data('admin') !== true);
		if (this._isPersonal) {
			this._storageConfigClass = OCA.External.Settings.UserStorageConfig;
		} else {
			this._storageConfigClass = OCA.External.Settings.GlobalStorageConfig;
		}

		if (options && !_.isUndefined(options.userListLimit)) {
			this._userListLimit = options.userListLimit;
		}

		this._encryptionEnabled = options.encryptionEnabled;

		// read the backend config that was carefully crammed
		// into the data-configurations attribute of the select
		this._allBackends = this.$el.find('.selectBackend').data('configurations');
		this._allAuthMechanisms = this.$el.find('#addMountPoint .authentication').data('mechanisms');

		//initialize hidden input field with list of users and groups
		this.$el.find('tr:not(#addMountPoint)').each(function(i,tr) {
			var $tr = $(tr);
			var $applicable = $tr.find('.applicable');
			if ($applicable.length > 0) {
				var groups = $applicable.data('applicable-groups');
				var groupsId = [];
				$.each(groups, function () {
					groupsId.push(this + '(group)');
				});
				var users = $applicable.data('applicable-users');
				if (users.indexOf('all') > -1 || users === '') {
					$tr.find('.applicableUsers').val('');
				} else {
					$tr.find('.applicableUsers').val(groupsId.concat(users).join(','));
				}
			}
		});

		addSelect2(this.$el.find('tr:not(#addMountPoint) .applicableUsers'), this._userListLimit);

		this.$el.find('tr:not(#addMountPoint)').each(function(i, tr) {
			self.recheckStorageConfig($(tr));
		});

		this._initEvents();
	},

	/**
	 * Initialize DOM event handlers
	 */
	_initEvents: function() {
		var self = this;

		var onChangeHandler = _.bind(this._onChange, this);
		//this.$el.on('input', 'td input', onChangeHandler);
		this.$el.on('keyup', 'td input', onChangeHandler);
		this.$el.on('paste', 'td input', onChangeHandler);
		this.$el.on('change', 'td input:checkbox', onChangeHandler);
		this.$el.on('change', '.applicable', onChangeHandler);

		this.$el.on('click', '.status>span', function() {
			self.recheckStorageConfig($(this).closest('tr'));
		});

		this.$el.on('click', 'td.remove>img', function() {
			self.deleteStorageConfig($(this).closest('tr'));
		});

		this.$el.on('click', 'td.mountOptionsToggle>img', function() {
			self._showMountOptionsDropdown($(this).closest('tr'));
		});

		this.$el.on('change', '.selectBackend', _.bind(this._onSelectBackend, this));
		this.$el.on('change', '.selectAuthMechanism', _.bind(this._onSelectAuthMechanism, this));
	},

	_onChange: function(event) {
		var self = this;
		var $target = $(event.target);
		if ($target.closest('.dropdown').length) {
			// ignore dropdown events
			return;
		}
		highlightInput($target);
		var $tr = $target.closest('tr');

		var timer = $tr.data('save-timer');
		clearTimeout(timer);
		timer = setTimeout(function() {
			self.saveStorageConfig($tr);
		}, 2000);
		$tr.data('save-timer', timer);
	},

	_onSelectBackend: function(event) {
		var $target = $(event.target);
		var $el = this.$el;
		var $tr = $target.closest('tr');
		$el.find('tbody').append($tr.clone());
		$el.find('tbody tr').last().find('.mountPoint input').val('');
		var selected = $target.find('option:selected').text();
		var backend = $target.val();
		$tr.find('.backend').text(selected);
		if ($tr.find('.mountPoint input').val() === '') {
			$tr.find('.mountPoint input').val(this._suggestMountPoint(selected));
		}
		$tr.addClass(backend);
		$tr.find('.backend').data('class', backend);
		var backendConfiguration = this._allBackends[backend];

		var selectAuthMechanism = $('<select class="selectAuthMechanism"></select>');
		$.each(this._allAuthMechanisms, function(authClass, authMechanism) {
			if (backendConfiguration['authSchemes'][authMechanism['scheme']]) {
				selectAuthMechanism.append(
					$('<option value="'+authClass+'" data-scheme="'+authMechanism['scheme']+'">'+authMechanism['name']+'</option>')
				);
			}
		});
		$tr.find('td.authentication').append(selectAuthMechanism);

		var $td = $tr.find('td.configuration');
		$.each(backendConfiguration['configuration'], _.partial(this.writeParameterInput, $td));

		selectAuthMechanism.trigger('change'); // generate configuration parameters for auth mechanism

		var priorityEl = $('<input type="hidden" class="priority" value="' + backendConfiguration['priority'] + '" />');
		$tr.append(priorityEl);
		if (backendConfiguration['custom'] && $el.find('tbody tr.'+backend.replace(/\\/g, '\\\\')).length === 1) {
			OC.addScript('files_external', backendConfiguration['custom']);
		}
		$td.children().not('[type=hidden]').first().focus();

		$tr.find('td').last().attr('class', 'remove');
		$tr.find('td.mountOptionsToggle').removeClass('hidden');
		$tr.find('td').last().removeAttr('style');
		$tr.removeAttr('id');
		$target.remove();
		addSelect2($tr.find('.applicableUsers'), this._userListLimit);
	},

	_onSelectAuthMechanism: function(event) {
		var $target = $(event.target);
		var $tr = $target.closest('tr');

		var authMechanism = $target.val();
		var authMechanismConfiguration = this._allAuthMechanisms[authMechanism];
		var $td = $tr.find('td.configuration');
		$td.find('.auth-param').remove();

		$.each(authMechanismConfiguration['configuration'], _.partial(
			this.writeParameterInput, $td, _, _, ['auth-param']
		));
	},

	writeParameterInput: function($td, parameter, placeholder, classes) {
		classes = $.isArray(classes) ? classes : [];
		classes.push('added');
		if (placeholder.indexOf('&') === 0) {
			classes.push('optional');
			placeholder = placeholder.substring(1);
		}
		var newElement;
		if (placeholder.indexOf('*') === 0) {
			newElement = $('<input type="password" class="'+classes.join(' ')+'" data-parameter="'+parameter+'" placeholder="'+placeholder.substring(1)+'" />');
		} else if (placeholder.indexOf('!') === 0) {
			newElement = $('<label><input type="checkbox" class="'+classes.join(' ')+'" data-parameter="'+parameter+'" />'+placeholder.substring(1)+'</label>');
		} else if (placeholder.indexOf('#') === 0) {
			newElement = $('<input type="hidden" class="'+classes.join(' ')+'" data-parameter="'+parameter+'" />');
		} else {
			newElement = $('<input type="text" class="'+classes.join(' ')+'" data-parameter="'+parameter+'" placeholder="'+placeholder+'" />');
		}
		highlightInput(newElement);
		$td.append(newElement);
	},

	/**
	 * Gets the storage model from the given row
	 *
	 * @param $tr row element
	 * @return {OCA.External.StorageConfig} storage model instance
	 */
	getStorageConfig: function($tr) {
		var storageId = parseInt($tr.attr('data-id'), 10);
		if (!storageId) {
			// new entry
			storageId = null;
		}
		var storage = new this._storageConfigClass(storageId);
		storage.mountPoint = $tr.find('.mountPoint input').val();
		storage.backend = $tr.find('.backend').data('class');
		storage.authMechanism = $tr.find('.selectAuthMechanism').val();

		var classOptions = {};
		var configuration = $tr.find('.configuration input');
		var missingOptions = [];
		$.each(configuration, function(index, input) {
			var $input = $(input);
			var parameter = $input.data('parameter');
			if ($input.attr('type') === 'button') {
				return;
			}
			if ($input.val() === '' && !$input.hasClass('optional')) {
				missingOptions.push(parameter);
				return;
			}
			if ($(input).is(':checkbox')) {
				if ($(input).is(':checked')) {
					classOptions[parameter] = true;
				} else {
					classOptions[parameter] = false;
				}
			} else {
				classOptions[parameter] = $(input).val();
			}
		});

		storage.backendOptions = classOptions;
		if (missingOptions.length) {
			storage.errors = {
				backendOptions: missingOptions
			};
		}

		// gather selected users and groups
		if (!this._isPersonal) {
			var groups = [];
			var users = [];
			var multiselect = getSelection($tr);
			$.each(multiselect, function(index, value) {
				var pos = value.indexOf('(group)');
				if (pos !== -1) {
					groups.push(value.substr(0, pos));
				} else {
					users.push(value);
				}
			});
			// FIXME: this should be done in the multiselect change event instead
			$tr.find('.applicable')
				.data('applicable-groups', groups)
				.data('applicable-users', users);

			storage.applicableUsers = users;
			storage.applicableGroups = groups;

			storage.priority = parseInt($tr.find('input.priority').val() || '100', 10);
		}

		var mountOptions = $tr.find('input.mountOptions').val();
		if (mountOptions) {
			storage.mountOptions = JSON.parse(mountOptions);
		}

		return storage;
	},

	/**
	 * Deletes the storage from the given tr
	 *
	 * @param $tr storage row
	 * @param Function callback callback to call after save
	 */
	deleteStorageConfig: function($tr) {
		var self = this;
		var configId = $tr.data('id');
		if (!_.isNumber(configId)) {
			// deleting unsaved storage
			$tr.remove();
			return;
		}
		var storage = new this._storageConfigClass(configId);
		this.updateStatus($tr, StorageConfig.Status.IN_PROGRESS);

		storage.destroy({
			success: function() {
				$tr.remove();
			},
			error: function() {
				self.updateStatus($tr, StorageConfig.Status.ERROR);
			}
		});
	},

	/**
	 * Saves the storage from the given tr
	 *
	 * @param $tr storage row
	 * @param Function callback callback to call after save
	 */
	saveStorageConfig:function($tr, callback) {
		var self = this;
		var storage = this.getStorageConfig($tr);
		if (!storage.validate()) {
			return false;
		}

		this.updateStatus($tr, StorageConfig.Status.IN_PROGRESS);
		storage.save({
			success: function(result) {
				self.updateStatus($tr, result.status);
				$tr.attr('data-id', result.id);

				if (_.isFunction(callback)) {
					callback(storage);
				}
			},
			error: function() {
				self.updateStatus($tr, StorageConfig.Status.ERROR);
			}
		});
	},

	/**
	 * Recheck storage availability
	 *
	 * @param {jQuery} $tr storage row
	 * @return {boolean} success
	 */
	recheckStorageConfig: function($tr) {
		var self = this;
		var storage = this.getStorageConfig($tr);
		if (!storage.validate()) {
			return false;
		}

		this.updateStatus($tr, StorageConfig.Status.IN_PROGRESS);
		storage.recheck({
			success: function(result) {
				self.updateStatus($tr, result.status);
			},
			error: function() {
				self.updateStatus($tr, StorageConfig.Status.ERROR);
			}
		});
	},

	/**
	 * Update status display
	 *
	 * @param {jQuery} $tr
	 * @param {int} status
	 */
	updateStatus: function($tr, status) {
		var $statusSpan = $tr.find('.status span');
		$statusSpan.removeClass('loading-small success indeterminate error');
		switch (status) {
			case StorageConfig.Status.IN_PROGRESS:
				$statusSpan.addClass('loading-small');
				break;
			case StorageConfig.Status.SUCCESS:
				$statusSpan.addClass('success');
				break;
			case StorageConfig.Status.INDETERMINATE:
				$statusSpan.addClass('indeterminate');
				break;
			default:
				$statusSpan.addClass('error');
		}
	},

	/**
	 * Suggest mount point name that doesn't conflict with the existing names in the list
	 *
	 * @param {string} defaultMountPoint default name
	 */
	_suggestMountPoint: function(defaultMountPoint) {
		var $el = this.$el;
		var pos = defaultMountPoint.indexOf('/');
		if (pos !== -1) {
			defaultMountPoint = defaultMountPoint.substring(0, pos);
		}
		defaultMountPoint = defaultMountPoint.replace(/\s+/g, '');
		var i = 1;
		var append = '';
		var match = true;
		while (match && i < 20) {
			match = false;
			$el.find('tbody td.mountPoint input').each(function(index, mountPoint) {
				if ($(mountPoint).val() === defaultMountPoint+append) {
					match = true;
					return false;
				}
			});
			if (match) {
				append = i;
				i++;
			} else {
				break;
			}
		}
		return defaultMountPoint + append;
	},
	
	/**
	 * Toggles the mount options dropdown
	 *
	 * @param {Object} $tr configuration row
	 */	
	_showMountOptionsDropdown: function($tr) {
		if (this._preventNextDropdown) {
			// prevented because the click was on the toggle
			this._preventNextDropdown = false;
			return;
		}
		var self = this;
		var storage = this.getStorageConfig($tr);
		var $toggle = $tr.find('.mountOptionsToggle');
		var dropDown = new MountOptionsDropdown();
		var enabledOptions = ['previews', 'filesystem_check_changes'];
		if (this._encryptionEnabled) {
			enabledOptions.push('encrypt');
		}
		dropDown.show($toggle, storage.mountOptions || [], enabledOptions);
		$('body').on('mouseup.mountOptionsDropdown', function(event) {
			var $target = $(event.target);
			if ($toggle.has($target).length) {
				// why is it always so hard to make dropdowns behave ?
				// this prevents the click on the toggle to cause
				// the dropdown to reopen itself
				// (preventDefault doesn't work here because the click
				// event is already in the queue and cannot be cancelled)
				self._preventNextDropdown = true;
			}
			if ($target.closest('.dropdown').length) {
				return;
			}
			dropDown.hide();
		});

		dropDown.$el.on('hide', function() {
			var mountOptions = dropDown.getOptions();
			$('body').off('mouseup.mountOptionsDropdown');
			$tr.find('input.mountOptions').val(JSON.stringify(mountOptions));
			self.saveStorageConfig($tr);
		});
	}
};

$(document).ready(function() {
	var enabled = $('#files_external').attr('data-encryption-enabled');
	var encryptionEnabled = (enabled ==='true')? true: false;
	var mountConfigListView = new MountConfigListView($('#externalStorage'), {
		encryptionEnabled: encryptionEnabled
	});

	$('#sslCertificate').on('click', 'td.remove>img', function() {
		var $tr = $(this).closest('tr');
		$.post(OC.filePath('files_external', 'ajax', 'removeRootCertificate.php'), {cert: $tr.attr('id')});
		$tr.remove();
		return true;
	});

	// TODO: move this into its own View class
	var $allowUserMounting = $('#allowUserMounting');
	$allowUserMounting.bind('change', function() {
		OC.msg.startSaving('#userMountingMsg');
		if (this.checked) {
			OC.AppConfig.setValue('files_external', 'allow_user_mounting', 'yes');
			$('input[name="allowUserMountingBackends\\[\\]"]').prop('checked', true);
			$('#userMountingBackends').removeClass('hidden');
			$('input[name="allowUserMountingBackends\\[\\]"]').eq(0).trigger('change');
		} else {
			OC.AppConfig.setValue('files_external', 'allow_user_mounting', 'no');
			$('#userMountingBackends').addClass('hidden');
		}
		OC.msg.finishedSaving('#userMountingMsg', {status: 'success', data: {message: t('files_external', 'Saved')}});
	});

	$('input[name="allowUserMountingBackends\\[\\]"]').bind('change', function() {
		OC.msg.startSaving('#userMountingMsg');
		var userMountingBackends = $('input[name="allowUserMountingBackends\\[\\]"]:checked').map(function(){return $(this).val();}).get();
		OC.AppConfig.setValue('files_external', 'user_mounting_backends', userMountingBackends.join());
		OC.msg.finishedSaving('#userMountingMsg', {status: 'success', data: {message: t('files_external', 'Saved')}});

		// disable allowUserMounting
		if(userMountingBackends.length === 0) {
			$allowUserMounting.prop('checked', false);
			$allowUserMounting.trigger('change');

		}
	});

	// global instance
	OCA.External.Settings.mountConfig = mountConfigListView;

	/**
	 * Legacy
	 *
	 * @namespace
	 * @deprecated use OCA.External.Settings.mountConfig instead
	 */
	OC.MountConfig = {
		saveStorage: _.bind(mountConfigListView.saveStorageConfig, mountConfigListView)
	};
});

// export

OCA.External = OCA.External || {};
/**
 * @namespace
 */
OCA.External.Settings = OCA.External.Settings || {};

OCA.External.Settings.GlobalStorageConfig = GlobalStorageConfig;
OCA.External.Settings.UserStorageConfig = UserStorageConfig;
OCA.External.Settings.MountConfigListView = MountConfigListView;

})();
