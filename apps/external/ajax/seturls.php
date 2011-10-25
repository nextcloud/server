<?php
/**
 * Copyright (c) 2011, Frank Karlitschek <karlitschek@kde.org>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING-README file.
 */

require_once('../../../lib/base.php');
OC_Util::checkAdminUser();

if(isset($_POST['s1name'])) OC_Config::setValue( 'external-site1name', $_POST['s1name'] );
if(isset($_POST['s1url'])) OC_Config::setValue( 'external-site1url', $_POST['s1url'] );
if(isset($_POST['s2name'])) OC_Config::setValue( 'external-site2name', $_POST['s2name'] );
if(isset($_POST['s2url'])) OC_Config::setValue( 'external-site2url', $_POST['s2url'] );
if(isset($_POST['s3name'])) OC_Config::setValue( 'external-site3name', $_POST['s3name'] );
if(isset($_POST['s3url'])) OC_Config::setValue( 'external-site3url', $_POST['s3url'] );
if(isset($_POST['s4name'])) OC_Config::setValue( 'external-site4name', $_POST['s4name'] );
if(isset($_POST['s4url'])) OC_Config::setValue( 'external-site4url', $_POST['s4url'] );
if(isset($_POST['s5name'])) OC_Config::setValue( 'external-site5name', $_POST['s5name'] );
if(isset($_POST['s5url'])) OC_Config::setValue( 'external-site5url', $_POST['s5url'] );

echo 'true';

?>
