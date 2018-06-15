<?php

namespace App\Manager;

use App\Model\Package;

class PackagesManager
{

    /**
     * @var Package[]
     */
    protected $packages;

    protected $packagesPath;

    /**
     * PackagesManager constructor.
     */
    public function __construct()
    {
        $this->packagesPath = getcwd() . '/app/data/packages.json';
        $json = \json_decode(\file_get_contents($this->packagesPath));
        $mapper = new \JsonMapper();
        $this->packages = $mapper->mapArray($json, [], Package::class);
    }

    public function save(...$packages): PackagesManager
    {
        foreach ($packages as $package) {
            $this->addPackage($package);
        }
        $json = \json_encode($this->packages, JSON_UNESCAPED_SLASHES);
        \file_put_contents($this->packagesPath, $json);

        return $this;
    }

    /**
     * @param Package[] $packages
     *
     * @return PackagesManager
     */
    public function setPackages(array $packages): PackagesManager
    {
        $this->packages = $packages;

        return $this;
    }

    /**
     * @return Package[]
     */
    public function getPackages(): array
    {
        return $this->packages;
    }

    /**
     * @param Package $package
     *
     * @return PackagesManager
     */
    public function addPackage(Package $package): PackagesManager
    {
        $this->packages[] = $package;

        return $this;
    }

    /**
     * @param Package $package
     *
     * @return PackagesManager
     */
    public function removePackage(Package $package): ?PackagesManager
    {
        if (false !== $key = array_search($package, $this->packages, true)) {
            array_splice($this->packages, $key, 1);

            return $this;
        }
    }

    /**
     * @param string $name
     *
     * @return Package|null
     */
    public function getPackage(string $name): ?Package
    {
        foreach ($this->getPackages() as $package) {
            if ($name === $package->getName() || $name === $package->getFullName()) {
                return $package;
            }
        }

        return null;
    }


    public function getDataMenu(): array
    {
        $res = [];
        foreach ($this->getPackages() as $package) {
            foreach ($package->getVersions() as $version) {
                $res[$package->getName()] = [$version->getMinor() => $version->getDocs()];
            }
        }
        return $res;
    }

}
