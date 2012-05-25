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
	echo('<p><em>Revert a file to a previous version by clicking on its revert button</em></p><br />');
	
	foreach ( $_['versions'] as $v ) {
	
		echo ' ';
		echo OCP\Util::formatDate( $v );
		echo ' <a href="'.OCP\Util::linkTo('files_versions', 'history.php').'?path='.urlencode( $_['path'] ).'&revert='. $v .'" class="button">Revert</a><br /><br />';
		
	}

}

?>
</div>
