<div id="history">

<?php

if( isset( $_['message'] ) ) {


	if( isset($_['path'] ) ) echo('<strong>File: '.$_['path'] ).'</strong><br>';
	echo('<strong>'.$_['message'] ).'</strong><br>';

}else{

	if( isset( $_['outcome_stat'] ) ) {
		
		echo( '<div id="feedback-messages" class="'.$_['outcome_stat'].'"><h3>'.$_['outcome_msg'] ).'</h3></div><br>';

	}

	echo( '<strong>Versions of '.$_['path'] ).'</strong><br>';
	echo('<p><em>You can click on the revert button to revert to the specific verson.</em></p><br />');
	
	foreach ( $_['versions'] as $v ) {
	
		echo ' ';
		echo OC_Util::formatDate( $v );
		echo ' <a href="history.php?path='.urlencode( $_['path'] ).'&revert='. $v .'" class="button">Revert</a><br /><br />';
		
	}

}

?>
</div>
