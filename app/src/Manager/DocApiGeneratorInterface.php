<?php

namespace App\Manager;

interface DocApiGeneratorInterface
{
    /**
     * @param string $repositoryPath
     * @param string $componentName
     * @param string $version
     *
     * @return bool
     */
    public function generate(string $repositoryPath, string $componentName, string $version): bool;
}
