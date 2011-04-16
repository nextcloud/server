$(document).ready(function(){
	// Dialog for adding users
	$( "#adduser-form" ).dialog({
		autoOpen: false,
		height: 300,
		width: 350,
		modal: true,
		buttons: {
			"Create an account": function() {
				$( this ).dialog( "close" );
			},
			Cancel: function() {
				$( this ).dialog( "close" );
			}
		},
		close: function() {
			allFields.val( "" ).removeClass( "ui-state-error" );
		}
	});

	$( "#adduser-button" )
		.click(function() {
			$( "#adduser-form" ).dialog( "open" );
			return false;
		});

	// Dialog for adding users
	$( "#edituser-form" ).dialog({
		autoOpen: false,
		height: 300,
		width: 350,
		modal: true,
		buttons: {
			"Edit password": function() {
				$( this ).dialog( "close" );
			},
			Cancel: function() {
				$( this ).dialog( "close" );
			}
		},
		close: function() {
			allFields.val( "" ).removeClass( "ui-state-error" );
		}
	});

	$( ".edituser-button" )
		.click(function() {
			$( "#edituser-form" ).dialog( "open" );
			return false;
		});

	// Dialog for adding users
	$( "#removeuser-form" ).dialog({
		autoOpen: false,
		height: 300,
		width: 350,
		modal: true,
		buttons: {
			"Remove user": function() {
				$( this ).dialog( "close" );
			},
			Cancel: function() {
				$( this ).dialog( "close" );
			}
		},
		close: function() {
			allFields.val( "" ).removeClass( "ui-state-error" );
		}
	});

	$( ".removeuser-button" )
		.click(function() {
			$( "#removeuser-form" ).dialog( "open" );
			return false;
		});

	// Dialog for adding users
	$( "#removegroup-form" ).dialog({
		autoOpen: false,
		height: 300,
		width: 350,
		modal: true,
		buttons: {
			"Remove group": function() {
				$( this ).dialog( "close" );
			},
			Cancel: function() {
				$( this ).dialog( "close" );
			}
		},
		close: function() {
			allFields.val( "" ).removeClass( "ui-state-error" );
		}
	});

	$( ".removegroup-button" )
		.click(function() {
			$( "#removegroup-form" ).dialog( "open" );
			return false;
		});
} );
