(function(){

function updateStatus(statusEl, result){
	statusEl.removeClass('success error loading-small');
	if (result && result.status === 'success' && result.data.message) {
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

OC.MountConfig={
	saveStorage:function($tr, callback) {
		var mountPoint = $tr.find('.mountPoint input').val();
		var oldMountPoint = $tr.find('.mountPoint input').data('mountpoint');
		if (mountPoint === '') {
			return false;
		}
		var statusSpan = $tr.find('.status span');
		var backendClass = $tr.find('.backend').data('class');
		var configuration = $tr.find('.configuration input');
		var addMountPoint = true;
		if (configuration.length < 1) {
			return false;
		}
		var classOptions = {};
		$.each(configuration, function(index, input) {
			if ($(input).val() === '' && !$(input).hasClass('optional')) {
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
			var multiselect = getSelection($tr);
		}
		if (addMountPoint) {
			var status = false;
			if ($('#externalStorage').data('admin') === true) {
				var isPersonal = false;
				var oldGroups = $tr.find('.applicable').data('applicable-groups');
				var oldUsers = $tr.find('.applicable').data('applicable-users');
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
							isPersonal: isPersonal,
							oldMountPoint: oldMountPoint
						},
						success: function(result) {
							$tr.find('.mountPoint input').data('mountpoint', mountPoint);
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
				$tr.find('.applicable').data('applicable-groups', groups);
				$tr.find('.applicable').data('applicable-users', users);
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
						isPersonal: isPersonal,
						oldMountPoint: oldMountPoint
					},
					success: function(result) {
						$tr.find('.mountPoint input').data('mountpoint', mountPoint);
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
	var $externalStorage = $('#externalStorage');

	//initialize hidden input field with list of users and groups
	$externalStorage.find('tr:not(#addMountPoint)').each(function(i,tr) {
		var $tr = $(tr);
		var $applicable = $tr.find('.applicable');
		if ($applicable.length > 0) {
			var groups = $applicable.data('applicable-groups');
			var groupsId = [];
			$.each(groups, function () {
				groupsId.push(this + '(group)');
			});
			var users = $applicable.data('applicable-users');
			if (users.indexOf('all') > -1) {
				$tr.find('.applicableUsers').val('');
			} else {
				$tr.find('.applicableUsers').val(groupsId.concat(users).join(','));
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
				})
			});
		}
	}
	addSelect2($('tr:not(#addMountPoint) .applicableUsers'));
	
	$externalStorage.on('change', '#selectBackend', function() {
		var $tr = $(this).closest('tr');
		$externalStorage.find('tbody').append($tr.clone());
		$externalStorage.find('tbody tr').last().find('.mountPoint input').val('');
		var selected = $(this).find('option:selected').text();
		var backendClass = $(this).val();
		$tr.find('.backend').text(selected);
		if ($tr.find('.mountPoint input').val() === '') {
			$tr.find('.mountPoint input').val(suggestMountPoint(selected));
		}
		$tr.addClass(backendClass);
		$tr.find('.status').append('<span></span>');
		$tr.find('.backend').data('class', backendClass);
		var configurations = $(this).data('configurations');
		var $td = $tr.find('td.configuration');
		$.each(configurations, function(backend, parameters) {
			if (backend === backendClass) {
				$.each(parameters['configuration'], function(parameter, placeholder) {
					var is_optional = false;
					if (placeholder.indexOf('&') === 0) {
						is_optional = true;
						placeholder = placeholder.substring(1);
					}
					var newElement;
					if (placeholder.indexOf('*') === 0) {
						var class_string = is_optional ? ' optional' : '';
						newElement = $('<input type="password" class="added' + class_string + '" data-parameter="'+parameter+'" placeholder="'+placeholder.substring(1)+'" />');
					} else if (placeholder.indexOf('!') === 0) {
						newElement = $('<label><input type="checkbox" class="added" data-parameter="'+parameter+'" />'+placeholder.substring(1)+'</label>');
					} else if (placeholder.indexOf('#') === 0) {
						newElement = $('<input type="hidden" class="added" data-parameter="'+parameter+'" />');
					} else {
						var class_string = is_optional ? ' optional' : '';
						newElement = $('<input type="text" class="added' + class_string + '" data-parameter="'+parameter+'" placeholder="'+placeholder+'" />');
					}
					highlightInput(newElement);
					$td.append(newElement);
				});
				if (parameters['custom'] && $externalStorage.find('tbody tr.'+backendClass.replace(/\\/g, '\\\\')).length === 1) {
					OC.addScript('files_external', parameters['custom']);
				}
				$td.children().not('[type=hidden]').first().focus();
				return false;
			}
		});
		$tr.find('td').last().attr('class', 'remove');
		$tr.find('td').last().removeAttr('style');
		$tr.removeAttr('id');
		$(this).remove();
		addSelect2($tr.find('.applicableUsers'));
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
			$externalStorage.find('tbody td.mountPoint input').each(function(index, mountPoint) {
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
		return defaultMountPoint+append;
	}

	$externalStorage.on('paste', 'td input', function() {
		var $me = $(this);
		var $tr = $me.closest('tr');
		setTimeout(function() {
			highlightInput($me);
			OC.MountConfig.saveStorage($tr);
		}, 20);
	});

	var timer;

	$externalStorage.on('keyup', 'td input', function() {
		clearTimeout(timer);
		var $tr = $(this).closest('tr');
		highlightInput($(this));
		if ($(this).val) {
			timer = setTimeout(function() {
				OC.MountConfig.saveStorage($tr);
			}, 2000);
		}
	});

	$externalStorage.on('change', 'td input:checkbox', function() {
		OC.MountConfig.saveStorage($(this).closest('tr'));
	});

	$externalStorage.on('change', '.applicable', function() {
		OC.MountConfig.saveStorage($(this).closest('tr'));
	});

	$externalStorage.on('click', '.status>span', function() {
		OC.MountConfig.saveStorage($(this).closest('tr'));
	});

	$('#sslCertificate').on('click', 'td.remove>img', function() {
		var $tr = $(this).closest('tr');
		$.post(OC.filePath('files_external', 'ajax', 'removeRootCertificate.php'), {cert: $tr.attr('id')});
		$tr.remove();
		return true;
	});

	$externalStorage.on('click', 'td.remove>img', function() {
		var $tr = $(this).closest('tr');
		var mountPoint = $tr.find('.mountPoint input').val();

		if ($externalStorage.data('admin') === true) {
			var isPersonal = false;
			var multiselect = getSelection($tr);
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
		$tr.remove();
	});

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
});

})();
