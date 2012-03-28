<?php
$appInfoDir = __DIR__;
$thisAppDir = dirname($appInfoDir);
$appsDir = dirname($thisAppDir);
$ownCloudDir = dirname($appsDir);
$docRoot = $_SERVER['DOCUMENT_ROOT'];
if(file_exists($docRoot . '/.well-known/host-meta')) {
    OC_Log::write(
        'user_webfinger',
        $docRoot . "/.well-known already exists; installation aborted",
        OC_Log::ERROR
    );
} else {
    if(@symlink($thisAppDir, $docRoot . '/.well-known')) {
        OC_Log::write(
            'user_webfinger',
            "Webfinger symlink created at " . $docRoot . "/.well-known",
            OC_Log::INFO
        );
    } else {
        if(@symlink($thisAppDir, $ownCloudDir . '/.well-known')) {
            OC_Log::write(
                'user_webfinger',
                "Couldn't create webfinger symlink in document root, linked to " . $ownCloudDir . "/.well-known instead",
                OC_Log::WARN
            );
        } else {
            OC_Log::write(
                'user_webfinger',
                "Couldn't create webfinger symlink, either check write permissions or create the link manually!",
                OC_Log::ERROR
            );
        }
    }
}
?>
