<?php

// Get the full path to the repository folder (FIXME: hard-coded to 'Backup')
$path = OC_Config::getValue('datadirectory', OC::$SERVERROOT.'/data')
        . DIRECTORY_SEPARATOR
        . OC_User::getUser()
        . DIRECTORY_SEPARATOR
        . 'files'
        . DIRECTORY_SEPARATOR
        . 'Backup'
        . DIRECTORY_SEPARATOR
        . '.git'
        . DIRECTORY_SEPARATOR;

$repository = new Granite\Git\Repository($path);

$commits = array();
// Fetch most recent 50 commits (FIXME - haven't tested this much)
$commit = $repository->head();
for ($i = 0; $i < 50; $i++) {
    $commits[] = $commit;
    $parents = $commit->parents();
    if (count($parents) > 0) {
        $parent = $parents[0];
    } else {
        break;
    }

    $commit = $repository->factory('commit', $parent);
}

$tmpl = new OC_Template( 'files_versioning', 'settings');
$tmpl->assign('commits', $commits);
return $tmpl->fetchPage();
