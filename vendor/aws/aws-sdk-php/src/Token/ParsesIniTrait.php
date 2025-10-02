<?php
namespace Aws\Token;

trait ParsesIniTrait
{
    /**
     * Gets profiles from specified $filename, or default ini files.
     */
    private static function loadProfiles($filename)
    {
        $profileData = \Aws\parse_ini_file($filename, true, INI_SCANNER_RAW);
        $configFilename = self::getHomeDir() . '/.aws/config';
        if (is_readable($configFilename)) {
            $configProfiles = \Aws\parse_ini_file($configFilename, true, INI_SCANNER_RAW);
            $profileData = array_merge($configProfiles, $profileData);
        }
        foreach ($profileData as $name => $profile) {
            // standardize config profile names
            $name = str_replace('profile ', '', $name);
            $profileData[$name] = $profile;
        }

        return $profileData;
    }

    /**
     * Gets the environment's HOME directory if available.
     *
     * @return null|string
     */
    private static function getHomeDir()
    {
        // On Linux/Unix-like systems, use the HOME environment variable
        if ($homeDir = getenv('HOME')) {
            return $homeDir;
        }

        // Get the HOMEDRIVE and HOMEPATH values for Windows hosts
        $homeDrive = getenv('HOMEDRIVE');
        $homePath = getenv('HOMEPATH');

        return ($homeDrive && $homePath) ? $homeDrive . $homePath : null;
    }
}
