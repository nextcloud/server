$(document).ready(function(){
    $('#file_versioning_head').chosen();

    $.getJSON(OC.filePath('files_versioning', 'ajax', 'gethead.php'), function(jsondata, status) {

        if (jsondata.head == 'HEAD') {
            // Most recent commit, do nothing
        } else {
            $("#file_versioning_head").val(jsondata.head);
            // Trigger the chosen update call
            // See http://harvesthq.github.com/chosen/
            $("#file_versioning_head").trigger("liszt:updated");
        }
    });

    $('#file_versioning_head').change(function() {

        var data = $(this).serialize();
        $.post( OC.filePath('files_versioning', 'ajax', 'sethead.php'), data, function(data){
	        if(data == 'error'){
		        console.log('Saving new HEAD failed');
	        }
        });
    });
});
