/**
 * Copyright (c) 2011, Robin Appelman <icewind1991@gmail.com>
 * Copyright (c) 2012, Thomas Tanghus <thomas@tanghus.net>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING-README file.
 */

OC.Settings = OC.Settings || {};
OC.Settings.Apps = OC.Settings.Apps || {
	loadApp:function(app) {
		var page = $('#app-content');
		page.find('p.license').show();
		page.find('span.name').text(app.name);
		page.find('small.externalapp').text(app.internallabel);
		if (app.version) {
			page.find('span.version').text(app.version);
		} else {
			page.find('span.version').text('');
		}
		page.find('span.score').html(app.score);
		page.find('p.description').text(app.description);
		page.find('img.preview').attr('src', app.preview);
		if (app.preview && app.preview.length) {
			page.find('img.preview').show();
		} else {
			page.find('img.preview').hide();
		}
		page.find('small.externalapp').attr('style', 'visibility:visible');
		page.find('span.author').text(app.author);

		// FIXME licenses of downloaded apps go into app.licence, licenses of not-downloaded apps into app.license
		var appLicense = '';
		if (typeof(app.licence) !== 'undefined') {
			appLicense = app.licence;
		} else if (typeof(app.license) !== 'undefined') {
			appLicense = app.license;
		}
		page.find('span.licence').text(appLicense);

		var userDocumentation = false;
		var adminDocumentation = false;
		if (typeof(app.documentation) !== 'undefined') {
			if (typeof(app.documentation.user) !== 'undefined') {
				userDocumentation = true;
				page.find('span.userDocumentation').html("<a id='userDocumentation' href='" + app.documentation.user + "'>" + t('settings', 'User Documentation') + "</a>");
				page.find('p.documentation').show();
			}
			if (typeof(app.documentation.admin) !== 'undefined') {
				adminDocumentation = true;
				page.find('span.adminDocumentation').html("<a id='adminDocumentation' href='" + app.documentation.admin + "'>" + t('settings', 'Admin Documentation') + "</a>");
				page.find('p.documentation').show();
			}

			if(userDocumentation && adminDocumentation) {
				page.find('span.userDocumentation').after(', ');
			}
		}

		if (typeof(app.website) !== 'undefined') {
			page.find('p.website').show();
			page.find('a#websitelink').attr('href', app.website);
		}

		if (app.update !== false) {
			page.find('input.update').show();
			page.find('input.update').data('appid', app.id);
			page.find('input.update').attr('value',t('settings', 'Update to {appversion}', {appversion:app.update}));
		} else {
			page.find('input.update').hide();
		}

		page.find('input.enable').show();
		page.find('input.enable').val((app.active) ? t('settings', 'Disable') : t('settings', 'Enable'));
		page.find('input.enable').data('appid', app.id);
		page.find('input.enable').data('active', app.active);
		if (app.internal === false) {
			page.find('span.score').show();
			page.find('p.appstore').show();
			page.find('a#appstorelink').attr('href', 'http://apps.owncloud.com/content/show.php?content=' + app.id);
			page.find('small.externalapp').hide();
		} else {
			page.find('p.appslink').hide();
			page.find('span.score').hide();
		}
		if (typeof($('#app-navigation ul li[data-id="'+app.id+'"]').data('errormsg')) !== "undefined") {
			page.find(".warning").show();
			page.find(".warning").text($('#app-navigation ul li[data-id="'+app.id+'"]').data('errormsg'));
		} else {
			page.find(".warning").hide();
		}

		page.find("div.multiselect").parent().remove();
		if(OC.Settings.Apps.isType(app, 'filesystem') || OC.Settings.Apps.isType(app, 'prelogin') ||
			OC.Settings.Apps.isType(app, 'authentication') || OC.Settings.Apps.isType(app, 'logging')) {
			page.find("#groups_enable").hide();
			page.find("label[for='groups_enable']").hide();
			page.find("#groups_enable").attr('checked', null);
		} else {
			$('#group_select > option').each(function (i, el) {
				if (app.groups.length === 0 || app.groups.indexOf(el.value) >= 0) {
					$(el).attr('selected', 'selected');
				} else {
					$(el).attr('selected', null);
				}
			});
			if (app.active) {
				if (app.groups.length) {
					$('#group_select').multiSelect();
					page.find("#groups_enable").attr('checked','checked');
				} else {
					page.find("#groups_enable").attr('checked', null);
				}
				page.find("#groups_enable").show();
				page.find("label[for='groups_enable']").show();
			} else {
				page.find("#groups_enable").hide();
				page.find("label[for='groups_enable']").hide();
			}
		}
	},
	enableApp:function(appid, active, element, groups) {
		groups = groups || [];
		var appitem=$('#app-navigation ul li[data-id="'+appid+'"]');
		element.val(t('settings','Please wait....'));
		if(active && !groups.length) {
			$.post(OC.filePath('settings','ajax','disableapp.php'),{appid:appid},function(result) {
				if(!result || result.status !== 'success') {
					if (result.data && result.data.message) {
						OC.Settings.Apps.showErrorMessage(result.data.message);
						appitem.data('errormsg', result.data.message);
					} else {
						OC.Settings.Apps.showErrorMessage(t('settings', 'Error while disabling app'));
						appitem.data('errormsg', t('settings', 'Error while disabling app'));
					}
					element.val(t('settings','Disable'));
					appitem.addClass('appwarning');
				}
				else {
					appitem.data('active',false);
					appitem.data('groups', '');
					element.data('active',false);
					OC.Settings.Apps.removeNavigation(appid);
					appitem.removeClass('active');
					element.val(t('settings','Enable'));
					element.parent().find("#groups_enable").hide();
					element.parent().find("label[for='groups_enable']").hide();
					var app = OC.get('appData_' + appid);
					app.active = false;
				}
			},'json');
		} else {
			$.post(OC.filePath('settings','ajax','enableapp.php'),{appid: appid, groups: groups},function(result) {
				if(!result || result.status !== 'success') {
					if (result.data && result.data.message) {
						OC.Settings.Apps.showErrorMessage(result.data.message);
						appitem.data('errormsg', result.data.message);
					} else {
						OC.Settings.Apps.showErrorMessage(t('settings', 'Error while enabling app'));
						appitem.data('errormsg', t('settings', 'Error while disabling app'));
					}
					element.val(t('settings','Enable'));
					appitem.addClass('appwarning');
				} else {
					OC.Settings.Apps.addNavigation(appid);
					appitem.data('active',true);
					element.data('active',true);
					appitem.addClass('active');
					element.val(t('settings','Disable'));
					var app = OC.get('appData_' + appid);
					app.active = true;
					if (OC.Settings.Apps.isType(app, 'filesystem') || OC.Settings.Apps.isType(app, 'prelogin') ||
						OC.Settings.Apps.isType(app, 'authentication') || OC.Settings.Apps.isType(app, 'logging')) {
						element.parent().find("#groups_enable").hide();
						element.parent().find("label[for='groups_enable']").hide();
					} else {
						element.parent().find("#groups_enable").show();
						element.parent().find("label[for='groups_enable']").show();
						if (groups) {
							appitem.data('groups', JSON.stringify(groups));
						} else {
							appitem.data('groups', '');
						}
					}
				}
			},'json')
			.fail(function() {
				OC.Settings.Apps.showErrorMessage(t('settings', 'Error while enabling app'));
				appitem.data('errormsg', t('settings', 'Error while enabling app'));
				appitem.data('active',false);
				appitem.addClass('appwarning');
				OC.Settings.Apps.removeNavigation(appid);
				element.val(t('settings','Enable'));
			});
		}
	},
	updateApp:function(appid, element) {
		element.val(t('settings','Updating....'));
		$.post(OC.filePath('settings','ajax','updateapp.php'),{appid:appid},function(result) {
			if(!result || result.status !== 'success') {
				OC.Settings.Apps.showErrorMessage(t('settings','Error while updating app'),t('settings','Error'));
				element.val(t('settings','Update'));
			}
			else {
				element.val(t('settings','Updated'));
				element.hide();
			}
		},'json');
	},

	insertApp:function(appdata) {
		var applist = $('#app-navigation ul li');
		var app =
				$('<li data-id="' + appdata.id + '" data-type="external" data-installed="0">'
				+ '<a class="app externalapp" href="' + OC.filePath('settings', 'apps', 'index.php') + '&appid=' + appdata.id+'">'
				+ appdata.name+'</a><small class="externalapp list">3rd party</small></li>');
		app.data('app', appdata);
		var added = false;
		applist.each(function() {
			if(!parseInt($(this).data('installed')) && $(this).find('a').text().toLowerCase() > appdata.name.toLowerCase()) {
				$(this).before(app);
				added = true;
				return false; // dang, remember this to get out of loop
			}
		});
		if(!added) {
			applist.last().after(app);
		}
		return app;
	},
	removeNavigation: function(appid){
		$.getJSON(OC.filePath('settings', 'ajax', 'navigationdetect.php'), {app: appid}).done(function(response){
			if(response.status === 'success'){
				var navIds=response.nav_ids;
				for(var i=0; i< navIds.length; i++){
					$('#apps .wrapper').children('li[data-id="'+navIds[i]+'"]').remove();
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
					var container = $('#apps .wrapper');

					if(container.children('li[data-id="'+entry.id+'"]').length === 0){
						var li=$('<li></li>');
						li.attr('data-id', entry.id);
						var img= $('<img class="icon"/>').attr({ src: entry.icon});
						var a=$('<a></a>').attr('href', entry.href);
						var filename=$('<span></span>');
						filename.text(entry.name);
						a.prepend(filename);
						a.prepend(img);
						li.append(a);

						// append the new app as last item in the list
						// (.push is from sticky footer)
						$('#apps .wrapper .push').before(li);

						// scroll the app navigation down
						// so the newly added app is seen
						$('#navigation').animate({
							scrollTop: $('#navigation').height()
						}, 'slow');

						// draw attention to the newly added app entry
						// by flashing it twice
						container.children('li[data-id="' + entry.id + '"]')
							.animate({opacity: 0.3})
							.animate({opacity: 1})
							.animate({opacity: 0.3})
							.animate({opacity: 1});

						if (!SVGSupport() && entry.icon.match(/\.svg$/i)) {
							$(img).addClass('svg');
							replaceSVG();
						}
					}
				}
			}
		});
	},
	showErrorMessage: function(message) {
		$('.appinfo .warning').show();
		$('.appinfo .warning').text(message);
	},
	isType: function(app, type){
		return app.types && app.types.indexOf(type) !== -1;
	}
};

$(document).ready(function(){
	$('#app-navigation ul li').each(function(index,li){
		var app = OC.get('appData_'+$(li).data('id'));
		if (app) {
			app.groups= $(li).data('groups') || [];
		}
		$(li).data('app',app);
		$(this).find('span.hidden').remove();
	});
	$('#app-navigation ul li').keydown(function(event) {
		if (event.which === 13 || event.which === 32) {
			$(event.target).click();
		}
		return false;
	});

	$(document).on('click', '#app-navigation', function(event){
		var tgt = $(event.target);
		if (tgt.is('li') || tgt.is('a')) {
			var item = tgt.is('li') ? $(tgt) : $(tgt).parent();
			var app = item.data('app');
			OC.Settings.Apps.loadApp(app);
			$('#app-navigation .selected').removeClass('selected');
			item.addClass('selected');
		}
		return false;
	});
	$('#app-content input.enable').click(function(){
		var element = $(this);
		var appid=$(this).data('appid');
		var active=$(this).data('active');
		if(appid) {
			OC.Settings.Apps.enableApp(appid, active, element);
		}
	});
	$('#app-content input.update').click(function(){
		var element = $(this);
		var appid=$(this).data('appid');
		if(appid) {
			OC.Settings.Apps.updateApp(appid, element);
		}
	});

	$('#group_select').change(function() {
		var element = $('#app-content input.enable');
		var groups = $(this).val();
		var appid = element.data('appid');
		if (appid) {
			OC.Settings.Apps.enableApp(appid, false, element, groups);
			var li = $('[data-id="'+appid+'"');
			var app = OC.get('appData_' + $(li).data('id'));
			app.groups = groups;
			li.data('groups', groups);
			li.attr('data-groups', JSON.stringify(groups));
		}
	});

	if(appid) {
		var item = $('#app-navigation ul li[data-id="'+appid+'"]');
		if(item) {
			item.trigger('click');
			item.addClass('active');
			$('#app-navigation').animate({scrollTop: $(item).offset().top-70}, 'slow','swing');
		}
	}

	$("#groups_enable").change(function() {
		if (this.checked) {
			$("div.multiselect").parent().remove();
			$('#group_select').multiSelect();
		} else {
			$('#group_select').hide().val(null);
			$("div.multiselect").parent().remove();
		}

		$('#group_select').change();
	});
});
