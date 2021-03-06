<?php

/**
 * This file is part of the Version package.
 *
 * Copyright (c) Nikola Posa <posa.nikola@gmail.com>
 *
 * For full copyright and license information, please refer to the LICENSE file,
 * located at the package root folder.
 */

namespace Version;

use Version\Metadata\PreRelease;
use Version\Metadata\Build;
use Version\Exception\InvalidVersionElementException;
use Version\Exception\InvalidVersionStringException;

/**
 * @author Nikola Posa <posa.nikola@gmail.com>
 */
final class Version
{
    /**
     * @var int
     */
    private $major;

    /**
     * @var int
     */
    private $minor;

    /**
     * @var int
     */
    private $patch;

    /**
     * @var PreRelease
     */
    private $preRelease;

    /**
     * @var Build
     */
    private $build;

    private function __construct($major, $minor, $patch, PreRelease $preRelease, Build $build)
    {
        $this->major = $major;
        $this->minor = $minor;
        $this->patch = $patch;
        $this->preRelease = $preRelease;
        $this->build = $build;
    }

    /**
     * @param int $major
     * @param int $minor
     * @param int $patch
     * @param PreRelease|array|string $preRelease
     * @param Build|array|string $build
     * @return self
     */
    public static function fromAllElements($major, $minor, $patch, $preRelease, $build)
    {
        self::validateVersionElement('major', $major);

        self::validateVersionElement('minor', $minor);

        self::validateVersionElement('patch', $patch);

        if (!$preRelease instanceof PreRelease) {
            $preRelease = PreRelease::create($preRelease);
        }

        if (!$build instanceof Build) {
            $build = Build::create($build);
        }

        return new self($major, $minor, $patch, $preRelease, $build);
    }

    private static function validateVersionElement($element, $value)
    {
        if (!is_int($value) || $value < 0) {
            throw InvalidVersionElementException::forElement($element);
        }
    }

    /**
     * @param int $major
     * @return self
     */
    public static function fromMajor($major)
    {
        return self::fromAllElements($major, 0, 0, PreRelease::createEmpty(), Build::createEmpty());
    }

    /**
     * @param int $major
     * @param int $minor
     * @return self
     */
    public static function fromMinor($major, $minor)
    {
        return self::fromAllElements($major, $minor, 0, PreRelease::createEmpty(), Build::createEmpty());
    }

    /**
     * @param int $major
     * @param int $minor
     * @param int $patch
     * @return self
     */
    public static function fromPatch($major, $minor, $patch)
    {
        return self::fromAllElements($major, $minor, $patch, PreRelease::createEmpty(), Build::createEmpty());
    }

    /**
     * @param int $major
     * @param int $minor
     * @param int $patch
     * @param @param PreRelease|array|string $preRelease
     * @return self
     */
    public static function fromPreRelease($major, $minor, $patch, $preRelease)
    {
        return self::fromAllElements($major, $minor, $patch, $preRelease, Build::createEmpty());
    }

    /**
     * @param int $major
     * @param int $minor
     * @param int $patch
     * @param Build|array|string $build
     * @return self
     */
    public static function fromBuild($major, $minor, $patch, $build)
    {
        return self::fromAllElements($major, $minor, $patch, PreRelease::createEmpty(), $build);
    }

    /**
     * @param string $versionString
     * @return self
     * @throws InvalidVersionStringException
     */
    public static function fromString($versionString)
    {
        $parts = [];

        if (!preg_match(
            '#^'
            . '(?P<core>(?:[0-9]|[1-9][0-9]+)(?:\.(?:[0-9]|[1-9][0-9]+)){2})'
            . '(?:\-(?P<preRelease>[0-9A-Za-z\-\.]+))?'
            . '(?:\+(?P<build>[0-9A-Za-z\-\.]+))?'
            . '$#',
            (string) $versionString,
            $parts
        )) {
            throw new InvalidVersionStringException("Version string is not valid and cannot be parsed");
        }

        list($major, $minor, $patch) = explode('.', $parts['core']);
        $major = (int) $major;
        $minor = (int) $minor;
        $patch = (int) $patch;

        $preRelease = (!empty($parts['preRelease'])) ? $parts['preRelease'] : PreRelease::createEmpty();

        $build = (!empty($parts['build'])) ? $parts['build'] : Build::createEmpty();

        return self::fromAllElements($major, $minor, $patch, $preRelease, $build);
    }

    /**
     * @return int
     */
    public function getMajor()
    {
        return $this->major;
    }

    /**
     * @return int
     */
    public function getMinor()
    {
        return $this->minor;
    }

    /**
     * @return int
     */
    public function getPatch()
    {
        return $this->patch;
    }

    /**
     * @return PreRelease
     */
    public function getPreRelease()
    {
        return $this->preRelease;
    }

    /**
     * @return Build
     */
    public function getBuild()
    {
        return $this->build;
    }

    /**
     * @return bool
     */
    public function isPreRelease()
    {
        return !$this->preRelease->isEmpty();
    }

    /**
     * @return bool
     */
    public function isBuild()
    {
        return !$this->build->isEmpty();
    }

    /**
     * @return string
     */
    public function getVersionString()
    {
        return
            $this->major
            . '.' . $this->minor
            . '.' . $this->patch
            . ($this->isPreRelease() ? '-' . (string) $this->preRelease : '')
            . ($this->isBuild() ? '+' . (string) $this->build : '')
        ;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->getVersionString();
    }

    /**
     * @param self|string $version
     * @return int (1 if $this > $version, -1 if $this < $version, 0 if equal)
     */
    public function compareTo($version)
    {
        if (!$version instanceof self) {
            $version = self::fromString((string) $version);
        }

        if ($this->major > $version->major) {
            return 1;
        }

        if ($this->major < $version->major) {
            return -1;
        }

        if ($this->minor > $version->minor) {
            return 1;
        }

        if ($this->minor < $version->minor) {
            return -1;
        }

        if ($this->patch > $version->patch) {
            return 1;
        }

        if ($this->patch < $version->patch) {
            return -1;
        }

        if (!$this->isPreRelease() && $version->isPreRelease()) {
            // normal version has greater precedence than a pre-release version version
            return 1;
        }

        if ($this->isPreRelease() && !$version->isPreRelease()) {
            // pre-release version has lower precedence than a normal version
            return -1;
        }

        if ($this->isPreRelease() && $version->isPreRelease()) {
            $result = $this->preRelease->compareTo($version->preRelease);

            if ($result > 0) {
                return 1;
            } elseif ($result < 0) {
                return -1;
            }
        }

        return 0;
    }

    /**
     * @param self|string $version
     * @return bool
     */
    public function isEqualTo($version)
    {
        return $this->compareTo($version) == 0;
    }

    /**
     * @param self|string $version
     * @return bool
     */
    public function isGreaterThan($version)
    {
        return $this->compareTo($version) > 0;
    }

    /**
     * @param self|string $version
     * @return bool
     */
    public function isLessThan($version)
    {
        return $this->compareTo($version) < 0;
    }

    /**
     * @return self
     */
    public function withMajorIncremented()
    {
        return self::fromAllElements($this->major + 1, 0, 0, PreRelease::createEmpty(), Build::createEmpty());
    }

    /**
     * @return self
     */
    public function withMinorIncremented()
    {
        return self::fromAllElements($this->major, $this->minor + 1, 0, PreRelease::createEmpty(), Build::createEmpty());
    }

    /**
     * @return self
     */
    public function withPatchIncremented()
    {
        return self::fromAllElements($this->major, $this->minor, $this->patch + 1, PreRelease::createEmpty(), Build::createEmpty());
    }

    /**
     * @param PreRelease|array|string $preRelease
     * @return self
     */
    public function withPreRelease($preRelease)
    {
        return self::fromAllElements($this->major, $this->minor, $this->patch, $preRelease, Build::createEmpty());
    }

    /**
     * @param Build|array|string $build
     * @return self
     */
    public function withBuild($build)
    {
        return self::fromAllElements($this->major, $this->minor, $this->patch, $this->preRelease, $build);
    }
}
