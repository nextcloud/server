/**
 * Copyright (c) 2011, Robin Appelman <icewind1991@gmail.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING-README file.
 */

/**
 * Post the email address change to the server.
 */
function changeEmailAddress(){
    var emailInfo = $('#email');
    if (emailInfo.val() === emailInfo.defaultValue){
        return;
    }
    emailInfo.defaultValue = emailInfo.val();
    OC.msg.startSaving('#lostpassword .msg');
    var post = $( "#lostpassword" ).serialize();
    $.post( 'ajax/lostpassword.php', post, function(data){
        OC.msg.finishedSaving('#lostpassword .msg', data);
    });
}

/**
 * Post the display name change to the server.
 */
function changeDisplayName(){
    if ($('#displayName').val() !== '' ) {
        OC.msg.startSaving('#displaynameform .msg');
        // Serialize the data
        var post = $( "#displaynameform" ).serialize();
        // Ajax foo
        $.post( 'ajax/changedisplayname.php', post, function(data){
            if( data.status === "success" ){
                $('#oldDisplayName').text($('#displayName').val());
                // update displayName on the top right expand button
                $('#expandDisplayName').text($('#displayName').val());
            }
            else{
                $('#newdisplayname').val(data.data.displayName);
            }
            OC.msg.finishedSaving('#displaynameform .msg', data);
        });
        return false;
    }
}

function selectAvatar (path) {
	$.post(OC.filePath('settings', 'ajax', 'newavatar.php'), {path: path}, function(data) {
		if (data.status === "success") {
			updateAvatar();
		} else {
			OC.dialogs.alert(data.data.message, t('core', "Error"));
		}
	});
}

function updateAvatar () {
	$('#avatar img').attr('src', OC.filePath('', '', 'avatar.php?user='+OC.currentUser+'&size=128'));
}

$(document).ready(function(){
	$("#passwordbutton").click( function(){
		if ($('#pass1').val() !== '' && $('#pass2').val() !== '') {
			// Serialize the data
			var post = $( "#passwordform" ).serialize();
			$('#passwordchanged').hide();
			$('#passworderror').hide();
			// Ajax foo
			$.post( 'ajax/changepassword.php', post, function(data){
				if( data.status === "success" ){
					$('#pass1').val('');
					$('#pass2').val('');
					$('#passwordchanged').show();
				}
				else{
					$('#passworderror').html( data.data.message );
					$('#passworderror').show();
				}
			});
			return false;
		} else {
			$('#passwordchanged').hide();
			$('#passworderror').show();
			return false;
		}

	});

    $('#displayName').keyup(function(){
        if ($('#displayName').val() !== '' ){
            if(typeof timeout !== 'undefined'){
                clearTimeout(timeout);
            }
            timeout = setTimeout('changeDisplayName()',1000);
        }
    });


    $('#email').keyup(function(){
        if ($('#email').val() !== '' ){
            if(typeof timeout !== 'undefined'){
                clearTimeout(timeout);
            }
            timeout = setTimeout('changeEmailAddress()',1000);
        }
    });

	$("#languageinput").chosen();
	// Show only the not selectable optgroup
	// Choosen only shows optgroup-labels if there are options in the optgroup
	$(".languagedivider").remove();

	$("#languageinput").change( function(){
		// Serialize the data
		var post = $( "#languageinput" ).serialize();
		// Ajax foo
		$.post( 'ajax/setlanguage.php', post, function(data){
			if( data.status === "success" ){
				location.reload();
			}
			else{
				$('#passworderror').html( data.data.message );
			}
		});
		return false;
	});

	$('button:button[name="submitDecryptAll"]').click(function() {
		var privateKeyPassword = $('#decryptAll input:password[id="privateKeyPassword"]').val();
		OC.Encryption.decryptAll(privateKeyPassword);
	});
	
	$('#decryptAll input:password[name="privateKeyPassword"]').keyup(function(event) {
		var privateKeyPassword = $('#decryptAll input:password[id="privateKeyPassword"]').val();
		if (privateKeyPassword !== '' ) {
			$('#decryptAll button:button[name="submitDecryptAll"]').removeAttr("disabled");
			if(event.which === 13) {
				OC.Encryption.decryptAll(privateKeyPassword);
			}
		} else {
			$('#decryptAll button:button[name="submitDecryptAll"]').attr("disabled", "true");
		}
	});
	
	$('#uploadavatar').click(function(){
		alert('To be done');
		updateAvatar();
	});

	var uploadparms = {
		done: function(e, data) {
			if (data.result.status === "success") {
				updateAvatar();
			} else {
				OC.dialogs.alert(data.result.data.message, t('core', "Error"));
			}
		}
	};

	$('#uploadavatarbutton').click(function(){
		$('#uploadavatar').click();
	});

	$('#uploadavatar').fileupload(uploadparms);

	$('#selectavatar').click(function(){
		OC.dialogs.filepicker(t('settings', "Select an avatar"), selectAvatar, false, "image");
	});

	$('#removeavatar').click(function(){
		$.post(OC.filePath('settings', 'ajax', 'newavatar.php'), {path: false});
		updateAvatar();
	});
} );

OC.Encryption = {
	decryptAll: function(password) {
		OC.Encryption.msg.startDecrypting('#decryptAll .msg');
		$.post('ajax/decryptall.php', {password:password}, function(data) {
			if (data.status === "error") {
				OC.Encryption.msg.finishedDecrypting('#decryptAll .msg', data);
			} else {
				OC.Encryption.msg.finishedDecrypting('#decryptAll .msg', data);
			}
		}
		);
	}
}

OC.Encryption.msg={
	startDecrypting:function(selector){
		$(selector)
			.html( t('files_encryption', 'Decrypting files... Please wait, this can take some time.') )
			.removeClass('success')
			.removeClass('error')
			.stop(true, true)
			.show();
	},
	finishedDecrypting:function(selector, data){
		if( data.status === "success" ){
			 $(selector).html( data.data.message )
				.addClass('success')
				.stop(true, true)
				.delay(3000)
				.fadeOut(900);
		}else{
			$(selector).html( data.data.message ).addClass('error');
		}
	}
};

OC.msg={
	startSaving:function(selector){
		$(selector)
			.html( t('settings', 'Saving...') )
			.removeClass('success')
			.removeClass('error')
			.stop(true, true)
			.show();
	},
	finishedSaving:function(selector, data){
		if( data.status === "success" ){
			 $(selector).html( data.data.message )
				.addClass('success')
				.stop(true, true)
				.delay(3000)
				.fadeOut(900);
		}else{
			$(selector).html( data.data.message ).addClass('error');
		}
	}
};
