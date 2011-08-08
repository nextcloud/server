$(document).ready(function(){
	// Vars we need
	var uid = "";
	var gid = "";
	var togglepassword = "";
	var togglegroup = "";

	//#########################################################################
	// Stuff I don't understand
	//#########################################################################

	function doToggleGroup( group ){
		$("#changegroupgid").val(group);

		// Serialize the data
		var post = $( "#changegroupsform" ).serialize();
		// Ajax foo
		$.post( 'ajax/togglegroups.php', post, function(data){
			if( data.status == "success" ){
				var groups = [];
				$("input[x-use='togglegroup']").each( function(index){
					if( $(this).attr("checked")){
						groups.push($(this).val());
					}
				});
				if( groups.length == 0 ){
					$("#changegroups").prev().html( '&nbsp;' );
				}
				else{
					$("#changegroups").prev().html( groups.join(", "));
				}
			}
			else{
				printError( data.data.message );
			}
		});
		return false;
	}

	function printError( message ){
		$("#errormessage").text( message );
		$("#errordialog").dialog( "open" );
		return false;
	}

	//#########################################################################
	// Functions for editing the dom after user manipulation
	//#########################################################################

	// Manipulating the page after crteating a user
	function userCreated( username, groups ){
		// We need at least a space for showing the div
		if( groups == "" ){
			groups = '&nbsp;';
		}

		// Add user to table
		var newrow = '<tr x-uid="'+username+'"><td x-use="username"><div x-use="usernamediv">'+username+'</div></td>';
		newrow = newrow+'<td x-use="usergroups"><div x-use="usergroupsdiv">'+groups+'</td>';
		newrow = newrow+'<td><input type="submit" class="removeuserbutton" value="Remove" /></td></tr>';
		$("#usertable").append( newrow  );

		// Clear forms
		$("input[x-use='createuserfield']").val( "" );
		$("input[x-use='createusercheckbox']").attr( "checked", false );
	}

	function userRemoved( username ){
		$( "tr[x-uid='"+username+"']" ).remove();
	}

	function groupCreated( groupname ){
		var newrow = '<tr x-gid="'+groupname+'"><td>' + groupname + '</td>';
		newrow = newrow + '<td><input type="submit" class="removeuserbutton" value="Remove" /></td></tr>';
		$("#grouptable").append( newrow  );

		// Delete form content
		$("input[x-use='creategroupfield']").val( "" );

		// Add group option to Create User and Edit User
		var createuser = '<input x-use="createusercheckbox" x-gid="'+groupname+'" type="checkbox" name="groups[]" value="'+groupname+'" /> <span x-gid="'+groupname+'">'+groupname+'<br /></span>';
		$("#createusergroups").append( createuser );
		var changeuser = '<input x-use="togglegroup" x-gid="'+groupname+'" type="checkbox" name="groups[]" value="'+groupname+'" /> <span x-use="togglegroup" x-gid="'+groupname+'">'+groupname+'<br /></span>';
		$("#changegroupsform").append( changeuser );
	}

	function groupRemoved( groupname ){
		// Delete the options
		$( "tr[x-gid='"+groupname+"']" ).remove();
		$( "span[x-gid='"+groupname+"']" ).remove();
		$( "input[x-gid='"+groupname+"']" ).remove();

		// remove it from user list
		$( "div[x-use='usergroupsdiv']" ).each(function(index){
			var content = $(this).text();
			var list = content.split( ", " );
			var newlist = [];
			for( var i = 0; i < list.length; i++ ){
				var temp = list[i];
				if( temp != groupname ){
					newlist.push( temp );
				}
			}
			var newstring = newlist.join( ", " );
			$(this).html( newstring )
		});

	}

	//#########################################################################
	// Editing the users properties by clicking the cell
	//#########################################################################

	// Password (clicking on user name)
	$("span[x-use='usernamediv']").live( "click", function(){
		if( togglepassword == "" || $(this).parent().parent().attr("x-uid") != togglepassword ){
			togglepassword = $(this).parent().parent().attr("x-uid");
			// Set the username!
			$("#changepassworduid").val(togglepassword);
			$("#changepasswordpwd").val("");
			$(this).parent().append( $('#changepassword') );
			$('#changepassword').show();
		}
		else{
			$('#changepassword').hide();
			togglepassword = "";
		}
	});

	$("#changepasswordbutton").click( function(){
		// Serialize the data
		var post = $( "#changepasswordform" ).serialize();
		// Ajax foo
		$.post( 'ajax/changepassword.php', post, function(data){
			if( data.status == "success" ){
				togglepassword = "";
				$('#changepassword').hide();
			}
			else{
				printError( data.data.message );
			}
		});
		return false;
	});

	// Groups
	$("div[x-use='usergroupsdiv']").live( "click", function(){
		if( togglegroup == "" || $(this).parent().parent().attr("x-uid") != togglegroup){
			togglegroup = $(this).parent().parent().attr("x-uid");
			var groups = $(this).text();
			groups = groups.split(", ");
			$("input[x-use='togglegroup']").each( function(index){
				var check = false;
				// Group checked?
				for( var i = 0; i < groups.length; i++ ){
					if( $(this).val() == groups[i] ){
						check = true;
					}
				}

				// Check/uncheck
				if( check ){
					$(this).attr("checked","checked");
				}
				else{
					$(this).removeAttr("checked");
				}
			});
			$("#changegroupuid").val(togglegroup);
			$(this).empty();
			$(this).parent().append( $('#changegroups') );
			$('#changegroups').show();
		}
		else{
			var groups = [];
			$("input[x-use='togglegroup']").each( function(index){
				if( $(this).attr("checked")){
					groups.push($(this).val());
				}
			});
			if( groups.length == 0 ){
				$("#changegroups").prev().html( '&nbsp;' );
			}
			else{
				$("#changegroups").prev().html( groups.join(", "));
			}
			$('#changegroups').hide();
			togglegroup = "";
		}
	});

	$("span[x-use='togglegroup']").live( "click", function(){
		if( $(this).prev().attr("checked")){
			$(this).prev().removeAttr("checked")
		}
		else{
			$(this).prev().attr("checked","checked")
		}
		doToggleGroup( $(this).attr("x-gid"));
	});

	$("input[x-use='togglegroup']").live( "click", function(){
		doToggleGroup( $(this).attr("x-gid"));
	});
	//#########################################################################
	// Clicking on buttons
	//#########################################################################


	// Create a new user
	$( "#createuserbutton" )
		.click(function(){
			if(!$( "#createuserbutton" ).data('active')){
				$( "#createuserbutton" ).data('active',true);
				
				// Create the post data
				var post = $( "#createuserdata" ).serialize();
				
				// Ajax call
				$.post( 'ajax/createuser.php', post, function(data){
					$( "#createuserbutton" ).data('active',false);
					
					// If it says "success" then we are happy
					if( data.status == "success" ){
						userCreated( data.data.username, data.data.groups );
					}
					else{
						printError( data.data.message );
					}
				});
			}
			return false;
		});

	$( ".removeuserbutton" ).live( 'click', function() {
			uid = $( this ).parent().parent().attr( 'x-uid' );
			$("#deleteuserusername").html(uid);
			$("#deleteusernamefield").val(uid);
			$("#removeuserform").dialog( "open" );
			return false;
		});

	$( "#creategroupbutton" )
		.click(function(){
			// Serialize the data
			var post = $( "#creategroupdata" ).serialize();
			// Ajax foo
			$.post( 'ajax/creategroup.php', post, function(data){
				if( data.status == "success" ){
					groupCreated( data.data.groupname );
				}
				else{
					printError( data.data.message );
				}
			});
			return false;
		});

	$( ".removegroupbutton" ).live( 'click', function(){
			gid = $( this ).parent().parent().attr( 'x-gid' );
			$("#removegroupgroupname").html(gid);
			$("#removegroupnamefield").val(gid);
			$("#removegroupform").dialog( "open" );
			return false;
		});

	//#########################################################################
	// Dialogs
	//#########################################################################

	// Removing users
	$( "#errordialog" ).dialog({
		autoOpen: false,
		modal: true,
		buttons: {
			OK: function() {
				$( this ).dialog( "close" );
			}
		}
	});

	// Removing users
	$( "#removeuserform" ).dialog({
		autoOpen: false,
		height: 300,
		width: 350,
		modal: true,
		buttons: {
			"Remove user": function() {
				var post = $( "#removeuserdata" ).serialize();
				$.post( 'ajax/removeuser.php', post, function(data){
					if( data.status == "success" ){
						userRemoved( uid );
					}
					else{
						printError( data.data.message );
					}
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


	// Dialog for adding users
	$( "#removegroupform" ).dialog({
		autoOpen: false,
		height: 300,
		width: 350,
		modal: true,
		buttons: {
			"Remove group": function(){
				var post = $( "#removegroupdata" ).serialize();
				$.post( 'ajax/removegroup.php', post, function(data){
					if( data.status == "success" ){
						groupRemoved( gid );
					}
					else{
						printError( data.data.message );
					}
				});
				$( this ).dialog( "close" );
			},
			Cancel: function() {
				$( this ).dialog( "close" );
			}
		},
		close: function(){
			true;
		}
	});

} );

