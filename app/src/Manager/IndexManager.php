<?php

namespace App\Manager;

use App\Config\PathsConfig;

/**
 * Class IndexManager
 * @package App\Manager
 */
class IndexManager
{
//    public const DOC_DIR = __DIR__ . '/../../../public/doc/';
//    public const HTML_DIR = __DIR__ . '/../../layouts/html/';

//    public $base_url;

    /**
     * @var PathsConfig[]
     */
    protected $paths;

    /**
     * IndexManager constructor.
     */
    public function __construct()
    {
//        $this->base_url = 'http://' . $_SERVER['HTTP_HOST'];

    }


    public function generateAll(): void
    {
        file_put_contents(__DIR__ . '/../../../public/doc/index.html', $this->docIndex());
    }

    public function docIndex(): string
    {
        $content = file_get_contents($this->getPaths()['html'] . 'doclayout.html');
        $pageContent = '<h1>Welcome to Objective PHP user documentation</h1><br/><br/>
<p>You can navigate through it from the left menu.</p><br/>
<p>Please take attention on the versions you are using</p>';
        $content = str_replace('{{PAGE-CONTENT}}', $pageContent, $content);
        $content = str_replace('{{TITLE}}', 'Objective PHP Documentation', $content);
        $content = str_replace('{{VERSION}}', '', $content);
        $content = str_replace('{{COMPONENT-NAME}}', '', $content);

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
