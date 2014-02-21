$(document).ready(function() {
	
	var loadTemplate = function (theme, template) {
			$.get(
				OC.filePath( 'files_sharing', 'ajax', 'getmailtemplate.php' )
				, { theme: theme, template: template }
			).done(function( result ) {
				$( '#mailTemplateSettings textarea' ).val(result);
			}).fail(function( result ) {
				alert(result);
			});
		
	}
	
	// load default template
	var theme = $( '#mts-theme' ).val();
	var template = $( '#mts-template' ).val();
	loadTemplate(theme, template);

	$( '#mts-template' ).change(
		function() {
			var theme = $( '#mts-theme' ).val();
			var template = $( this ).val();
			loadTemplate(theme, template);
		}
	);
	$( '#mts-theme' ).change(
		function() {
			var theme = $( this ).val();
			var template = $( '#mts-template' ).val();
			loadTemplate(theme, template);
		}
	);
	$( '#mailTemplateSettings .actions' ).on('click', '.save',
		function() {
			var theme = $( '#mts-theme' ).val();
			var template = $( '#mts-template' ).val();
			var content = $( '#mailTemplateSettings textarea' ).val();
			OC.msg.startSaving('#mts-msg');
			$.post(
				OC.filePath( 'files_sharing', 'ajax', 'setmailtemplate.php' )
				, { theme: theme, template: template, content: content }
			).done(function( result ) {
				var data = { status:'success', data:{message:t('files_sharing', 'Saved')} };
				OC.msg.finishedSaving('#mts-msg', data);
			}).fail(function( result ) {
				var data = { status:'error', data:{message:t('files_sharing', 'Error')} };
				OC.msg.finishedSaving('#mts-msg', data);
			});	
		}
	);
	$( '#mailTemplateSettings .actions' ).on('click', '.reset',
		function() {
			var theme = $( '#mts-theme' ).val();
			var template = $( '#mts-template' ).val();
			var content = $( '#mailTemplateSettings textarea' ).val();
			OC.msg.startSaving('#mts-msg');
			$.post(
				OC.filePath( 'files_sharing', 'ajax', 'resetmailtemplate.php' )
				, { theme: theme, template: template }
			).done(function( result ) {
				var data = { status:'success', data:{message:t('files_sharing', 'Reset')} };
				OC.msg.finishedSaving('#mts-msg', data);

				// load default template
				var theme = $( '#mts-theme' ).val();
				var template = $( '#mts-template' ).val();
				loadTemplate(theme, template);
			}).fail(function( result ) {
				var data = { status:'error', data:{message:t('files_sharing', 'Error')} };
				OC.msg.finishedSaving('#mts-msg', data);
			});	
		}
	);
});
