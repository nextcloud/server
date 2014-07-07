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

OC.MountConfig={
	saveStorage:function(tr, callback) {
		var mountPoint = $(tr).find('.mountPoint input').val();
		var oldMountPoint = $(tr).find('.mountPoint input').data('mountpoint');
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
			var multiselect = $(tr).find('.chzn-select').val();
			if (multiselect == null) {
				return false;
			}
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
							isPersonal: isPersonal,
							oldMountPoint: oldMountPoint
						},
						success: function(result) {
							$(tr).find('.mountPoint input').data('mountpoint', mountPoint);
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
						isPersonal: isPersonal,
						oldMountPoint: oldMountPoint
					},
					success: function(result) {
						$(tr).find('.mountPoint input').data('mountpoint', mountPoint);
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
	$('.chzn-select').chosen();

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
					var is_optional = false;
					if (placeholder.indexOf('&') === 0) {
						is_optional = true;
						placeholder = placeholder.substring(1);
					}
					if (placeholder.indexOf('*') === 0) {
						var class_string = is_optional ? ' class="optional"' : '';
						td.append('<input type="password"' + class_string + ' data-parameter="'+parameter+'" placeholder="'+placeholder.substring(1)+'" />');
					} else if (placeholder.indexOf('!') === 0) {
						td.append('<label><input type="checkbox" data-parameter="'+parameter+'" />'+placeholder.substring(1)+'</label>');
					} else if (placeholder.indexOf('#') === 0) {
						td.append('<input type="hidden" data-parameter="'+parameter+'" />');
					} else {
						var class_string = is_optional ? ' class="optional"' : '';
						td.append('<input type="text"' + class_string + ' data-parameter="'+parameter+'" placeholder="'+placeholder+'" />');
					}
				});
				if (parameters['custom'] && $('#externalStorage tbody tr.'+backendClass.replace(/\\/g, '\\\\')).length == 1) {
					OC.addScript('files_external', parameters['custom']);
				}
				return false;
			}
		});
		// Reset chosen
		var chosen = $(tr).find('.applicable select');
		chosen.parent().find('div').remove();
		chosen.removeAttr('id').removeClass('chzn-done').css({display:'inline-block'});
		chosen.chosen();
		$(tr).find('td').last().attr('class', 'remove');
		$(tr).find('td').last().removeAttr('style');
		$(tr).removeAttr('id');
		$(this).remove();
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

	$('#externalStorage').on('change', '.applicable .chzn-select', function() {
		OC.MountConfig.saveStorage($(this).parent().parent());
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
			var multiselect = $(tr).find('.chzn-select').val();
			if (multiselect != null) {
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
			}
		} else {
			var mountType = 'user';
			var applicable = OC.currentUser;
			var isPersonal = true;
			$.post(OC.filePath('files_external', 'ajax', 'removeMountPoint.php'), { mountPoint: mountPoint, mountType: mountType, applicable: applicable, isPersonal: isPersonal });
		}
		$(tr).remove();
	});

	$('#allowUserMounting').bind('change', function() {
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
		OC.msg.finishedSaving('#userMountingMsg', {status: 'success', data: {message: t('settings', 'Saved')}});
	});

	$('input[name="allowUserMountingBackends\\[\\]"]').bind('change', function() {
		OC.msg.startSaving('#userMountingMsg');
		var userMountingBackends = $('input[name="allowUserMountingBackends\\[\\]"]:checked').map(function(){return $(this).val();}).get();
		OC.AppConfig.setValue('files_external', 'user_mounting_backends', userMountingBackends.join());
		OC.msg.finishedSaving('#userMountingMsg', {status: 'success', data: {message: t('settings', 'Saved')}});

		// disable allowUserMounting
		if(userMountingBackends.length === 0) {
			$('#allowUserMounting').prop('checked', false);
			$('#allowUserMounting').trigger('change');

		}
	});
});

})();
