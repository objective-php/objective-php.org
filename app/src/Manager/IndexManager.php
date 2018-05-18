<?php

namespace Project\Manager;

use ObjectivePHP\Primitives\String\Camel;
use Symfony\Component\Finder\Finder;

class IndexManager
{
    public const DOC_DIR = __DIR__ . '/../../../public/doc/';
    public const DOCAPI_DIR = __DIR__ . '/../../../public/docapi/';
    public const HTML_DIR = __DIR__ . '/../../layouts/html/';

    public $base_url;

    /**
     * IndexManager constructor.
     */
    public function __construct()
    {
        $this->base_url = 'http://' . $_SERVER['HTTP_HOST'];
    }


    public function generateAll(): void
    {
        file_put_contents(__DIR__ . '/../../../public/doc/index.html', $this->docIndex());
    }

    public function docIndex(): string
    {
        $content = file_get_contents(self::HTML_DIR . 'doclayout.html');
        $pageContent = '<h1>Welcome to Objective PHP user documentation</h1><br/><br/>
<p>You can navigate through it from the left menu.</p><br/>
<p>Please take attention on the versions you are using</p>';
        $content = str_replace('{{PAGE-CONTENT}}', $pageContent, $content);
        $content = str_replace('{{TITLE}}', 'Objective PHP Documentation', $content);
        $content = str_replace('{{VERSION}}', '', $content);
        $content = str_replace('{{COMPONENT-NAME}}', '', $content);

        return $content;
    }

}
