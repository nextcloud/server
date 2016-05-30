/* global Handlebars */

Handlebars.registerHelper('score', function() {
	if(this.score) {
		var score = Math.round( this.score / 10 );
		var imageName = 'rating/s' + score + '.svg';

		return new Handlebars.SafeString('<img src="' + OC.imagePath('core', imageName) + '">');
	}
	return new Handlebars.SafeString('');
});
Handlebars.registerHelper('level', function() {
	if(typeof this.level !== 'undefined') {
		if(this.level === 200) {
			return new Handlebars.SafeString('<span class="official icon-checkmark">' + t('settings', 'Official') + '</span>');
		} else if(this.level === 100) {
			return new Handlebars.SafeString('<span class="approved">' + t('settings', 'Approved') + '</span>');
		} else {
			return new Handlebars.SafeString('<span class="experimental">' + t('settings', 'Experimental') + '</span>');
		}
	}
});

OC.Settings = OC.Settings || {};
OC.Settings.Apps = OC.Settings.Apps || {
	setupGroupsSelect: function($elements) {
		OC.Settings.setupGroupsSelect($elements, {
			placeholder: t('core', 'All')
		});
	},

	State: {
		currentCategory: null,
		apps: null
	},

	loadCategories: function() {
		if (this._loadCategoriesCall) {
			this._loadCategoriesCall.abort();
		}

		var categories = [
			{displayName: t('settings', 'Enabled'), ident: 'enabled', id: '0'},
			{displayName: t('settings', 'Not enabled'), ident: 'disabled', id: '1'}
		];

		var source   = $("#categories-template").html();
		var template = Handlebars.compile(source);
		var html = template(categories);
		$('#apps-categories').html(html);

		OC.Settings.Apps.loadCategory($('#app-navigation').attr('data-category'));

		this._loadCategoriesCall = $.ajax(OC.generateUrl('settings/apps/categories'), {
			data:{},
			type:'GET',
			success:function (jsondata) {
				var html = template(jsondata);
				$('#apps-categories').html(html);
				$('#app-category-' + OC.Settings.Apps.State.currentCategory).addClass('active');
			},
			complete: function() {
				$('#app-navigation').removeClass('icon-loading');
			}
		});

	},

	loadCategory: function(categoryId) {
		if (OC.Settings.Apps.State.currentCategory === categoryId) {
			return;
		}
		if (this._loadCategoryCall) {
			this._loadCategoryCall.abort();
		}
		$('#apps-list')
			.addClass('icon-loading')
			.removeClass('hidden')
			.html('');
		$('#apps-list-empty').addClass('hidden');
		$('#app-category-' + OC.Settings.Apps.State.currentCategory).removeClass('active');
		$('#app-category-' + categoryId).addClass('active');
		OC.Settings.Apps.State.currentCategory = categoryId;

		this._loadCategoryCall = $.ajax(OC.generateUrl('settings/apps/list?category={categoryId}&includeUpdateInfo=0', {
			categoryId: categoryId
		}), {
			type:'GET',
			success: function (apps) {
				var appListWithIndex = _.indexBy(apps.apps, 'id');
				OC.Settings.Apps.State.apps = appListWithIndex;
				var appList = _.map(appListWithIndex, function(app) {
					// default values for missing fields
					return _.extend({level: 0}, app);
				});
				var source   = $("#app-template").html();
				var template = Handlebars.compile(source);

				if (appList.length) {
					appList.sort(function(a,b) {
						var levelDiff = b.level - a.level;
						if (levelDiff === 0) {
							return OC.Util.naturalSortCompare(a.name, b.name);
						}
						return levelDiff;
					});

					var firstExperimental = false;
					_.each(appList, function(app) {
						if(app.level === 0 && firstExperimental === false) {
							firstExperimental = true;
							OC.Settings.Apps.renderApp(app, template, null, true);
						} else {
							OC.Settings.Apps.renderApp(app, template, null, false);
						}
					});
				} else {
					$('#apps-list').addClass('hidden');
					$('#apps-list-empty').removeClass('hidden').find('h2').text(t('settings', 'No apps found for your version'));
				}

				$('.enable.needs-download').tipsy({fallback: t('settings', 'The app will be downloaded from the app store')});

				$('.app-level .official').tipsy({fallback: t('settings', 'Official apps are developed by and within the ownCloud community. They offer functionality central to ownCloud and are ready for production use.')});
				$('.app-level .approved').tipsy({fallback: t('settings', 'Approved apps are developed by trusted developers and have passed a cursory security check. They are actively maintained in an open code repository and their maintainers deem them to be stable for casual to normal use.')});
				$('.app-level .experimental').tipsy({fallback: t('settings', 'This app is not checked for security issues and is new or known to be unstable. Install at your own risk.')});
			},
			complete: function() {
				var availableUpdates = 0;
				$('#apps-list').removeClass('icon-loading');
				$.ajax(OC.generateUrl('settings/apps/list?category={categoryId}&includeUpdateInfo=1', {
					categoryId: categoryId
				}), {
					type: 'GET',
					success: function (apps) {
						_.each(apps.apps, function(app) {
							if (app.update) {
								var $update = $('#app-' + app.id + ' .update');
								$update.removeClass('hidden');
								$update.val(t('settings', 'Update to %s').replace(/%s/g, app.update));
								availableUpdates++;
								OC.Settings.Apps.State.apps[app.id].update = true;
							}
						});

						if (availableUpdates > 0) {
							OC.Notification.show(n('settings', 'You have %n app update pending', 'You have %n app updates pending', availableUpdates));
						}
					}
				});
			}
		});
	},

	renderApp: function(app, template, selector, firstExperimental) {
		if (!template) {
			var source   = $("#app-template").html();
			template = Handlebars.compile(source);
		}
		if (typeof app === 'string') {
			app = OC.Settings.Apps.State.apps[app];
		}
		app.firstExperimental = firstExperimental;

		if (!app.preview) {
			app.preview = OC.imagePath('core', 'default-app-icon');
			app.previewAsIcon = true;
		}

		var html = template(app);
		if (selector) {
			selector.html(html);
		} else {
			$('#apps-list').append(html);
		}

		var page = $('#app-' + app.id);

		// image loading kung-fu (IE doesn't properly scale SVGs, so disable app icons)
		if (app.preview && !OC.Util.isIE()) {
			var currentImage = new Image();
			currentImage.src = app.preview;

			currentImage.onload = function() {
				page.find('.app-image')
					.append(this)
					.fadeIn();
			};
		}

		// set group select properly
		if(OC.Settings.Apps.isType(app, 'filesystem') || OC.Settings.Apps.isType(app, 'prelogin') ||
			OC.Settings.Apps.isType(app, 'authentication') || OC.Settings.Apps.isType(app, 'logging') ||
			OC.Settings.Apps.isType(app, 'prevent_group_restriction')) {
			page.find(".groups-enable").hide();
			page.find(".groups-enable__checkbox").prop('checked', false);
		} else {
			page.find('#group_select').val((app.groups || []).join('|'));
			if (app.active) {
				if (app.groups.length) {
					OC.Settings.Apps.setupGroupsSelect(page.find('#group_select'));
					page.find(".groups-enable__checkbox").prop('checked', true);
				} else {
					page.find(".groups-enable__checkbox").prop('checked', false);
				}
				page.find(".groups-enable").show();
			} else {
				page.find(".groups-enable").hide();
			}
		}
	},

	isType: function(app, type){
		return app.types && app.types.indexOf(type) !== -1;
	},

	/**
	 * Checks the server health.
	 *
	 * If the promise fails, the server is broken.
	 *
	 * @return {Promise} promise
	 */
	_checkServerHealth: function() {
		return $.get(OC.generateUrl('apps/files'));
	},

	enableApp:function(appId, active, element, groups) {
		var self = this;
		OC.Settings.Apps.hideErrorMessage(appId);
		groups = groups || [];
		var appItem = $('div#app-'+appId+'');
		element.val(t('settings','Please wait....'));
		if(active && !groups.length) {
			$.post(OC.filePath('settings','ajax','disableapp.php'),{appid:appId},function(result) {
				if(!result || result.status !== 'success') {
					if (result.data && result.data.message) {
						OC.Settings.Apps.showErrorMessage(appId, result.data.message);
						appItem.data('errormsg', result.data.message);
					} else {
						OC.Settings.Apps.showErrorMessage(appId, t('settings', 'Error while disabling app'));
						appItem.data('errormsg', t('settings', 'Error while disabling app'));
					}
					element.val(t('settings','Disable'));
					appItem.addClass('appwarning');
				} else {
					OC.Settings.Apps.rebuildNavigation();
					appItem.data('active',false);
					appItem.data('groups', '');
					element.data('active',false);
					appItem.removeClass('active');
					element.val(t('settings','Enable'));
					element.parent().find(".groups-enable").hide();
					element.parent().find('#group_select').hide().val(null);
					OC.Settings.Apps.State.apps[appId].active = false;
				}
			},'json');
		} else {
			// TODO: display message to admin to not refresh the page!
			// TODO: lock UI to prevent further operations
			$.post(OC.filePath('settings','ajax','enableapp.php'),{appid: appId, groups: groups},function(result) {
				if(!result || result.status !== 'success') {
					if (result.data && result.data.message) {
						OC.Settings.Apps.showErrorMessage(appId, result.data.message);
						appItem.data('errormsg', result.data.message);
					} else {
						OC.Settings.Apps.showErrorMessage(appId, t('settings', 'Error while enabling app'));
						appItem.data('errormsg', t('settings', 'Error while disabling app'));
					}
					element.val(t('settings','Enable'));
					appItem.addClass('appwarning');
				} else {
					self._checkServerHealth().done(function() {
						if (result.data.update_required) {
							OC.Settings.Apps.showReloadMessage();

							setTimeout(function() {
								location.reload();
							}, 5000);
						}

						OC.Settings.Apps.rebuildNavigation();
						appItem.data('active',true);
						element.data('active',true);
						appItem.addClass('active');
						element.val(t('settings','Disable'));
						var app = OC.Settings.Apps.State.apps[appId];
						app.active = true;

						if (OC.Settings.Apps.isType(app, 'filesystem') || OC.Settings.Apps.isType(app, 'prelogin') ||
							OC.Settings.Apps.isType(app, 'authentication') || OC.Settings.Apps.isType(app, 'logging')) {
							element.parent().find(".groups-enable").prop('checked', true);
							element.parent().find(".groups-enable").hide();
							element.parent().find('#group_select').hide().val(null);
						} else {
							element.parent().find("#groups-enable").show();
							if (groups) {
								appItem.data('groups', JSON.stringify(groups));
							} else {
								appItem.data('groups', '');
							}
						}
					}).fail(function() {
						// server borked, emergency disable app
						$.post(OC.webroot + '/index.php/disableapp', {appid: appId}, function() {
							OC.Settings.Apps.showErrorMessage(
								appId,
								t('settings', 'Error: this app cannot be enabled because it makes the server unstable')
							);
							appItem.data('errormsg', t('settings', 'Error while enabling app'));
							element.val(t('settings','Enable'));
							appItem.addClass('appwarning');
						}).fail(function() {
							OC.Settings.Apps.showErrorMessage(
								appId,
								t('settings', 'Error: could not disable broken app')
							);
							appItem.data('errormsg', t('settings', 'Error while disabling broken app'));
							element.val(t('settings','Enable'));
						});
					});
				}
			},'json')
				.fail(function() {
					OC.Settings.Apps.showErrorMessage(appId, t('settings', 'Error while enabling app'));
					appItem.data('errormsg', t('settings', 'Error while enabling app'));
					appItem.data('active',false);
					appItem.addClass('appwarning');
					element.val(t('settings','Enable'));
				});
		}
	},

	updateApp:function(appId, element) {
		var oldButtonText = element.val();
		element.val(t('settings','Updating....'));
		OC.Settings.Apps.hideErrorMessage(appId);
		$.post(OC.filePath('settings','ajax','updateapp.php'),{appid:appId},function(result) {
			if(!result || result.status !== 'success') {
				if (result.data && result.data.message) {
					OC.Settings.Apps.showErrorMessage(appId, result.data.message);
				} else {
					OC.Settings.Apps.showErrorMessage(appId, t('settings','Error while updating app'));
				}
				element.val(oldButtonText);
			}
			else {
				element.val(t('settings','Updated'));
				element.hide();
			}
		},'json');
	},

	uninstallApp:function(appId, element) {
		OC.Settings.Apps.hideErrorMessage(appId);
		element.val(t('settings','Uninstalling ....'));
		$.post(OC.filePath('settings','ajax','uninstallapp.php'),{appid:appId},function(result) {
			if(!result || result.status !== 'success') {
				OC.Settings.Apps.showErrorMessage(appId, t('settings','Error while uninstalling app'));
				element.val(t('settings','Uninstall'));
			} else {
				OC.Settings.Apps.rebuildNavigation();
				element.parent().fadeOut(function() {
					element.remove();
				});
			}
		},'json');
	},

	rebuildNavigation: function() {
		$.getJSON(OC.filePath('settings', 'ajax', 'navigationdetect.php')).done(function(response){
			if(response.status === 'success'){
				var idsToKeep = {};
				var navEntries=response.nav_entries;
				var container = $('#apps ul');
				for(var i=0; i< navEntries.length; i++){
					var entry = navEntries[i];
					idsToKeep[entry.id] = true;

					if(container.children('li[data-id="'+entry.id+'"]').length === 0){
						var li=$('<li></li>');
						li.attr('data-id', entry.id);
						var img= $('<img class="app-icon"/>').attr({ src: entry.icon});
						var a=$('<a></a>').attr('href', entry.href);
						var filename=$('<span></span>');
						var loading = $('<div class="icon-loading-dark"></div>').css('display', 'none');
						filename.text(entry.name);
						a.prepend(filename);
						a.prepend(loading);
						a.prepend(img);
						li.append(a);

						// append the new app as last item in the list
						// which is the "add apps" entry with the id
						// #apps-management
						$('#apps-management').before(li);

						// scroll the app navigation down
						// so the newly added app is seen
						$('#navigation').animate({
							scrollTop: $('#navigation').height()
						}, 'slow');

						// draw attention to the newly added app entry
						// by flashing it twice
						$('#header .menutoggle')
							.animate({opacity: 0.5})
							.animate({opacity: 1})
							.animate({opacity: 0.5})
							.animate({opacity: 1})
							.animate({opacity: 0.75});

						if (!OC.Util.hasSVGSupport() && entry.icon.match(/\.svg$/i)) {
							$(img).addClass('svg');
							OC.Util.replaceSVG();
						}
					}
				}

				container.children('li[data-id]').each(function(index, el) {
					if (!idsToKeep[$(el).data('id')]) {
						$(el).remove();
					}
				});
			}
		});
	},

	showErrorMessage: function(appId, message) {
		$('div#app-'+appId+' .warning')
			.show()
			.text(message);
	},

	hideErrorMessage: function(appId) {
		$('div#app-'+appId+' .warning')
			.hide()
			.text('');
	},

	showReloadMessage: function() {
		OC.dialogs.info(
			t(
				'settings',
				'The app has been enabled but needs to be updated. You will be redirected to the update page in 5 seconds.'
			),
			t('settings','App update'),
			function () {
				window.location.reload();
			},
			true
		);
	},

	filter: function(query) {
		var $appList = $('#apps-list'),
			$emptyList = $('#apps-list-empty');
		$appList.removeClass('hidden');
		$appList.find('.section').removeClass('hidden');
		$emptyList.addClass('hidden');

		if (query === '') {
			return;
		}

		query = query.toLowerCase();
		$appList.find('.section').addClass('hidden');

		// App Name
		var apps = _.filter(OC.Settings.Apps.State.apps, function (app) {
			return app.name.toLowerCase().indexOf(query) !== -1;
		});

		// App ID
		apps = apps.concat(_.filter(OC.Settings.Apps.State.apps, function (app) {
			return app.id.toLowerCase().indexOf(query) !== -1;
		}));

		// App Description
		apps = apps.concat(_.filter(OC.Settings.Apps.State.apps, function (app) {
			return app.description.toLowerCase().indexOf(query) !== -1;
		}));

		// Author Name
		apps = apps.concat(_.filter(OC.Settings.Apps.State.apps, function (app) {
			return app.author.toLowerCase().indexOf(query) !== -1;
		}));

		// App status
		if (t('settings', 'Official').toLowerCase().indexOf(query) !== -1) {
			apps = apps.concat(_.filter(OC.Settings.Apps.State.apps, function (app) {
				return app.level === 200;
			}));
		}
		if (t('settings', 'Approved').toLowerCase().indexOf(query) !== -1) {
			apps = apps.concat(_.filter(OC.Settings.Apps.State.apps, function (app) {
				return app.level === 100;
			}));
		}
		if (t('settings', 'Experimental').toLowerCase().indexOf(query) !== -1) {
			apps = apps.concat(_.filter(OC.Settings.Apps.State.apps, function (app) {
				return app.level !== 100 && app.level !== 200;
			}));
		}

		apps = _.uniq(apps, function(app){return app.id;});

		if (apps.length === 0) {
			$appList.addClass('hidden');
			$emptyList.removeClass('hidden');
			$emptyList.removeClass('hidden').find('h2').text(t('settings', 'No apps found for {query}', {
				query: query
			}));
		} else {
			_.each(apps, function (app) {
				$('#app-' + app.id).removeClass('hidden');
			});

			$('#searchresults').hide();
		}
	},

	_onPopState: function(params) {
		params = _.extend({
			category: 'enabled'
		}, params);

		OC.Settings.Apps.loadCategory(params.category);
	},

	/**
	 * Initializes the apps list
	 */
	initialize: function($el) {
		OC.Plugins.register('OCA.Search', OC.Settings.Apps.Search);
		OC.Settings.Apps.loadCategories();
		OC.Util.History.addOnPopStateHandler(_.bind(this._onPopState, this));

		$(document).on('click', 'ul#apps-categories li', function () {
			var categoryId = $(this).data('categoryId');
			OC.Settings.Apps.loadCategory(categoryId);
			OC.Util.History.pushState({
				category: categoryId
			});
			$('#searchbox').val('');
		});

		$(document).on('click', '.app-description-toggle-show', function () {
			$(this).addClass('hidden');
			$(this).siblings('.app-description-toggle-hide').removeClass('hidden');
			$(this).siblings('.app-description-container').slideDown();
		});
		$(document).on('click', '.app-description-toggle-hide', function () {
			$(this).addClass('hidden');
			$(this).siblings('.app-description-toggle-show').removeClass('hidden');
			$(this).siblings('.app-description-container').slideUp();
		});

		$(document).on('click', '#apps-list input.enable', function () {
			var appId = $(this).data('appid');
			var element = $(this);
			var active = $(this).data('active');

			OC.Settings.Apps.enableApp(appId, active, element);
		});

		$(document).on('click', '#apps-list input.uninstall', function () {
			var appId = $(this).data('appid');
			var element = $(this);

			OC.Settings.Apps.uninstallApp(appId, element);
		});

		$(document).on('click', '#apps-list input.update', function () {
			var appId = $(this).data('appid');
			var element = $(this);

			OC.Settings.Apps.updateApp(appId, element);
		});

		$(document).on('change', '#group_select', function() {
			var element = $(this).parent().find('input.enable');
			var groups = $(this).val();
			if (groups && groups !== '') {
				groups = groups.split('|');
			} else {
				groups = [];
			}

			var appId = element.data('appid');
			if (appId) {
				OC.Settings.Apps.enableApp(appId, false, element, groups);
				OC.Settings.Apps.State.apps[appId].groups = groups;
			}
		});

		$(document).on('change', ".groups-enable__checkbox", function() {
			var $select = $(this).closest('.section').find('#group_select');
			$select.val('');

			if (this.checked) {
				OC.Settings.Apps.setupGroupsSelect($select);
			} else {
				$select.select2('destroy');
			}

			$select.change();
		});

		$(document).on('click', '#enable-experimental-apps', function () {
			var state = $(this).prop('checked');
			$.ajax(OC.generateUrl('settings/apps/experimental'), {
				data: {state: state},
				type: 'POST',
				success:function () {
					location.reload();
				}
			});
		});
	}
};

OC.Settings.Apps.Search = {
	attach: function (search) {
		search.setFilter('settings', OC.Settings.Apps.filter);
	}
};

$(document).ready(function () {
	// HACK: FIXME: use plugin approach
	if (!window.TESTING) {
		OC.Settings.Apps.initialize($('#apps-list'));
	}
});
