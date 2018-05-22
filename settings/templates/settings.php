<?php
/**
 * @copyright Copyright (c) 2018, John Molakvoæ (skjnldsv) <skjnldsv@protonmail.com>
 * @author John Molakvoæ (skjnldsv) <skjnldsv@protonmail.com>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This is the default empty template to load Vue!
 * Do your cbackend computations into a php files
 * then serve this file as template and include your data into
 * the $serverData template variable
 *
 * return new TemplateResponse('settings', 'settings', ['serverData' => $serverData]);
 * 
 */

script('settings', 'main');
style('settings', 'settings');

// Did we have some data to inject ?
if(is_array($_['serverData'])) {
?>
<span id="serverData" data-server="<?php p(json_encode($_['serverData']));?>"></span>
<?php } ?>
