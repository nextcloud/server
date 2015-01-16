/* global Handlebars */

Handlebars.registerHelper('score', function() {
	if(this.score) {
		var score = Math.round( this.score / 10 );
		var imageName = 'rating/s' + score + '.png';
		
		return new Handlebars.SafeString('<img src="' + OC.imagePath('core', imageName) + '">');
	}
	return new Handlebars.SafeString('');
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
			{displayName: 'Enabled', id: '0'}
		];

		var source   = $("#categories-template").html();
		var template = Handlebars.compile(source);
		var html = template(categories);
		$('#apps-categories').html(html);

		OC.Settings.Apps.loadCategory(0);

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
			.html('');
		$('#app-category-' + OC.Settings.Apps.State.currentCategory).removeClass('active');
		$('#app-category-' + categoryId).addClass('active');
		OC.Settings.Apps.State.currentCategory = categoryId;

		this._loadCategoryCall = $.ajax(OC.generateUrl('settings/apps/list?category={categoryId}', {
			categoryId: categoryId
		}), {
			data:{},
			type:'GET',
			success:function (apps) {
				OC.Settings.Apps.State.apps = _.indexBy(apps.apps, 'id');
				var source   = $("#app-template").html();
				var template = Handlebars.compile(source);

				_.each(apps.apps, function(app) {
					OC.Settings.Apps.renderApp(app, template, null);
				});
			},
			complete: function() {
				$('#apps-list').removeClass('icon-loading');
			}
		});
	},

	renderApp: function(app, template, selector) {
		if (!template) {
			var source   = $("#app-template").html();
			template = Handlebars.compile(source);
		}
		if (typeof app === 'string') {
			app = OC.Settings.Apps.State.apps[app];
		}

		var html = template(app);
		if (selector) {
			selector.html(html);
		} else {
			$('#apps-list').append(html);
		}

		var page = $('#app-' + app.id);

		// image loading kung-fu
		if (app.preview) {
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
			OC.Settings.Apps.isType(app, 'authentication') || OC.Settings.Apps.isType(app, 'logging')) {
			page.find(".groups-enable").hide();
			page.find("label[for='groups_enable-"+app.id+"']").hide();
			page.find(".groups-enable").attr('checked', null);
		} else {
			page.find('#group_select').val((app.groups || []).join(','));
			if (app.active) {
				if (app.groups.length) {
					OC.Settings.Apps.setupGroupsSelect(page.find('#group_select'));
					page.find(".groups-enable").attr('checked','checked');
				} else {
					page.find(".groups-enable").attr('checked', null);
				}
				page.find(".groups-enable").show();
				page.find("label[for='groups_enable-"+app.id+"']").show();
			} else {
				page.find(".groups-enable").hide();
				page.find("label[for='groups_enable-"+app.id+"']").hide();
			}
		}
	},

	isType: function(app, type){
		return app.types && app.types.indexOf(type) !== -1;
	},

	enableApp:function(appId, active, element, groups) {
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
					appItem.data('active',false);
					appItem.data('groups', '');
					element.data('active',false);
					OC.Settings.Apps.removeNavigation(appId);
					appItem.removeClass('active');
					element.val(t('settings','Enable'));
					element.parent().find(".groups-enable").hide();
					element.parent().find("#groups_enable-"+appId).hide();
					element.parent().find("label[for='groups_enable-"+appId+"']").hide();
					element.parent().find('#group_select').hide().val(null);
					OC.Settings.Apps.State.apps[appId].active = false;
				}
			},'json');
		} else {
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
					OC.Settings.Apps.addNavigation(appId);
					appItem.data('active',true);
					element.data('active',true);
					appItem.addClass('active');
					element.val(t('settings','Disable'));
					var app = OC.Settings.Apps.State.apps[appId];
					app.active = true;

					if (OC.Settings.Apps.isType(app, 'filesystem') || OC.Settings.Apps.isType(app, 'prelogin') ||
						OC.Settings.Apps.isType(app, 'authentication') || OC.Settings.Apps.isType(app, 'logging')) {
						element.parent().find(".groups-enable").attr('checked', null);
						element.parent().find("#groups_enable-"+appId).hide();
						element.parent().find("label[for='groups_enable-"+appId+"']").hide();
						element.parent().find(".groups-enable").hide();
						element.parent().find("#groups_enable-"+appId).hide();
						element.parent().find("label[for='groups_enable-"+appId+"']").hide();
						element.parent().find('#group_select').hide().val(null);
					} else {
						element.parent().find("#groups_enable-"+appId).show();
						element.parent().find("label[for='groups_enable-"+appId+"']").show();
						if (groups) {
							appItem.data('groups', JSON.stringify(groups));
						} else {
							appItem.data('groups', '');
						}
					}
				}
			},'json')
				.fail(function() {
					OC.Settings.Apps.showErrorMessage(appId, t('settings', 'Error while enabling app'));
					appItem.data('errormsg', t('settings', 'Error while enabling app'));
					appItem.data('active',false);
					appItem.addClass('appwarning');
					OC.Settings.Apps.removeNavigation(appId);
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
				OC.Settings.Apps.showErrorMessage(appId, t('settings','Error while updating app'));
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
				OC.Settings.Apps.removeNavigation(appId);
				element.parent().fadeOut(function() {
					element.remove();
				});
			}
		},'json');
	},

	removeNavigation: function(appId){
		$.getJSON(OC.filePath('settings', 'ajax', 'navigationdetect.php'), {app: appId}).done(function(response){
			if(response.status === 'success'){
				var navIds=response.nav_ids;
				for(var i=0; i< navIds.length; i++){
					$('#apps ul').children('li[data-id="'+navIds[i]+'"]').remove();
				}
			}
		});
	},
	addNavigation: function(appid){
		$.getJSON(OC.filePath('settings', 'ajax', 'navigationdetect.php'), {app: appid}).done(function(response){
			if(response.status === 'success'){
				var navEntries=response.nav_entries;
				for(var i=0; i< navEntries.length; i++){
					var entry = navEntries[i];
					var container = $('#apps ul');

					if(container.children('li[data-id="'+entry.id+'"]').length === 0){
						var li=$('<li></li>');
						li.attr('data-id', entry.id);
						var img= $('<img class="app-icon"/>').attr({ src: entry.icon});
						var a=$('<a></a>').attr('href', entry.href);
						var filename=$('<span></span>');
						filename.text(entry.name);
						a.prepend(filename);
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
	}

};

$(document).ready(function () {
	OC.Settings.Apps.loadCategories();

	$(document).on('click', 'ul#apps-categories li', function () {
		var categoryId = $(this).data('categoryId');
		OC.Settings.Apps.loadCategory(categoryId);
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
			groups = groups.split(',');
		} else {
			groups = [];
		}

		var appId = element.data('appid');
		if (appId) {
			OC.Settings.Apps.enableApp(appId, false, element, groups);
			OC.Settings.Apps.State.apps[appId].groups = groups;
		}
	});

	$(document).on('change', ".groups-enable", function() {
		var $select = $(this).parent().find('#group_select');
		$select.val('');

		if (this.checked) {
			OC.Settings.Apps.setupGroupsSelect($select);
		} else {
			$select.select2('destroy');
		}

		$select.change();
	});

});
