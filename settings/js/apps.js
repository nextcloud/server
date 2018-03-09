/* global Handlebars */

Handlebars.registerHelper('score', function() {
	if(this.score) {
		var score = Math.round( this.score * 10 );
		var imageName = 'rating/s' + score + '.svg';

		return new Handlebars.SafeString('<img src="' + OC.imagePath('core', imageName) + '">');
	}
	return new Handlebars.SafeString('');
});
Handlebars.registerHelper('level', function() {
	if(typeof this.level !== 'undefined') {
		if(this.level === 200) {
			return new Handlebars.SafeString('<span class="official icon-checkmark">' + t('settings', 'Official') + '</span>');
		}
	}
});

OC.Settings = OC.Settings || {};
OC.Settings.Apps = OC.Settings.Apps || {
	markedOptions: {},

	setupGroupsSelect: function($elements) {
		OC.Settings.setupGroupsSelect($elements, {
			placeholder: t('core', 'All')
		});
	},

	State: {
		currentCategory: null,
		currentCategoryElements: null,
		apps: null,
		$updateNotification: null,
		availableUpdates: 0
	},

	loadCategories: function() {
		if (this._loadCategoriesCall) {
			this._loadCategoriesCall.abort();
		}

		var categories = [
			{displayName: t('settings', 'Your apps'), ident: 'installed', id: '0'},
			{displayName: t('settings', 'Enabled apps'), ident: 'enabled', id: '1'},
			{displayName: t('settings', 'Disabled apps'), ident: 'disabled', id: '2'}
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
				var updateCategory = $.grep(jsondata, function(element, index) {
					return element.ident === 'updates'
				});
				$('#apps-categories').html(html);
				$('#app-category-' + OC.Settings.Apps.State.currentCategory).addClass('active');
				if (updateCategory.length === 1) {
					OC.Settings.Apps.State.availableUpdates = updateCategory[0].counter;
					OC.Settings.Apps.refreshUpdateCounter();
				}
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

		$('#app-content').addClass('icon-loading');
		$('#apps-list')
			.removeClass('hidden')
			.html('');
		$('#apps-list-empty').addClass('hidden');
		$('#app-category-' + OC.Settings.Apps.State.currentCategory).removeClass('active');
		$('#app-category-' + categoryId).addClass('active');
		OC.Settings.Apps.State.currentCategory = categoryId;

		this._loadCategoryCall = $.ajax(OC.generateUrl('settings/apps/list?category={categoryId}', {
			categoryId: categoryId
		}), {
			type:'GET',
			success: function (apps) {
				OC.Settings.Apps.State.currentCategoryElements = apps.apps;
				var appListWithIndex = _.indexBy(apps.apps, 'id');
				OC.Settings.Apps.State.apps = appListWithIndex;
				var appList = _.map(appListWithIndex, function(app) {
					// default values for missing fields
					return _.extend({level: 0}, app);
				});
				var source;
				if (categoryId === 'enabled' || categoryId === 'updates' || categoryId === 'disabled' || categoryId === 'installed' || categoryId === 'app-bundles') {
					source = $("#app-template-installed").html();
					$('#apps-list').addClass('installed');
				} else {
					source = $("#app-template").html();
					$('#apps-list').removeClass('installed');
				}
				var template = Handlebars.compile(source);

				if (appList.length) {
					if(categoryId !== 'app-bundles') {
						appList.sort(function (a, b) {
							if (a.active !== b.active) {
								return (a.active ? -1 : 1)
							}
							if (a.update !== b.update) {
								return (a.update ? -1 : 1)
							}
							return OC.Util.naturalSortCompare(a.name, b.name);
						});
					}

					var firstExperimental = false;
					var hasNewUpdates = false;
					_.each(appList, function(app) {
						if(app.level === 0 && firstExperimental === false) {
							firstExperimental = true;
							OC.Settings.Apps.renderApp(app, template, null, true);
						} else {
							OC.Settings.Apps.renderApp(app, template, null, false);
						}

						if (app.update) {
							hasNewUpdates = true;
							var $update = $('#app-' + app.id + ' .update');
							$update.removeClass('hidden');
							$update.val(t('settings', 'Update to %s').replace(/%s/g, app.update));
						}
					});
					// reload updates if a list with new updates is loaded
					if (hasNewUpdates) {
						OC.Settings.Apps.reloadUpdates();
					} else {
						// hide update category after all updates are installed
						// and the user is switching away from the empty updates view
						OC.Settings.Apps.refreshUpdateCounter();
					}
				} else {
					if (categoryId === 'updates') {
						OC.Settings.Apps.showEmptyUpdates();
					} else {
						$('#apps-list').addClass('hidden');
						$('#apps-list-empty').removeClass('hidden').find('h2').text(t('settings', 'No apps found for your version'));
						$('#app-list-empty-icon').addClass('icon-search').removeClass('icon-download');
					}
				}

				$('.enable.needs-download').tooltip({
					title: t('settings', 'The app will be downloaded from the app store'),
					placement: 'bottom',
					container: 'body'
				});

				$('.app-level .official').tooltip({
					title: t('settings', 'Official apps are developed by and within the community. They offer central functionality and are ready for production use.'),
					placement: 'bottom',
					container: 'body'
				});
				$('.app-level .approved').tooltip({
					title: t('settings', 'Approved apps are developed by trusted developers and have passed a cursory security check. They are actively maintained in an open code repository and their maintainers deem them to be stable for casual to normal use.'),
					placement: 'bottom',
					container: 'body'
				});
				$('.app-level .experimental').tooltip({
					title: t('settings', 'This app is not checked for security issues and is new or known to be unstable. Install at your own risk.'),
					placement: 'bottom',
					container: 'body'
				});
			},
			complete: function() {
				$('#app-content').removeClass('icon-loading');
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
			app.preview = OC.imagePath('core', 'places/default-app-icon');
			app.previewAsIcon = true;
		}

		if (_.isArray(app.author)) {
			var authors = [];
			_.each(app.author, function (author) {
				if (typeof author === 'string') {
					authors.push(author);
				} else {
					authors.push(author['@value']);
				}
			});
			app.author = authors.join(', ');
		} else if (typeof app.author !== 'string') {
			app.author = app.author['@value'];
		}

		// Parse markdown in app description
		app.description = DOMPurify.sanitize(
			marked(app.description.trim(), OC.Settings.Apps.markedOptions),
			{
				SAFE_FOR_JQUERY: true,
				ALLOWED_TAGS: [
					'strong',
					'p',
					'a',
					'ul',
					'ol',
					'li',
					'em',
					'del',
					'blockquote'
				]
			}
		);

		var html = template(app);
		if (selector) {
			selector.html(html);
		} else {
			$('#apps-list').append(html);
		}

		var page = $('#app-' + app.id);

		if (app.preview) {
			var currentImage = new Image();
			currentImage.src = app.preview;

			currentImage.onload = function() {
				/* Trigger color inversion for placeholder image too */
				if(app.previewAsIcon) {
					page.find('.app-image')
						.append(OC.Settings.Apps.imageUrl(app.preview, false))
						.removeClass('icon-loading');
				} else {
					page.find('.app-image')
						.append(OC.Settings.Apps.imageUrl(app.preview, app.fromAppStore))
						.removeClass('icon-loading');
				}
			};
		}

		// set group select properly
		if(OC.Settings.Apps.isType(app, 'filesystem') || OC.Settings.Apps.isType(app, 'prelogin') ||
			OC.Settings.Apps.isType(app, 'authentication') || OC.Settings.Apps.isType(app, 'logging') ||
			OC.Settings.Apps.isType(app, 'prevent_group_restriction')) {
			page.find(".groups-enable").hide();
			page.find(".groups-enable__checkbox").prop('checked', false);
		} else {
			page.find('.group_select').val((app.groups || []).join('|'));
			if (app.active) {
				if (app.groups.length) {
					OC.Settings.Apps.setupGroupsSelect(page.find('.group_select'));
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

	/**
	 * Returns the image for apps listing
	 * url : the url of the image
	 * appfromstore: bool to check whether the app is fetched from store or not.
	 */

	imageUrl : function (url, appfromstore) {
		var img;
		if (appfromstore) {
			img = '<svg viewBox="0 0 72 72">';
			img += '<image x="0" y="0" width="72" height="72" preserveAspectRatio="xMinYMin meet" xlink:href="' + url  + '"  class="app-icon" /></svg>';
		} else {
			var rnd = Math.floor((Math.random() * 100 )) + new Date().getSeconds() + new Date().getMilliseconds();
			img = '<svg width="32" height="32" viewBox="0 0 32 32">';
			img += '<defs><filter id="invertIconApps-' + rnd + '"><feColorMatrix in="SourceGraphic" type="matrix" values="-1 0 0 0 1 0 -1 0 0 1 0 0 -1 0 1 0 0 0 1 0"></feColorMatrix></filter></defs>'
			img += '<image x="0" y="0" width="32" height="32" preserveAspectRatio="xMinYMin meet" filter="url(#invertIconApps-' + rnd + ')" xlink:href="' + url + '?v=' + oc_config.version + '" class="app-icon"></image></svg>';
		}
		return img;
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

	enableAppBundle:function(bundleId, active, element, groups) {
		if (OC.PasswordConfirmation.requiresPasswordConfirmation()) {
			OC.PasswordConfirmation.requirePasswordConfirmation(_.bind(this.enableAppBundle, this, bundleId, active, element, groups));
			return;
		}

		var apps = OC.Settings.Apps.State.currentCategoryElements;
		var appsToEnable = [];
		apps.forEach(function(app) {
			if(app['bundleId'] === bundleId) {
				if(app['active'] === false) {
					appsToEnable.push(app['id']);
				}
			}
		});

		OC.Settings.Apps.enableApp(appsToEnable, false, groups);
	},

		/**
		 * @param {string[]} appId
		 * @param {boolean} active
		 * @param {array} groups
		 */
	enableApp:function(appId, active, groups) {
		if (OC.PasswordConfirmation.requiresPasswordConfirmation()) {
			OC.PasswordConfirmation.requirePasswordConfirmation(_.bind(this.enableApp, this, appId, active, groups));
			return;
		}

		var elements = [];
		appId.forEach(function(appId) {
			elements.push($('#app-'+appId+' .enable'));
		});

		var self = this;
		appId.forEach(function(appId) {
			OC.Settings.Apps.hideErrorMessage(appId);
		});
		groups = groups || [];
		var appItems = [];
		appId.forEach(function(appId) {
			appItems.push($('div#app-'+appId+''));
		});

		if(active && !groups.length) {
			elements.forEach(function(element) {
				element.val(t('settings','Disabling app …'));
			});
			$.post(OC.filePath('settings','ajax','disableapp.php'),{appid:appId},function(result) {
				if(!result || result.status !== 'success') {
					if (result.data && result.data.message) {
						OC.Settings.Apps.showErrorMessage(appId, result.data.message);
						appItems.forEach(function(appItem) {
							appItem.data('errormsg', result.data.message);
						})
					} else {
						OC.Settings.Apps.showErrorMessage(appId, t('settings', 'Error while disabling app'));
						appItems.forEach(function(appItem) {
							appItem.data('errormsg', t('settings', 'Error while disabling app'));
						});
					}
					elements.forEach(function(element) {
						element.val(t('settings','Disable'));
					});
					appItems.forEach(function(appItem) {
						appItem.addClass('appwarning');
					});
				} else {
					OC.Settings.Apps.rebuildNavigation();
					appItems.forEach(function(appItem) {
						appItem.data('active', false);
						appItem.data('groups', '');
					});
					elements.forEach(function(element) {
						element.data('active', false);
					});
					appItems.forEach(function(appItem) {
						appItem.removeClass('active');
					});
					elements.forEach(function(element) {
						element.val(t('settings', 'Enable'));
						element.parent().find(".groups-enable").hide();
						element.parent().find('.group_select').hide().val(null);
					});
					OC.Settings.Apps.State.apps[appId].active = false;
				}
			},'json');
		} else {
			// TODO: display message to admin to not refresh the page!
			// TODO: lock UI to prevent further operations
			elements.forEach(function(element) {
				element.val(t('settings', 'Enabling app …'));
			});

			var appIdArray = [];
			if( typeof appId === 'string' ) {
				appIdArray = [appId];
			} else {
				appIdArray = appId;
			}
			$.post(OC.filePath('settings','ajax','enableapp.php'),{appIds: appIdArray, groups: groups},function(result) {
				if(!result || result.status !== 'success') {
					if (result.data && result.data.message) {
						OC.Settings.Apps.showErrorMessage(appId, result.data.message);
						appItems.forEach(function(appItem) {
							appItem.data('errormsg', result.data.message);
						});
					} else {
						OC.Settings.Apps.showErrorMessage(appId, t('settings', 'Error while enabling app'));
						appItems.forEach(function(appItem) {
							appItem.data('errormsg', t('settings', 'Error while disabling app'));
						});
					}
					elements.forEach(function(element) {
						element.val(t('settings', 'Enable'));
					});
					appItems.forEach(function(appItem) {
						appItem.addClass('appwarning');
					});
				} else {
					self._checkServerHealth().done(function() {
						if (result.data.update_required) {
							OC.Settings.Apps.showReloadMessage();

							setTimeout(function() {
								location.reload();
							}, 5000);
						}

						OC.Settings.Apps.rebuildNavigation();
						appItems.forEach(function(appItem) {
							appItem.data('active', true);
						});
						elements.forEach(function(element) {
							element.data('active', true);
						});
						appItems.forEach(function(appItem) {
							appItem.addClass('active');
						});
						elements.forEach(function(element) {
							element.val(t('settings', 'Disable'));
						});
						var app = OC.Settings.Apps.State.apps[appId];
						app.active = true;

						if (OC.Settings.Apps.isType(app, 'filesystem') || OC.Settings.Apps.isType(app, 'prelogin') ||
							OC.Settings.Apps.isType(app, 'authentication') || OC.Settings.Apps.isType(app, 'logging')) {
							elements.forEach(function(element) {
								element.parent().find(".groups-enable").prop('checked', true);
								element.parent().find(".groups-enable").hide();
								element.parent().find('.group_select').hide().val(null);
							});
						} else {
							elements.forEach(function(element) {
								element.parent().find("#groups-enable").show();
							});
							if (groups) {
								appItems.forEach(function(appItem) {
									appItem.data('groups', JSON.stringify(groups));
								});
							} else {
								appItems.forEach(function(appItem) {
									appItem.data('groups', '');
								});
							}
						}
					}).fail(function() {
						// server borked, emergency disable app
						$.post(OC.webroot + '/index.php/disableapp', {appid: appId}, function() {
							OC.Settings.Apps.showErrorMessage(
								appId,
								t('settings', 'Error: This app can not be enabled because it makes the server unstable')
							);
							appItems.forEach(function(appItem) {
								appItem.data('errormsg', t('settings', 'Error while enabling app'));
							});
							elements.forEach(function(element) {
								element.val(t('settings', 'Enable'));
							});
							appItems.forEach(function(appItem) {
								appItem.addClass('appwarning');
							});
						}).fail(function() {
							OC.Settings.Apps.showErrorMessage(
								appId,
								t('settings', 'Error: Could not disable broken app')
							);
							appItems.forEach(function(appItem) {
								appItem.data('errormsg', t('settings', 'Error while disabling broken app'));
							});
							elements.forEach(function(element) {
								element.val(t('settings', 'Enable'));
							});
						});
					});
				}
			},'json')
				.fail(function() {
					OC.Settings.Apps.showErrorMessage(appId, t('settings', 'Error while enabling app'));
					appItems.forEach(function(appItem) {
						appItem.data('errormsg', t('settings', 'Error while enabling app'));
						appItem.data('active', false);
						appItem.addClass('appwarning');
					});
					elements.forEach(function(element) {
						element.val(t('settings', 'Enable'));
					});
				});
		}
	},

	showEmptyUpdates: function() {
		$('#apps-list').addClass('hidden');
		$('#apps-list-empty').removeClass('hidden').find('h2').text(t('settings', 'App up to date'));
		$('#app-list-empty-icon').removeClass('icon-search').addClass('icon-download');
	},

	updateApp:function(appId, element) {
		var oldButtonText = element.val();
		element.val(t('settings','Updating …'));
		OC.Settings.Apps.hideErrorMessage(appId);
		$.post(OC.filePath('settings','ajax','updateapp.php'),{appid:appId},function(result) {
			if(!result || result.status !== 'success') {
				if (result.data && result.data.message) {
					OC.Settings.Apps.showErrorMessage(appId, result.data.message);
				} else {
					OC.Settings.Apps.showErrorMessage(appId, t('settings','Could not update app'));
				}
				element.val(oldButtonText);
			}
			else {
				element.val(t('settings','Updated'));
				element.hide();

				var $update = $('#app-' + appId + ' .update');
				$update.addClass('hidden');
				var $version = $('#app-' + appId + ' .app-version');
				$version.text(OC.Settings.Apps.State.apps[appId]['update']);

				OC.Settings.Apps.State.availableUpdates--;
				OC.Settings.Apps.refreshUpdateCounter();

				if (OC.Settings.Apps.State.currentCategory === 'updates') {
					$('#app-' + appId).remove();
					if (OC.Settings.Apps.State.availableUpdates === 0) {
						OC.Settings.Apps.showEmptyUpdates();
					}
				}
			}
		},'json');
	},

	uninstallApp:function(appId, element) {
		if (OC.PasswordConfirmation.requiresPasswordConfirmation()) {
			OC.PasswordConfirmation.requirePasswordConfirmation(_.bind(this.uninstallApp, this, appId, element));
			return;
		}

		OC.Settings.Apps.hideErrorMessage(appId);
		element.val(t('settings','Removing …'));
		$.post(OC.filePath('settings','ajax','uninstallapp.php'),{appid:appId},function(result) {
			if(!result || result.status !== 'success') {
				OC.Settings.Apps.showErrorMessage(appId, t('settings','Could not remove app'));
				element.val(t('settings','Remove'));
			} else {
				OC.Settings.Apps.rebuildNavigation();
				element.parents('#apps-list > .section').fadeOut(function() {
					this.remove();
				});
			}
		},'json');
	},

	rebuildNavigation: function() {
		$.getJSON(OC.filePath('settings', 'ajax', 'navigationdetect.php')).done(function(response){
			if(response.status === 'success') {
				var addedApps = {};
				var navEntries = response.nav_entries;
				var container = $('#apps ul');

				// remove disabled apps
				for (var i = 0; i < navEntries.length; i++) {
					var entry = navEntries[i];
					if(container.children('li[data-id="' + entry.id + '"]').length === 0) {
						addedApps[entry.id] = true;
					}
				}
				container.children('li[data-id]').each(function (index, el) {
					var id = $(el).data('id');
					// remove all apps that are not in the correct order
					if (!navEntries[index] || (navEntries[index] && navEntries[index].id !== $(el).data('id'))) {
						$(el).remove();
						$('#appmenu li[data-id='+id+']').remove();
					}
				});

				var previousEntry = {};
				// add enabled apps to #navigation and #appmenu
				for (var i = 0; i < navEntries.length; i++) {
					var entry = navEntries[i];
					if (container.children('li[data-id="' + entry.id + '"]').length === 0) {
						var li = $('<li></li>');
						li.attr('data-id', entry.id);
						var img = '<svg width="16" height="16" viewBox="0 0 16 16">';
						img += '<defs><filter id="invert"><feColorMatrix in="SourceGraphic" type="matrix" values="-1 0 0 0 1 0 -1 0 0 1 0 0 -1 0 1 0 0 0 1 0" /></filter></defs>';
						img += '<image x="0" y="0" width="16" height="16" preserveAspectRatio="xMinYMin meet" filter="url(#invert)" xlink:href="' + entry.icon + '"  class="app-icon" /></svg>';
						var a = $('<a></a>').attr('href', entry.href);
						var filename = $('<span></span>');
						var loading = $('<div class="icon-loading-dark"></div>').css('display', 'none');
						filename.text(entry.name);
						a.prepend(filename);
						a.prepend(loading);
						a.prepend(img);
						li.append(a);

						$('#navigation li[data-id=' + previousEntry.id + ']').after(li);

						// draw attention to the newly added app entry
						// by flashing it twice
						if(addedApps[entry.id]) {
							$('#header .menutoggle')
								.animate({opacity: 0.5})
								.animate({opacity: 1})
								.animate({opacity: 0.5})
								.animate({opacity: 1})
								.animate({opacity: 0.75});
						}
					}

					if ($('#appmenu').children('li[data-id="' + entry.id + '"]').length === 0) {
						var li = $('<li></li>');
						li.attr('data-id', entry.id);
						var img = '<img src="' + entry.icon + '" class="app-icon">';
						if (OCA.Theming && OCA.Theming.inverted) {
							img = '<svg width="20" height="20" viewBox="0 0 20 20">';
							img += '<defs><filter id="invert"><feColorMatrix in="SourceGraphic" type="matrix" values="-1 0 0 0 1 0 -1 0 0 1 0 0 -1 0 1 0 0 0 1 0" /></filter></defs>';
							img += '<image x="0" y="0" width="20" height="20" preserveAspectRatio="xMinYMin meet" filter="url(#invert)" xlink:href="' + entry.icon + '"  class="app-icon" /></svg>';
						}
						var a = $('<a></a>').attr('href', entry.href);
						var filename = $('<span></span>');
						var loading = $('<div class="icon-loading-dark"></div>').css('display', 'none');
						filename.text(entry.name);
						a.prepend(filename);
						a.prepend(loading);
						a.prepend(img);
						li.append(a);
						$('#appmenu li[data-id='+ previousEntry.id+']').after(li);
						if(addedApps[entry.id]) {
							li.animate({opacity: 0.5})
								.animate({opacity: 1})
								.animate({opacity: 0.5})
								.animate({opacity: 1});
						}
					}
					previousEntry = entry;
				}

				$(window).trigger('resize');
			}
		});
	},

	reloadUpdates: function() {
		if (this._loadUpdatesCall) {
			this._loadUpdatesCall.abort();
		}
		this._loadUpdatesCall = $.ajax(OC.generateUrl('settings/apps/list?category=updates'), {
			type:'GET',
			success: function (apps) {
				OC.Settings.Apps.State.availableUpdates = apps.apps.length;
				OC.Settings.Apps.refreshUpdateCounter();
			}
		});
	},

	refreshUpdateCounter: function() {
		var $appCategoryUpdates = $('#app-category-updates');
		var $updateCount = $appCategoryUpdates.find('.app-navigation-entry-utils-counter');
		if (OC.Settings.Apps.State.availableUpdates > 0) {
			$updateCount.html(OC.Settings.Apps.State.availableUpdates);
			$appCategoryUpdates.show();
		} else {
			$updateCount.empty();
			if (OC.Settings.Apps.State.currentCategory !== 'updates') {
				$appCategoryUpdates.hide();
			}
		}
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

	/**
	 * Splits the query by spaces and tries to find all substring in the app
	 * @param {string} string
	 * @param {string} query
	 * @returns {boolean}
	 */
	_search: function(string, query) {
		var keywords = query.split(' '),
			stringLower = string.toLowerCase(),
			found = true;

		_.each(keywords, function(keyword) {
			found = found && stringLower.indexOf(keyword) !== -1;
		});

		return found;
	},

	filter: function(query) {
		var $appList = $('#apps-list'),
			$emptyList = $('#apps-list-empty');
		$('#app-list-empty-icon').addClass('icon-search').removeClass('icon-download');
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
			return OC.Settings.Apps._search(app.name, query);
		});

		// App ID
		apps = apps.concat(_.filter(OC.Settings.Apps.State.apps, function (app) {
			return OC.Settings.Apps._search(app.id, query);
		}));

		// App Description
		apps = apps.concat(_.filter(OC.Settings.Apps.State.apps, function (app) {
			return OC.Settings.Apps._search(app.description, query);
		}));

		// Author Name
		apps = apps.concat(_.filter(OC.Settings.Apps.State.apps, function (app) {
			var authors = [];
			if (_.isArray(app.author)) {
				_.each(app.author, function (author) {
					if (typeof author === 'string') {
						authors.push(author);
					} else {
						authors.push(author['@value']);
						if (!_.isUndefined(author['@attributes']['homepage'])) {
							authors.push(author['@attributes']['homepage']);
						}
						if (!_.isUndefined(author['@attributes']['mail'])) {
							authors.push(author['@attributes']['mail']);
						}
					}
				});
				return OC.Settings.Apps._search(authors.join(' '), query);
			} else if (typeof app.author !== 'string') {
				authors.push(app.author['@value']);
				if (!_.isUndefined(app.author['@attributes']['homepage'])) {
					authors.push(app.author['@attributes']['homepage']);
				}
				if (!_.isUndefined(app.author['@attributes']['mail'])) {
					authors.push(app.author['@attributes']['mail']);
				}
				return OC.Settings.Apps._search(authors.join(' '), query);
			}
			return OC.Settings.Apps._search(app.author, query);
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

		var renderer = new marked.Renderer();
		renderer.link = function(href, title, text) {
			try {
				var prot = decodeURIComponent(unescape(href))
					.replace(/[^\w:]/g, '')
					.toLowerCase();
			} catch (e) {
				return '';
			}

			if (prot.indexOf('http:') !== 0 && prot.indexOf('https:') !== 0) {
				return '';
			}

			var out = '<a href="' + href + '" rel="noreferrer noopener"';
			if (title) {
				out += ' title="' + title + '"';
			}
			out += '>' + text + '</a>';
			return out;
		};
		renderer.image = function(href, title, text) {
			if (text) {
				return text;
			}
			return title;
		};
		renderer.blockquote = function(quote) {
			return quote;
		};

		OC.Settings.Apps.markedOptions = {
			renderer: renderer,
			gfm: false,
			highlight: false,
			tables: false,
			breaks: false,
			pedantic: false,
			sanitize: true,
			smartLists: true,
			smartypants: false
		};

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
			var bundleId = $(this).data('bundleid');
			var element = $(this);
			var active = $(this).data('active');

			var category = $('#app-navigation').attr('data-category');
			if(bundleId) {
				OC.Settings.Apps.enableAppBundle(bundleId, active, element);
				element.val(t('settings', 'Enable all'));
			} else {
				OC.Settings.Apps.enableApp([appId], active);
			}
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

		$(document).on('change', '.group_select', function() {
			var element = $(this).closest('.section').find('input.enable');
			var groups = $(this).val();
			if (groups && groups !== '') {
				groups = groups.split('|');
			} else {
				groups = [];
			}

			var appId = element.data('appid');
			if (appId) {
				OC.Settings.Apps.enableApp([appId], false, groups);
				OC.Settings.Apps.State.apps[appId].groups = groups;
			}
		});

		$(document).on('change', ".groups-enable__checkbox", function() {
			var $select = $(this).closest('.section').find('.group_select');
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
