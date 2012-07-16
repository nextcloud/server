<?php if(OC_User::userExists(WF_USER)): ?>
    {
        "rel":"remoteStorage",
        "template":"<?php echo WF_BASEURL; ?>/remote.php/remoteStorage/<?php echo WF_USER; ?>/remoteStorage/{category}/",
        "api":"WebDAV",
        "auth":"<?php echo WF_BASEURL; ?>/?app=remoteStorage&getfile=auth.php&userid=<?php echo WF_USER; ?>"
    }
<?php endif ?>
