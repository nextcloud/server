OC.MountConfig={
	saveStorage:function(tr) {
		var mountPoint = $(tr).find('.mountPoint input').val();
		if (mountPoint == '') {
			return false;
		}
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
		if (addMountPoint) {
			if ($('#externalStorage').data('admin') === true) {
				var isPersonal = false;
				var multiselect = $(tr).find('.chzn-select').val();
				var oldGroups = $(tr).find('.applicable').data('applicable-groups');
				var oldUsers = $(tr).find('.applicable').data('applicable-users');
				$.each(multiselect, function(index, value) {
					var pos = value.indexOf('(group)');
					if (pos != -1) {
						var mountType = 'group';
						var applicable = value.substr(0, pos);
						if ($.inArray(applicable, oldGroups) != -1) {
							oldGroups.splice($.inArray(applicable, oldGroups), 1);
						}
					} else {
						var mountType = 'user';
						var applicable = value;
						if ($.inArray(applicable, oldUsers) != -1) {
							oldUsers.splice($.inArray(applicable, oldUsers), 1);
						}
					}
					$.post(OC.filePath('files_external', 'ajax', 'addMountPoint.php'), { mountPoint: mountPoint, class: backendClass, classOptions: classOptions, mountType: mountType, applicable: applicable, isPersonal: isPersonal });
				});
				var mountType = 'group';
				$.each(oldGroups, function(index, applicable) {
					$.post(OC.filePath('files_external', 'ajax', 'removeMountPoint.php'), { mountPoint: mountPoint, mountType: mountType, applicable: applicable, isPersonal: isPersonal });
				});
				var mountType = 'user';
				$.each(oldUsers, function(index, applicable) {
					$.post(OC.filePath('files_external', 'ajax', 'removeMountPoint.php'), { mountPoint: mountPoint, mountType: mountType, applicable: applicable, isPersonal: isPersonal });
				});
			} else {
				var isPersonal = true;
				var mountType = 'user';
				var applicable = OC.currentUser;
				$.post(OC.filePath('files_external', 'ajax', 'addMountPoint.php'), { mountPoint: mountPoint, class: backendClass, classOptions: classOptions, mountType: mountType, applicable: applicable, isPersonal: isPersonal });
			}
			return true;
		}
	}
};

$(document).ready(function() {
	$('.chzn-select').chosen();

	$('#selectBackend').live('change', function() {
		var tr = $(this).parent().parent();
		$('#externalStorage tbody').append($(tr).clone());
		$('#externalStorage tbody tr').last().find('.mountPoint input').val('');
		var selected = $(this).find('option:selected').text();
		var backendClass = $(this).val();
		$(this).parent().text(selected);
		if ($(tr).find('.mountPoint input').val() == '') {
			$(tr).find('.mountPoint input').val(suggestMountPoint(selected.replace(/\s+/g, '')));
		}
		$(tr).addClass(backendClass);
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
		$('.chz-select').chosen();
		$(tr).find('td').last().attr('class', 'remove');
		$(tr).find('td').last().removeAttr('style');
		$(tr).removeAttr('id');
		$(this).remove();
	});

	function suggestMountPoint(defaultMountPoint) {
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

	$('#externalStorage td').live('change', function() {
		OC.MountConfig.saveStorage($(this).parent());
	});

	$('td.remove>img').live('click', function() {
		var tr = $(this).parent().parent();
		var mountPoint = $(tr).find('.mountPoint input').val();
		if ( ! mountPoint) {
			var row=this.parentNode.parentNode;
			$.post(OC.filePath('files_external', 'ajax', 'removeRootCertificate.php'), { cert: row.id  });
			$(tr).remove();
			return true;
		}
		if ($('#externalStorage').data('admin') === true) {
			var isPersonal = false;
			var multiselect = $(tr).find('.chzn-select').val();
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
		}
		$.post(OC.filePath('files_external', 'ajax', 'removeMountPoint.php'), { mountPoint: mountPoint, mountType: mountType, applicable: applicable, isPersonal: isPersonal });
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
