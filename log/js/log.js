$(document).ready(function() {
	// Sets the select_all checkbox behaviour :
	$('#all').click(function() {
		if($(this).attr('checked')){
			// Check all
			$('input.action:checkbox').attr('checked', true);
		}else{
			// Uncheck all
			$('input.action:checkbox').attr('checked', false);
		}
	});
	$('input.action:checkbox').click(function() {
		if(!$(this).attr('checked')){
			$('#all').attr('checked',false);
		}else{
			if($('input.action:checkbox:checked').length==$('input.action:checkbox').length){
				$('#all').attr('checked',true);
			}
		}
	});
});