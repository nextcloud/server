$(document).ready(function(){

	function applicableChange(applicable) {
		if (applicable == 'Global') {
			
		}
		console.log(applicable);
	}

	$('#selectStorage').live('change', function() {
		var tr = $(this).parent().parent();
		$('#externalStorage tbody').last().append($(tr).clone());
		var selected = $(this).val();
		$(this).parent().text(selected);
		var backends = $(this).data('configurations').split(';');
		var configuration = [];
		// Find selected backend configuration parameters
		$.each(backends, function(index, backend) {
			if (backend.split(':')[0] == selected) {
				configuration = backend.split(':')[1].split(',');
				// 				break;
			}
		});
		var td = $(tr).find('td.configuration');
		$.each(configuration, function(index, config) {
			if (config.indexOf('*') != -1) {
				td.append('<input type="password" placeholder="'+config.substring(1)+'" />');
			} else {
				td.append('<input type="text" placeholder="'+config+'" />');
			}
		});
		$(tr).find('td').last().attr('class', 'remove');
		$(tr).removeAttr('id');
		$(this).remove();
	});

	$('td.remove>img').live('click', function() {
		$(this).parent().parent().remove();
		// TODO remove storage
	});

	$('#externalStorage select[multiple]').each(function(index,element){
		applyMultiplySelect($(element));
	});

	function applyMultiplySelect(element) {
		var checkHandeler=false;
		element.multiSelect({
			oncheck:applicableChange,
			onuncheck:applicableChange,
			minWidth: 120,
		});
	}

	$('#allowUserMounting').bind('change', function() {
		// TODO save setting
	});

});