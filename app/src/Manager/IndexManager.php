<?php

namespace App\Manager;

use App\Config\PathsConfig;

/**
 * Class IndexManager
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
        if (!is_dir($this->getPaths()['tmp']) && !mkdir($this->getPaths()['tmp'], 0755, true) && !is_dir($this->getPaths()['tmp'])) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', $this->getPaths()['tmp']));
        }
        if (!is_dir($this->getPaths()['doc']) && !mkdir($this->getPaths()['doc'], 0755, true) && !is_dir($this->getPaths()['doc'])) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', $this->getPaths()['doc']));
        }

        file_put_contents($this->getPaths()['doc'] . 'index.html', $this->docIndex());
    }

    public function docIndex(): string
    {
        $asset = json_decode(file_get_contents($this->getPaths()['public'] . 'dist/manifest.json'), true);

        $content = file_get_contents($this->getPaths()['html'] . 'doclayout.html');
        $pageContent = '<h1>Welcome to Objective PHP user documentation</h1><br/><br/>
<p>You can navigate through it from the left menu.</p><br/>
<p>Please take attention on the versions you are using</p>';
        $content = str_replace('{{PAGE-CONTENT}}', $pageContent, $content);
        $content = str_replace('{{TITLE}}', 'Objective PHP Documentation', $content);
        $content = str_replace('{{VERSION}}', '', $content);
        $content = str_replace('{{COMPONENT-NAME}}', '', $content);
        $content = str_replace('{{STYLE}}', $asset['theme.css'], $content);
        $content = str_replace('{{SCRIPT}}', $asset['app.js'], $content);

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

        return $this;
    }
}
