<?php

// We only can count up. The 4. digit is only for the internal patchlevel to trigger DB upgrades between betas, final and RCs. This is _not_ the public version number. Reset minor/patchlevel when updating major/minor version number.
$OC_Version=array(5, 80, 8, 0);

// The human radable string
$OC_VersionString='6.0 pre alpha';

// The ownCloud edition
$OC_Edition='';

// The ownCloud channel
$OC_Channel='';

// The build number
$OC_Build='';

