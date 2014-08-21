(function(){

function updateStatus(statusEl, result){
	statusEl.removeClass('success error loading-small');
	if (result && result.status == 'success' && result.data.message) {
		statusEl.addClass('success');
		return true;
	} else {
		statusEl.addClass('error');
		return false;
	}
}

function getSelection($row) {
	var values = $row.find('.applicableUsers').select2('val');
	if (!values || values.length === 0) {
		values = ['all'];
	}
	return values;
}

OC.MountConfig={
	saveStorage:function(tr, callback) {
		var mountPoint = $(tr).find('.mountPoint input').val();
		if (mountPoint == '') {
			return false;
		}
		var statusSpan = $(tr).closest('tr').find('.status span');
		var backendClass = $(tr).find('.backend').data('class');
		var configuration = $(tr).find('.configuration input');
		var addMountPoint = true;
		if (configuration.length < 1) {
			return false;
		}
		var classOptions = {};
		$.each(configuration, function(index, input) {
			if ($(input).val() == '' && !$(input).hasClass('optional')) {
				addMountPoint = false;
				return false;
			}
			if ($(input).is(':checkbox')) {
				if ($(input).is(':checked')) {
					classOptions[$(input).data('parameter')] = true;
				} else {
					classOptions[$(input).data('parameter')] = false;
				}
			} else {
				classOptions[$(input).data('parameter')] = $(input).val();
			}
		});
		if ($('#externalStorage').data('admin') === true) {
			var multiselect = getSelection($(tr));
		}
		if (addMountPoint) {
			var status = false;
			if ($('#externalStorage').data('admin') === true) {
				var isPersonal = false;
				var oldGroups = $(tr).find('.applicable').data('applicable-groups');
				var oldUsers = $(tr).find('.applicable').data('applicable-users');
				var groups = [];
				var users = [];
				$.each(multiselect, function(index, value) {
					var pos = value.indexOf('(group)');
					if (pos != -1) {
						var mountType = 'group';
						var applicable = value.substr(0, pos);
						if ($.inArray(applicable, oldGroups) != -1) {
							oldGroups.splice($.inArray(applicable, oldGroups), 1);
						}
						groups.push(applicable);
					} else {
						var mountType = 'user';
						var applicable = value;
						if ($.inArray(applicable, oldUsers) != -1) {
							oldUsers.splice($.inArray(applicable, oldUsers), 1);
						}
						users.push(applicable);
					}
					statusSpan.addClass('loading-small').removeClass('error success');
					$.ajax({type: 'POST',
						url: OC.filePath('files_external', 'ajax', 'addMountPoint.php'),
						data: {
							mountPoint: mountPoint,
							'class': backendClass,
							classOptions: classOptions,
							mountType: mountType,
							applicable: applicable,
							isPersonal: isPersonal
						},
						success: function(result) {
							status = updateStatus(statusSpan, result);
							if (callback) {
								callback(status);
							}
						},
						error: function(result){
							status = updateStatus(statusSpan, result);
							if (callback) {
								callback(status);
							}
						}
					});
				});
				$(tr).find('.applicable').data('applicable-groups', groups);
				$(tr).find('.applicable').data('applicable-users', users);
				var mountType = 'group';
				$.each(oldGroups, function(index, applicable) {
					$.ajax({type: 'POST',
						url: OC.filePath('files_external', 'ajax', 'removeMountPoint.php'),
						data: {
							mountPoint: mountPoint,
							'class': backendClass,
							classOptions: classOptions,
							mountType: mountType,
							applicable: applicable,
							isPersonal: isPersonal
						}
					});
				});
				var mountType = 'user';
				$.each(oldUsers, function(index, applicable) {
					$.ajax({type: 'POST',
						url: OC.filePath('files_external', 'ajax', 'removeMountPoint.php'),
						data: {
							mountPoint: mountPoint,
							'class': backendClass,
							classOptions: classOptions,
							mountType: mountType,
							applicable: applicable,
							isPersonal: isPersonal
						}
					});
				});
			} else {
				var isPersonal = true;
				var mountType = 'user';
				var applicable = OC.currentUser;
				statusSpan.addClass('loading-small').removeClass('error success');
				$.ajax({type: 'POST',
					url: OC.filePath('files_external', 'ajax', 'addMountPoint.php'),
					data: {
						mountPoint: mountPoint,
						'class': backendClass,
						classOptions: classOptions,
						mountType: mountType,
						applicable: applicable,
						isPersonal: isPersonal
					},
					success: function(result) {
						status = updateStatus(statusSpan, result);
						if (callback) {
							callback(status);
						}
					},
					error: function(result){
						status = updateStatus(statusSpan, result);
						if (callback) {
							callback(status);
						}
					}
				});
			}
			return status;
		}
	}
};

$(document).ready(function() {
	//initialize hidden input field with list of users and groups
	$('#externalStorage').find('tr:not(#addMountPoint)').each(function(i,tr) {
		var applicable = $(tr).find('.applicable');
		if (applicable.length > 0) {
			var groups = applicable.data('applicable-groups');
			var groupsId = [];
			$.each(groups, function () {
				groupsId.push(this+"(group)");
			});
			var users = applicable.data('applicable-users');
			if (users.indexOf('all') > -1) {
				$(tr).find('.applicableUsers').val('');
			} else {
				$(tr).find('.applicableUsers').val(groupsId.concat(users).join(','));
			}
		}
	});

	var userListLimit = 30;
	function addSelect2 ($elements) {
		if ($elements.length > 0) {
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
					results: function (data, page) {
						if (data.status === "success") {

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

					var promises = [];

					var results = [];

					$(element.val().split(",")).each(function (i,userId) {
						var def = new $.Deferred();
						promises.push(def.promise());

						var pos = userId.indexOf('(group)');
						if (pos !== -1) {
							//add as group
							results.push({name:userId, displayname:userId.substr(0, pos), type:'group'});
							def.resolve();
						} else {
							$.ajax(OC.generateUrl('apps/files_external/applicable'), {
								data: {
									pattern: userId
								},
								dataType: "json"
							}).done(function(data) {
								if (data.status === "success") {
									if (data.users[userId]) {
										results.push({name:userId, displayname:data.users[userId], type:'user'});
									}
									def.resolve();
								} else {
									//FIXME add error handling
								}
							});
						}
					});
					$.when.apply(undefined, promises).then(function(){
						callback(results);
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
			}).on("select2-loaded", function() {
				$.each($(".avatardiv"), function(i, div) {
					$div = $(div);
					if ($div.data('type') === 'user') {
						$div.avatar($div.data('name'),32);
					}
				})
			});
		}
	}
	addSelect2($('tr:not(#addMountPoint) .applicableUsers'));

	$('#externalStorage').on('change', '#selectBackend', function() {
		var tr = $(this).parent().parent();
		$('#externalStorage tbody').append($(tr).clone());
		$('#externalStorage tbody tr').last().find('.mountPoint input').val('');
		var selected = $(this).find('option:selected').text();
		var backendClass = $(this).val();
		$(this).parent().text(selected);
		if ($(tr).find('.mountPoint input').val() == '') {
			$(tr).find('.mountPoint input').val(suggestMountPoint(selected));
		}
		$(tr).addClass(backendClass);
		$(tr).find('.status').append('<span></span>');
		$(tr).find('.backend').data('class', backendClass);
		var configurations = $(this).data('configurations');
		var td = $(tr).find('td.configuration');
		$.each(configurations, function(backend, parameters) {
			if (backend == backendClass) {
				$.each(parameters['configuration'], function(parameter, placeholder) {
					if (placeholder.indexOf('*') != -1) {
						td.append('<input type="password" data-parameter="'+parameter+'" placeholder="'+placeholder.substring(1)+'" />');
					} else if (placeholder.indexOf('!') != -1) {
						td.append('<label><input type="checkbox" data-parameter="'+parameter+'" />'+placeholder.substring(1)+'</label>');
					} else if (placeholder.indexOf('&') != -1) {
						td.append('<input type="text" class="optional" data-parameter="'+parameter+'" placeholder="'+placeholder.substring(1)+'" />');
					} else if (placeholder.indexOf('#') != -1) {
						td.append('<input type="hidden" data-parameter="'+parameter+'" />');
					} else {
						td.append('<input type="text" data-parameter="'+parameter+'" placeholder="'+placeholder+'" />');
					}
				});
				if (parameters['custom'] && $('#externalStorage tbody tr.'+backendClass.replace(/\\/g, '\\\\')).length == 1) {
					OC.addScript('files_external', parameters['custom']);
				}
				return false;
			}
		});
		$(tr).find('td').last().attr('class', 'remove');
		$(tr).find('td').last().removeAttr('style');
		$(tr).removeAttr('id');
		$(this).remove();
		addSelect2($('tr:not(#addMountPoint) .applicableUsers'));
	});

	function suggestMountPoint(defaultMountPoint) {
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
			$('#externalStorage tbody td.mountPoint input').each(function(index, mountPoint) {
				if ($(mountPoint).val() == defaultMountPoint+append) {
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
		return defaultMountPoint+append;
	}

	$('#externalStorage').on('paste', 'td', function() {
		var tr = $(this).parent();
		setTimeout(function() {
			OC.MountConfig.saveStorage(tr);
		}, 20);
	});

	var timer;

	$('#externalStorage').on('keyup', 'td input', function() {
		clearTimeout(timer);
		var tr = $(this).parent().parent();
		if ($(this).val) {
			timer = setTimeout(function() {
				OC.MountConfig.saveStorage(tr);
			}, 2000);
		}
	});

	$('#externalStorage').on('change', 'td input:checkbox', function() {
		OC.MountConfig.saveStorage($(this).parent().parent().parent());
	});

	$('#externalStorage').on('change', '.applicable', function() {
		OC.MountConfig.saveStorage($(this).parent());
	});

	$('#sslCertificate').on('click', 'td.remove>img', function() {
		var $tr = $(this).parent().parent();
		var row = this.parentNode.parentNode;
		$.post(OC.filePath('files_external', 'ajax', 'removeRootCertificate.php'), {cert: row.id});
		$tr.remove();
		return true;
	});

	$('#externalStorage').on('click', 'td.remove>img', function() {
		var tr = $(this).parent().parent();
		var mountPoint = $(tr).find('.mountPoint input').val();

		if ($('#externalStorage').data('admin') === true) {
			var isPersonal = false;
			var multiselect = getSelection($(tr));
			$.each(multiselect, function(index, value) {
				var pos = value.indexOf('(group)');
				if (pos != -1) {
					var mountType = 'group';
					var applicable = value.substr(0, pos);
				} else {
					var mountType = 'user';
					var applicable = value;
				}
				$.post(OC.filePath('files_external', 'ajax', 'removeMountPoint.php'), { mountPoint: mountPoint, mountType: mountType, applicable: applicable, isPersonal: isPersonal });
			});
		} else {
			var mountType = 'user';
			var applicable = OC.currentUser;
			var isPersonal = true;
			$.post(OC.filePath('files_external', 'ajax', 'removeMountPoint.php'), { mountPoint: mountPoint, mountType: mountType, applicable: applicable, isPersonal: isPersonal });
		}
		$(tr).remove();
	});

	$('#allowUserMounting').bind('change', function() {
		if (this.checked) {
			OC.AppConfig.setValue('files_external', 'allow_user_mounting', 'yes');
		} else {
			OC.AppConfig.setValue('files_external', 'allow_user_mounting', 'no');
		}
	});

});

})();
