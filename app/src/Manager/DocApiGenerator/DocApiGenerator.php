<?php

namespace App\Manager\DocApiGenerator;

use App\Manager\DocApiGeneratorInterface;
use Sami\Sami;
use Symfony\Component\Finder\Finder;

require_once 'phar://' . __DIR__ . '/../../../../sami.phar/Sami/Sami.php';
require_once 'phar://' . __DIR__ . '/../../../../sami.phar/vendor/autoload.php';

class DocApiGenerator implements DocApiGeneratorInterface
{

    /**
     * @var PathsConfig[]
     */
    protected $paths;

    /**
     * @param string $repositoryPath
     * @param string $componentName
     * @param string $version
     *
     * @return string
     * @throws \InvalidArgumentException
     */
    public function generate(string $repositoryPath, string $componentName, string $version): string
    {
        $iterator = Finder::create()
            ->files()
            ->name('*.php')
            ->in($dir = $this->getPaths()['tmp'] . $repositoryPath . '/src');

        $sami = new Sami($iterator, [
            'theme'                => 'objectivephp-sami',
            'versions'             => $version,
            'title'                => ucfirst(str_replace('-', ' ', $componentName)),
            'build_dir'            => $this->getPaths()['doc'] . $componentName . '/%version%/api',
            'cache_dir'            => $this->getPaths()['tmp'] . '/cache/' . $componentName . '/%version%',
            'template_dirs'        => [$this->getPaths()['layouts']],
            'default_opened_level' => 1
        ]);

        $sami->extend('twig', function ($twig) use ($componentName) {
            $asset = json_decode(file_get_contents($this->getPaths()['manifest.json']), true);
            $twig->addGlobal('style', $asset['theme.css']);
            $twig->addGlobal('app', $asset['app.js']);
            $twig->addGlobal('componentrawname', $componentName);
            $twig->addGlobal('githublinktext', 'This package on Github');

            return $twig;
        });

        $sami->extend('renderer', function () use ($sami) {
            return new SamiRenderer(
                $sami->offsetGet('twig'),
                $sami->offsetGet('themes'),
                $sami->offsetGet('tree'),
                $sami->offsetGet('indexer')
            );
        });

        $sami->offsetGet('project')->update(null, true);

        return 'Worked';
    }

    /**
     * @return PathsConfig[]
     */
    public function getPaths(): array
    {
        return $this->paths;
    }

    /**
     * @param PathsConfig[] $paths
     *
     * @return $this
     */
    public function setPaths($paths): self
    {
        $this->paths = $paths;

        return $this;
    }
}
