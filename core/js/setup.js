var dbtypes
$(document).ready(function() {
	dbtypes={
		sqlite:!!$('#hasSQLite').val(),
		mysql:!!$('#hasMySQL').val(),
		postgresql:!!$('#hasPostgreSQL').val(),
	}
	
	$('#selectDbType').buttonset();
	$('#datadirContent').hide(250);
	$('#databaseField').hide(250);
	if($('#hasSQLite').val()=='true'){
		$('#use_other_db').hide();
		$('#dbhost').hide();
		$('#dbhostlabel').hide();
	}
	$('#adminlogin').change(function(){
		$('#adminlogin').val($.trim($('#adminlogin').val()));
	});
	$('#sqlite').click(function() {
		$('#use_other_db').slideUp(250);
		$('#dbhost').hide(250);
		$('#dbhostlabel').hide(250);
	});

	$('#mysql').click(function() {
		$('#use_other_db').slideDown(250);
		$('#dbhost').show(250);
		$('#dbhostlabel').show(250);
	});
	
	$('#pgsql').click(function() {
		$('#use_other_db').slideDown(250);
		$('#dbhost').show(250);
		$('#dbhostlabel').show(250);
	});

	$('input[checked]').trigger('click');

	$('#showAdvanced').click(function() {
		$('#datadirContent').slideToggle(250);
		$('#databaseField').slideToggle(250);
	});
	$("form").submit(function(){
		// Save form parameters
		var post = $(this).serializeArray();

		// Disable inputs
		$(':submit', this).attr('disabled','disabled').val('Finishing …');
		$('input', this).addClass('ui-state-disabled').attr('disabled','disabled');
		$('#selectDbType').button('disable');
		$('label.ui-button', this).addClass('ui-state-disabled').attr('aria-disabled', 'true').button('disable');

		// Create the form
		var form = $('<form>');
		form.attr('action', $(this).attr('action'));
		form.attr('method', 'POST');

		for(var i=0; i<post.length; i++){
			var input = $('<input type="hidden">');
			input.attr(post[i]);
			form.append(input);
		}

		// Submit the form
		form.appendTo(document.body);
		form.submit();
		return false;
	});

	if(!dbtypes.sqlite){
		$('#showAdvanced').click();
		$('input[type="radio"]').first().click();
	}
});
