<?php

namespace App\Model;

use LogicException;

class Package implements \JsonSerializable
{

    //application
    protected $name;

    //ojective-php/application
    protected $fullName;

    //2.0.0
    protected $minVersion;

    /**
     * @var Version[]
     */
    protected $versions = [];

    /**
     * Package constructor.
     *
     * @param string $name
     * @param string $fullName
     * @param mixed  $minVersion
     */
    public function __construct($name, $fullName, $minVersion = '0')
    {
        $this->name = $name;
        $this->fullName = $fullName;
        $this->minVersion = $minVersion;
    }


    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     *
     * @return Package
     */
    public function setName(string $name): Package
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getFullName(): string
    {
        return $this->fullName;
    }

    /**
     * @param string $fullName
     *
     * @return Package
     */
    public function setFullName(string $fullName): Package
    {
        $this->fullName = $fullName;

        return $this;
    }

    /**
     * @return string
     */
    public function getMinVersion(): string
    {
        return $this->minVersion;
    }

    /**
     * @param string $minVersion
     *
     * @return Package
     */
    public function setMinVersion(string $minVersion): Package
    {
        $this->minVersion = $minVersion;

        return $this;
    }

    /**
     * @return Version[]
     */
    public function getVersions(): array
    {
        return $this->versions;
    }

    /**
     * @param $minor
     *
     * @return Version|null
     */
    public function getVersion($tag): ?Version
    {
        foreach ($this->getVersions() as $version) {
            if ($version->getMinor() === $tag || $version->getPatch() === $tag) {
                return $version;
            }
        }

        return null;
    }

    /**
     * @param Version[] $versions
     *
     * @return Package
     */
    protected function setVersions($versions): Package
    {
        $this->versions = $versions;

        return $this;
    }

    /**
     * @param Version $version
     *
     * @return Package
     * @throws LogicException
     */
    public function addVersion(Version $version): Package
    {
        if (version_compare($version->getPatch(), $this->getMinVersion(), '<')) {
            throw new LogicException(sprintf(
                'You are trying to add the patch %s but the mininmum available version for documentation is %s',
                $version->getPatch(),
                $this->getMinVersion()
            ));
        }

        if ($oldVersion = $this->getVersion($version->getMinor())) {
            if (!version_compare($version->getPatch(), $oldVersion->getPatch(), '>=')) {
                throw new LogicException('There is already a superior patch');
            }
            $this->removeVersion($oldVersion);
        }
        $versions = $this->getVersions();
        $versions[] = $version;
        $this->setVersions($versions);

        return $this;
    }

    /**
     * @param Version $version
     *
     * @return Package
     */
    public function removeVersion(
        Version $version
    ): Package {
        if (false !== $key = array_search($version, $this->versions, true)) {
            array_splice($this->versions, $key, 1);
        }

        return $this;
    }

    /**
     * @return Package
     */
    public function sortVersions(): Package
    {
        //        echo '<pre>';
        //        print_r($this->getVersions());
        //        echo '</pre>';

        $versions = $this->getVersions();

        usort($versions, function ($a, $b) {
            if ($a->getMinor() > $b->getMinor()) {
                return true;
            }

            return false;
        });

        $this->setVersions($versions);

        //        echo '<pre>';
        //        print_r($this->getVersions());
        //        echo '</pre>';
        die();

        return $this;
    }

    /**
     * Specify data which should be serialized to JSON
     *
     * @link  http://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @since 5.4.0
     */
    public function jsonSerialize()
    {
        return [
            'name'       => $this->name,
            'fullName'   => $this->fullName,
            'minVersion' => $this->minVersion,
            'versions'   => $this->versions
        ];
    }
}
