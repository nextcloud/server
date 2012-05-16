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
		echo OCP\Util::formatDate( $v['version'] );
		echo ' <a href="history.php?path='.urlencode( $_['path'] ).'&revert='. $v['version'] .'" class="button">Revert</a>';
		if ( $v['cur'] ) { echo '  (<b>Current</b>)'; }
		echo '<br /><br />';
		
	}

}

?>
</div>
