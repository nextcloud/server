var dbtypes;
$(document).ready(function() {
	dbtypes={
		sqlite:!!$('#hasSQLite').val(),
		mysql:!!$('#hasMySQL').val(),
		postgresql:!!$('#hasPostgreSQL').val(),
		oracle:!!$('#hasOracle').val(),
		mssql:!!$('#hasMSSQL').val()
	};

	$('#selectDbType').buttonset();

	if($('#hasSQLite').val()){
		$('#use_other_db').hide();
		$('#use_oracle_db').hide();
	} else {
		$('#sqliteInformation').hide();
	}
	$('#adminlogin').change(function(){
		$('#adminlogin').val($.trim($('#adminlogin').val()));
	});
	$('#sqlite').click(function() {
		$('#use_other_db').slideUp(250);
		$('#use_oracle_db').slideUp(250);
		$('#sqliteInformation').show();
	});

	$('#mysql,#pgsql,#mssql').click(function() {
		$('#use_other_db').slideDown(250);
		$('#use_oracle_db').slideUp(250);
		$('#sqliteInformation').hide();
	});

	$('#oci').click(function() {
		$('#use_other_db').slideDown(250);
		$('#use_oracle_db').show(250);
		$('#sqliteInformation').hide();
	});

	$('input[checked]').trigger('click');

	$('#showAdvanced').click(function() {
		$('#datadirContent').slideToggle(250);
		$('#databaseBackend').slideToggle(250);
		$('#databaseField').slideToggle(250);
	});
	$("form").submit(function(){
		// Save form parameters
		var post = $(this).serializeArray();

		// Disable inputs
		$(':submit', this).attr('disabled','disabled').val($(':submit', this).data('finishing'));
		$('input', this).addClass('ui-state-disabled').attr('disabled','disabled');
		$('#selectDbType').buttonset('disable');

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

	// Expand latest db settings if page was reloaded on error
	var currentDbType = $('input[type="radio"]:checked').val();

	if (currentDbType === undefined){
		$('input[type="radio"]').first().click();
	}

	if (currentDbType === 'sqlite' || (dbtypes.sqlite && currentDbType === undefined)){
		$('#datadirContent').hide(250);
		$('#databaseBackend').hide(250);
		$('#databaseField').hide(250);
	}

	$('#adminpass').strengthify({
		zxcvbn: OC.linkTo('3rdparty','zxcvbn/js/zxcvbn.js'),
		titles: [
			t('core', 'Very weak password'),
			t('core', 'Weak password'),
			t('core', 'So-so password'),
			t('core', 'Good password'),
			t('core', 'Strong password')
		]
	});
});
