$(document).ready(function(){
	// Vars we need
	var uid = "";
	var gid = "";
	// Dialog for adding users
	$( "#adduser-form" ).dialog({
		autoOpen: false,
		height: 300,
		width: 350,
		modal: true,
		buttons: {
			"Create an account": function() {
				var post = $( "#createuserdata" ).serialize();
				$.post( 'ajax/createuser.php', post, function(data){
					var newrow = '<tr><td>' + data.data.username + '</td>';
					newrow = newrow + '<td>' + data.data.groups + '</td>';
					newrow = newrow + '<td><a href="" class="edituser-button">edit</a> | <a  class="removeuser-button" href="">remove</a></td></tr>';
					$("#userstable").append( newrow  );
				});
				$( this ).dialog( "close" );
			},
			Cancel: function() {
				$( this ).dialog( "close" );
			}
		},
		close: function() {
			true;
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
			true;
		}
	});

	$( ".edituser-button" )
		.click(function(){
			uid = $( this ).parent().attr( 'x-uid' );
			$("#edituserusername").html(uid);
			$("#edituser-form").dialog("open");
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
			uid = $( this ).parent().attr( 'x-uid' );
			$("#deleteuserusername").html(uid);
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
			"Remove group": function(){
				var post = $( "#deletegroupdata" ).serialize();
				$.post( 'ajax/deletegroup.php', post, function(data){
					$( "a[x-gid='"+gid+"']" ).parent().remove();
				});
				$( this ).dialog( "close" );
			},
			Cancel: function() {
				$( this ).dialog( "close" );
			}
		},
		close: function(){
			allFields.val( "" ).removeClass( "ui-state-error" );
		}
	});

	$( ".removegroup-button" )
		.click(function(){
			gid = $( this ).parent().attr( 'x-gid' );
			$("#deletegroupgroupname").html(gid);
			$("#deletegroupnamefield").val(gid);
			$("#removegroup-form").dialog( "open" );
			return false;
		});
} );
