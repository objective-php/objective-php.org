<?php

namespace App\Manager;

use App\Config\PathsConfig;

/**
 * Class IndexManager
 *
 * @package App\Manager
 */
class IndexManager
{

    /**
     * @var PathsConfig[]
     */
    protected $paths;


    public function generateAll(): void
    {
        \file_put_contents($this->getPaths()['doc'] . 'index.html', $this->docIndex());
    }

    public function docIndex(): string
    {
        $asset = \json_decode(file_get_contents($this->getPaths()['public'] . 'dist/manifest.json'), true);
        $content = file_get_contents($this->getPaths()['base.twig']);
        $pageContent = file_get_contents($this->getPaths()['app'] . 'layouts/objectivephp-sami/doc-index-content.twig');
        $content = str_replace(
            [
                '{% block content \'\' %}',
                '{% block title project.config(\'title\') %}',
                '{% block VERSION \'\' %}',
                '{% block COMPONENTNAME \'\' %}',
                '{{ componentrawname }}',
                '{{ githublinktext }}',
                '{{ style }}',
                '{{ app }}'
            ],
            [
                $pageContent,
                'Objective PHP Documentation',
                '',
                '',
                '',
                '',
                $asset['theme.css'],
                $asset['app.js']
            ],
            $content
        );

        return $content;
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
    public function setPaths($paths): IndexManager
    {
        $this->paths = $paths;
        $this->generateAll();

        return $this;
    }
}
