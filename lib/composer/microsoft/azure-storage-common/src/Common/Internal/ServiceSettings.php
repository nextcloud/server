<?php

/**
 * LICENSE: The MIT License (the "License")
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 * https://github.com/azure/azure-storage-php/LICENSE
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * PHP version 5
 *
 * @ignore
 * @category  Microsoft
 * @package   MicrosoftAzure\Storage\Common\Internal
 * @author    Azure Storage PHP SDK <dmsh@microsoft.com>
 * @copyright 2016 Microsoft Corporation
 * @license   https://github.com/azure/azure-storage-php/LICENSE
 * @link      https://github.com/azure/azure-storage-php
 */

namespace MicrosoftAzure\Storage\Common\Internal;

/**
 * Base class for all REST services settings.
 *
 * Derived classes must implement the following members:
 * 1- $isInitialized: A static property that indicates whether the class's static
 *    members have been initialized.
 * 2- init(): A protected static method that initializes static members.
 * 3- $validSettingKeys: A static property that contains valid setting keys for this
 *    service.
 * 4- createFromConnectionString($connectionString): A public static function that
 *    takes a connection string and returns the created settings object.
 *
 * @category  Microsoft
 * @package   MicrosoftAzure\Storage\Common\Internal
 * @author    Azure Storage PHP SDK <dmsh@microsoft.com>
 * @copyright 2016 Microsoft Corporation
 * @license   https://github.com/azure/azure-storage-php/LICENSE
 * @link      https://github.com/azure/azure-storage-php
 */
abstract class ServiceSettings
{
    /**
     * Throws an exception if the connection string format does not match any of the
     * available formats.
     *
     * @param string $connectionString The invalid formatted connection string.
     *
     * @return void
     *
     * @throws \RuntimeException
     */
    protected static function noMatch($connectionString)
    {
        throw new \RuntimeException(
            sprintf(Resources::MISSING_CONNECTION_STRING_SETTINGS, $connectionString)
        );
    }

    /**
     * Parses the connection string and then validate that the parsed keys belong to
     * the $validSettingKeys
     *
     * @param string $connectionString The user provided connection string.
     *
     * @return array The tokenized connection string keys.
     *
     * @throws \RuntimeException
     */
    protected static function parseAndValidateKeys($connectionString)
    {
        // Initialize the static values if they are not initialized yet.
        if (!static::$isInitialized) {
            static::init();
            static::$isInitialized = true;
        }

        $tokenizedSettings = ConnectionStringParser::parseConnectionString(
            'connectionString',
            $connectionString
        );

        // Assure that all given keys are valid.
        foreach ($tokenizedSettings as $key => $value) {
            if (!Utilities::inArrayInsensitive($key, static::$validSettingKeys)) {
                throw new \RuntimeException(
                    sprintf(
                        Resources::INVALID_CONNECTION_STRING_SETTING_KEY,
                        $key,
                        implode("\n", static::$validSettingKeys)
                    )
                );
            }
        }

        return $tokenizedSettings;
    }

    /**
     * Creates an anonymous function that acts as predicate.
     *
     * @param array   $requirements The array of conditions to satisfy.
     * @param boolean $isRequired   Either these conditions are all required or all
     * optional.
     * @param boolean $atLeastOne   Indicates that at least one requirement must
     * succeed.
     *
     * @return callable
     */
    protected static function getValidator(
        array $requirements,
        $isRequired,
        $atLeastOne
    ) {
        // @codingStandardsIgnoreStart

        return function ($userSettings) use ($requirements, $isRequired, $atLeastOne) {
            $oneFound = false;
            $result   = array_change_key_case($userSettings);
            foreach ($requirements as $requirement) {
                $settingName = strtolower($requirement[Resources::SETTING_NAME]);

                // Check if the setting name exists in the provided user settings.
                if (array_key_exists($settingName, $result)) {
                    // Check if the provided user setting value is valid.
                    $validationFunc = $requirement[Resources::SETTING_CONSTRAINT];
                    $isValid        = $validationFunc($result[$settingName]);

                    if ($isValid) {
                        // Remove the setting as indicator for successful validation.
                        unset($result[$settingName]);
                        $oneFound = true;
                    }
                } else {
                    // If required then fail because the setting does not exist
                    if ($isRequired) {
                        return null;
                    }
                }
            }

            if ($atLeastOne) {
                // At least one requirement must succeed, otherwise fail.
                return $oneFound ? $result : null;
            } else {
                return $result;
            }
        };

        // @codingStandardsIgnoreEnd
    }

    /**
     * Creates at lease one succeed predicate for the provided list of requirements.
     *
     * @return callable
     */
    protected static function atLeastOne()
    {
        $allSettings = func_get_args();
        return self::getValidator($allSettings, false, true);
    }

    /**
     * Creates an optional predicate for the provided list of requirements.
     *
     * @return callable
     */
    protected static function optional()
    {
        $optionalSettings = func_get_args();
        return self::getValidator($optionalSettings, false, false);
    }

    /**
     * Creates an required predicate for the provided list of requirements.
     *
     * @return callable
     */
    protected static function allRequired()
    {
        $requiredSettings = func_get_args();
        return self::getValidator($requiredSettings, true, false);
    }

    /**
     * Creates a setting value condition using the passed predicate.
     *
     * @param string   $name      The setting key name.
     * @param callable $predicate The setting value predicate.
     *
     * @return array
     */
    protected static function settingWithFunc($name, $predicate)
    {
        $requirement                                = array();
        $requirement[Resources::SETTING_NAME]       = $name;
        $requirement[Resources::SETTING_CONSTRAINT] = $predicate;

        return $requirement;
    }

    /**
     * Creates a setting value condition that validates it is one of the
     * passed valid values.
     *
     * @param string $name The setting key name.
     *
     * @return array
     */
    protected static function setting($name)
    {
        $validValues = func_get_args();

        // Remove $name argument.
        unset($validValues[0]);

        $validValuesCount = func_num_args();

        $predicate = function ($settingValue) use ($validValuesCount, $validValues) {
            if (empty($validValues)) {
                // No restrictions, succeed,
                return true;
            }

            // Check to find if the $settingValue is valid or not. The index must
            // start from 1 as unset deletes the value but does not update the array
            // indecies.
            for ($index = 1; $index < $validValuesCount; $index++) {
                if ($settingValue == $validValues[$index]) {
                    // $settingValue is found in valid values set, succeed.
                    return true;
                }
            }

            throw new \RuntimeException(
                sprintf(
                    Resources::INVALID_CONFIG_VALUE,
                    $settingValue,
                    implode("\n", $validValues)
                )
            );

            // $settingValue is missing in valid values set, fail.
            return false;
        };

        return self::settingWithFunc($name, $predicate);
    }

    /**
     * Tests to see if a given list of settings matches a set of filters exactly.
     *
     * @param array $settings The settings to check.
     *
     * @return boolean If any filter returns null, false. If there are any settings
     * left over after all filters are processed, false. Otherwise true.
     */
    protected static function matchedSpecification(array $settings)
    {
        $constraints = func_get_args();

        // Remove first element which corresponds to $settings
        unset($constraints[0]);

        foreach ($constraints as $constraint) {
            $remainingSettings = $constraint($settings);

            if (is_null($remainingSettings)) {
                return false;
            } else {
                $settings = $remainingSettings;
            }
        }

        if (empty($settings)) {
            return true;
        }

        return false;
    }
}
