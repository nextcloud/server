<?php

/*
 * This file is part of composer/semver.
 *
 * (c) Composer <https://github.com/composer>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Composer\Semver;

use Composer\Semver\Constraint\ConstraintInterface;
use Composer\Semver\Constraint\EmptyConstraint;
use Composer\Semver\Constraint\MultiConstraint;
use Composer\Semver\Constraint\Constraint;

/**
 * Version parser.
 *
 * @author Jordi Boggiano <j.boggiano@seld.be>
 */
class VersionParser
{
    /**
     * Regex to match pre-release data (sort of).
     *
     * Due to backwards compatibility:
     *   - Instead of enforcing hyphen, an underscore, dot or nothing at all are also accepted.
     *   - Only stabilities as recognized by Composer are allowed to precede a numerical identifier.
     *   - Numerical-only pre-release identifiers are not supported, see tests.
     *
     *                        |--------------|
     * [major].[minor].[patch] -[pre-release] +[build-metadata]
     *
     * @var string
     */
    private static $modifierRegex = '[._-]?(?:(stable|beta|b|RC|alpha|a|patch|pl|p)((?:[.-]?\d+)*+)?)?([.-]?dev)?';

    /** @var string */
    private static $stabilitiesRegex = 'stable|RC|beta|alpha|dev';

    /**
     * Returns the stability of a version.
     *
     * @param string $version
     *
     * @return string
     */
    public static function parseStability($version)
    {
        $version = preg_replace('{#.+$}i', '', $version);

        if (strpos($version, 'dev-') === 0 || '-dev' === substr($version, -4)) {
            return 'dev';
        }

        preg_match('{' . self::$modifierRegex . '(?:\+.*)?$}i', strtolower($version), $match);

        if (!empty($match[3])) {
            return 'dev';
        }

        if (!empty($match[1])) {
            if ('beta' === $match[1] || 'b' === $match[1]) {
                return 'beta';
            }
            if ('alpha' === $match[1] || 'a' === $match[1]) {
                return 'alpha';
            }
            if ('rc' === $match[1]) {
                return 'RC';
            }
        }

        return 'stable';
    }

    /**
     * @param string $stability
     *
     * @return string
     */
    public static function normalizeStability($stability)
    {
        $stability = strtolower($stability);

        return $stability === 'rc' ? 'RC' : $stability;
    }

    /**
     * Normalizes a version string to be able to perform comparisons on it.
     *
     * @param string $version
     * @param string $fullVersion optional complete version string to give more context
     *
     * @throws \UnexpectedValueException
     *
     * @return string
     */
    public function normalize($version, $fullVersion = null)
    {
        $version = trim($version);
        $origVersion = $version;
        if (null === $fullVersion) {
            $fullVersion = $version;
        }

        // strip off aliasing
        if (preg_match('{^([^,\s]++) ++as ++([^,\s]++)$}', $version, $match)) {
            $version = $match[1];
        }

        // strip off stability flag
        if (preg_match('{@(?:' . self::$stabilitiesRegex . ')$}i', $version, $match)) {
            $version = substr($version, 0, strlen($version) - strlen($match[0]));
        }

        // match master-like branches
        if (preg_match('{^(?:dev-)?(?:master|trunk|default)$}i', $version)) {
            return '9999999-dev';
        }

        // if requirement is branch-like, use full name
        if (stripos($version, 'dev-') === 0) {
            return 'dev-' . substr($version, 4);
        }

        // strip off build metadata
        if (preg_match('{^([^,\s+]++)\+[^\s]++$}', $version, $match)) {
            $version = $match[1];
        }

        // match classical versioning
        if (preg_match('{^v?(\d{1,5})(\.\d++)?(\.\d++)?(\.\d++)?' . self::$modifierRegex . '$}i', $version, $matches)) {
            $version = $matches[1]
                . (!empty($matches[2]) ? $matches[2] : '.0')
                . (!empty($matches[3]) ? $matches[3] : '.0')
                . (!empty($matches[4]) ? $matches[4] : '.0');
            $index = 5;
        // match date(time) based versioning
        } elseif (preg_match('{^v?(\d{4}(?:[.:-]?\d{2}){1,6}(?:[.:-]?\d{1,3})?)' . self::$modifierRegex . '$}i', $version, $matches)) {
            $version = preg_replace('{\D}', '.', $matches[1]);
            $index = 2;
        }

        // add version modifiers if a version was matched
        if (isset($index)) {
            if (!empty($matches[$index])) {
                if ('stable' === $matches[$index]) {
                    return $version;
                }
                $version .= '-' . $this->expandStability($matches[$index]) . (isset($matches[$index + 1]) && '' !== $matches[$index + 1] ? ltrim($matches[$index + 1], '.-') : '');
            }

            if (!empty($matches[$index + 2])) {
                $version .= '-dev';
            }

            return $version;
        }

        // match dev branches
        if (preg_match('{(.*?)[.-]?dev$}i', $version, $match)) {
            try {
                $normalized = $this->normalizeBranch($match[1]);
                // a branch ending with -dev is only valid if it is numeric
                // if it gets prefixed with dev- it means the branch name should
                // have had a dev- prefix already when passed to normalize
                if (strpos($normalized, 'dev-') === false) {
                    return $normalized;
                }
            } catch (\Exception $e) {
            }
        }

        $extraMessage = '';
        if (preg_match('{ +as +' . preg_quote($version) . '(?:@(?:'.self::$stabilitiesRegex.'))?$}', $fullVersion)) {
            $extraMessage = ' in "' . $fullVersion . '", the alias must be an exact version';
        } elseif (preg_match('{^' . preg_quote($version) . '(?:@(?:'.self::$stabilitiesRegex.'))? +as +}', $fullVersion)) {
            $extraMessage = ' in "' . $fullVersion . '", the alias source must be an exact version, if it is a branch name you should prefix it with dev-';
        }

        throw new \UnexpectedValueException('Invalid version string "' . $origVersion . '"' . $extraMessage);
    }

    /**
     * Extract numeric prefix from alias, if it is in numeric format, suitable for version comparison.
     *
     * @param string $branch Branch name (e.g. 2.1.x-dev)
     *
     * @return string|false Numeric prefix if present (e.g. 2.1.) or false
     */
    public function parseNumericAliasPrefix($branch)
    {
        if (preg_match('{^(?P<version>(\d++\\.)*\d++)(?:\.x)?-dev$}i', $branch, $matches)) {
            return $matches['version'] . '.';
        }

        return false;
    }

    /**
     * Normalizes a branch name to be able to perform comparisons on it.
     *
     * @param string $name
     *
     * @return string
     */
    public function normalizeBranch($name)
    {
        $name = trim($name);

        if (in_array($name, array('master', 'trunk', 'default'))) {
            return $this->normalize($name);
        }

        if (preg_match('{^v?(\d++)(\.(?:\d++|[xX*]))?(\.(?:\d++|[xX*]))?(\.(?:\d++|[xX*]))?$}i', $name, $matches)) {
            $version = '';
            for ($i = 1; $i < 5; ++$i) {
                $version .= isset($matches[$i]) ? str_replace(array('*', 'X'), 'x', $matches[$i]) : '.x';
            }

            return str_replace('x', '9999999', $version) . '-dev';
        }

        return 'dev-' . $name;
    }

    /**
     * Parses a constraint string into MultiConstraint and/or Constraint objects.
     *
     * @param string $constraints
     *
     * @return ConstraintInterface
     */
    public function parseConstraints($constraints)
    {
        $prettyConstraint = $constraints;

        $orConstraints = preg_split('{\s*\|\|?\s*}', trim($constraints));
        $orGroups = array();

        foreach ($orConstraints as $constraints) {
            $andConstraints = preg_split('{(?<!^|as|[=>< ,]) *(?<!-)[, ](?!-) *(?!,|as|$)}', $constraints);
            if (count($andConstraints) > 1) {
                $constraintObjects = array();
                foreach ($andConstraints as $constraint) {
                    foreach ($this->parseConstraint($constraint) as $parsedConstraint) {
                        $constraintObjects[] = $parsedConstraint;
                    }
                }
            } else {
                $constraintObjects = $this->parseConstraint($andConstraints[0]);
            }

            if (1 === count($constraintObjects)) {
                $constraint = $constraintObjects[0];
            } else {
                $constraint = new MultiConstraint($constraintObjects);
            }

            $orGroups[] = $constraint;
        }

        if (1 === count($orGroups)) {
            $constraint = $orGroups[0];
        } elseif (2 === count($orGroups)
            // parse the two OR groups and if they are contiguous we collapse
            // them into one constraint
            && $orGroups[0] instanceof MultiConstraint
            && $orGroups[1] instanceof MultiConstraint
            && 2 === count($orGroups[0]->getConstraints())
            && 2 === count($orGroups[1]->getConstraints())
            && ($a = (string) $orGroups[0])
            && strpos($a, '[>=') === 0 && (false !== ($posA = strpos($a, '<', 4)))
            && ($b = (string) $orGroups[1])
            && strpos($b, '[>=') === 0 && (false !== ($posB = strpos($b, '<', 4)))
            && substr($a, $posA + 2, -1) === substr($b, 4, $posB - 5)
        ) {
            $constraint = new MultiConstraint(array(
                new Constraint('>=', substr($a, 4, $posA - 5)),
                new Constraint('<', substr($b, $posB + 2, -1)),
            ));
        } else {
            $constraint = new MultiConstraint($orGroups, false);
        }

        $constraint->setPrettyString($prettyConstraint);

        return $constraint;
    }

    /**
     * @param string $constraint
     *
     * @throws \UnexpectedValueException
     *
     * @return array
     */
    private function parseConstraint($constraint)
    {
        // strip off aliasing
        if (preg_match('{^([^,\s]++) ++as ++([^,\s]++)$}', $constraint, $match)) {
            $constraint = $match[1];
        }

        // strip @stability flags, and keep it for later use
        if (preg_match('{^([^,\s]*?)@(' . self::$stabilitiesRegex . ')$}i', $constraint, $match)) {
            $constraint = '' !== $match[1] ? $match[1] : '*';
            if ($match[2] !== 'stable') {
                $stabilityModifier = $match[2];
            }
        }

        // get rid of #refs as those are used by composer only
        if (preg_match('{^(dev-[^,\s@]+?|[^,\s@]+?\.x-dev)#.+$}i', $constraint, $match)) {
            $constraint = $match[1];
        }

        if (preg_match('{^v?[xX*](\.[xX*])*$}i', $constraint)) {
            return array(new EmptyConstraint());
        }

        $versionRegex = 'v?(\d++)(?:\.(\d++))?(?:\.(\d++))?(?:\.(\d++))?(?:' . self::$modifierRegex . '|\.([xX*][.-]?dev))(?:\+[^\s]+)?';

        // Tilde Range
        //
        // Like wildcard constraints, unsuffixed tilde constraints say that they must be greater than the previous
        // version, to ensure that unstable instances of the current version are allowed. However, if a stability
        // suffix is added to the constraint, then a >= match on the current version is used instead.
        if (preg_match('{^~>?' . $versionRegex . '$}i', $constraint, $matches)) {
            if (strpos($constraint, '~>') === 0) {
                throw new \UnexpectedValueException(
                    'Could not parse version constraint ' . $constraint . ': ' .
                    'Invalid operator "~>", you probably meant to use the "~" operator'
                );
            }

            // Work out which position in the version we are operating at
            if (isset($matches[4]) && '' !== $matches[4] && null !== $matches[4]) {
                $position = 4;
            } elseif (isset($matches[3]) && '' !== $matches[3] && null !== $matches[3]) {
                $position = 3;
            } elseif (isset($matches[2]) && '' !== $matches[2] && null !== $matches[2]) {
                $position = 2;
            } else {
                $position = 1;
            }

            // when matching 2.x-dev or 3.0.x-dev we have to shift the second or third number, despite no second/third number matching above
            if (!empty($matches[8])) {
                $position++;
            }

            // Calculate the stability suffix
            $stabilitySuffix = '';
            if (empty($matches[5]) && empty($matches[7]) && empty($matches[8])) {
                $stabilitySuffix .= '-dev';
            }

            $lowVersion = $this->normalize(substr($constraint . $stabilitySuffix, 1));
            $lowerBound = new Constraint('>=', $lowVersion);

            // For upper bound, we increment the position of one more significance,
            // but highPosition = 0 would be illegal
            $highPosition = max(1, $position - 1);
            $highVersion = $this->manipulateVersionString($matches, $highPosition, 1) . '-dev';
            $upperBound = new Constraint('<', $highVersion);

            return array(
                $lowerBound,
                $upperBound,
            );
        }

        // Caret Range
        //
        // Allows changes that do not modify the left-most non-zero digit in the [major, minor, patch] tuple.
        // In other words, this allows patch and minor updates for versions 1.0.0 and above, patch updates for
        // versions 0.X >=0.1.0, and no updates for versions 0.0.X
        if (preg_match('{^\^' . $versionRegex . '($)}i', $constraint, $matches)) {
            // Work out which position in the version we are operating at
            if ('0' !== $matches[1] || '' === $matches[2] || null === $matches[2]) {
                $position = 1;
            } elseif ('0' !== $matches[2] || '' === $matches[3] || null === $matches[3]) {
                $position = 2;
            } else {
                $position = 3;
            }

            // Calculate the stability suffix
            $stabilitySuffix = '';
            if (empty($matches[5]) && empty($matches[7]) && empty($matches[8])) {
                $stabilitySuffix .= '-dev';
            }

            $lowVersion = $this->normalize(substr($constraint . $stabilitySuffix, 1));
            $lowerBound = new Constraint('>=', $lowVersion);

            // For upper bound, we increment the position of one more significance,
            // but highPosition = 0 would be illegal
            $highVersion = $this->manipulateVersionString($matches, $position, 1) . '-dev';
            $upperBound = new Constraint('<', $highVersion);

            return array(
                $lowerBound,
                $upperBound,
            );
        }

        // X Range
        //
        // Any of X, x, or * may be used to "stand in" for one of the numeric values in the [major, minor, patch] tuple.
        // A partial version range is treated as an X-Range, so the special character is in fact optional.
        if (preg_match('{^v?(\d++)(?:\.(\d++))?(?:\.(\d++))?(?:\.[xX*])++$}', $constraint, $matches)) {
            if (isset($matches[3]) && '' !== $matches[3] && null !== $matches[3]) {
                $position = 3;
            } elseif (isset($matches[2]) && '' !== $matches[2] && null !== $matches[2]) {
                $position = 2;
            } else {
                $position = 1;
            }

            $lowVersion = $this->manipulateVersionString($matches, $position) . '-dev';
            $highVersion = $this->manipulateVersionString($matches, $position, 1) . '-dev';

            if ($lowVersion === '0.0.0.0-dev') {
                return array(new Constraint('<', $highVersion));
            }

            return array(
                new Constraint('>=', $lowVersion),
                new Constraint('<', $highVersion),
            );
        }

        // Hyphen Range
        //
        // Specifies an inclusive set. If a partial version is provided as the first version in the inclusive range,
        // then the missing pieces are replaced with zeroes. If a partial version is provided as the second version in
        // the inclusive range, then all versions that start with the supplied parts of the tuple are accepted, but
        // nothing that would be greater than the provided tuple parts.
        if (preg_match('{^(?P<from>' . $versionRegex . ') +- +(?P<to>' . $versionRegex . ')($)}i', $constraint, $matches)) {
            // Calculate the stability suffix
            $lowStabilitySuffix = '';
            if (empty($matches[6]) && empty($matches[8]) && empty($matches[9])) {
                $lowStabilitySuffix = '-dev';
            }

            $lowVersion = $this->normalize($matches['from']);
            $lowerBound = new Constraint('>=', $lowVersion . $lowStabilitySuffix);

            $empty = function ($x) {
                return ($x === 0 || $x === '0') ? false : empty($x);
            };

            if ((!$empty($matches[12]) && !$empty($matches[13])) || !empty($matches[15]) || !empty($matches[17]) || !empty($matches[18])) {
                $highVersion = $this->normalize($matches['to']);
                $upperBound = new Constraint('<=', $highVersion);
            } else {
                $highMatch = array('', $matches[11], $matches[12], $matches[13], $matches[14]);

                // validate to version
                $this->normalize($matches['to']);

                $highVersion = $this->manipulateVersionString($highMatch, $empty($matches[12]) ? 1 : 2, 1) . '-dev';
                $upperBound = new Constraint('<', $highVersion);
            }

            return array(
                $lowerBound,
                $upperBound,
            );
        }

        // Basic Comparators
        if (preg_match('{^(<>|!=|>=?|<=?|==?)?\s*(.*)}', $constraint, $matches)) {
            try {
                try {
                    $version = $this->normalize($matches[2]);
                } catch (\UnexpectedValueException $e) {
                    // recover from an invalid constraint like foobar-dev which should be dev-foobar
                    // except if the constraint uses a known operator, in which case it must be a parse error
                    if (substr($matches[2], -4) === '-dev' && preg_match('{^[0-9a-zA-Z-./]+$}', $matches[2])) {
                        $version = $this->normalize('dev-'.substr($matches[2], 0, -4));
                    } else {
                        throw $e;
                    }
                }

                $op = $matches[1] ?: '=';

                if ($op !== '==' && $op !== '=' && !empty($stabilityModifier) && self::parseStability($version) === 'stable') {
                    $version .= '-' . $stabilityModifier;
                } elseif ('<' === $op || '>=' === $op) {
                    if (!preg_match('/-' . self::$modifierRegex . '$/', strtolower($matches[2]))) {
                        if (strpos($matches[2], 'dev-') !== 0) {
                            $version .= '-dev';
                        }
                    }
                }

                return array(new Constraint($matches[1] ?: '=', $version));
            } catch (\Exception $e) {
            }
        }

        $message = 'Could not parse version constraint ' . $constraint;
        if (isset($e)) {
            $message .= ': ' . $e->getMessage();
        }

        throw new \UnexpectedValueException($message);
    }

    /**
     * Increment, decrement, or simply pad a version number.
     *
     * Support function for {@link parseConstraint()}
     *
     * @param array  $matches   Array with version parts in array indexes 1,2,3,4
     * @param int    $position  1,2,3,4 - which segment of the version to increment/decrement
     * @param int    $increment
     * @param string $pad       The string to pad version parts after $position
     *
     * @return string|null The new version
     */
    private function manipulateVersionString($matches, $position, $increment = 0, $pad = '0')
    {
        for ($i = 4; $i > 0; --$i) {
            if ($i > $position) {
                $matches[$i] = $pad;
            } elseif ($i === $position && $increment) {
                $matches[$i] += $increment;
                // If $matches[$i] was 0, carry the decrement
                if ($matches[$i] < 0) {
                    $matches[$i] = $pad;
                    --$position;

                    // Return null on a carry overflow
                    if ($i === 1) {
                        return null;
                    }
                }
            }
        }

        return $matches[1] . '.' . $matches[2] . '.' . $matches[3] . '.' . $matches[4];
    }

    /**
     * Expand shorthand stability string to long version.
     *
     * @param string $stability
     *
     * @return string
     */
    private function expandStability($stability)
    {
        $stability = strtolower($stability);

        switch ($stability) {
            case 'a':
                return 'alpha';
            case 'b':
                return 'beta';
            case 'p':
            case 'pl':
                return 'patch';
            case 'rc':
                return 'RC';
            default:
                return $stability;
        }
    }
}
