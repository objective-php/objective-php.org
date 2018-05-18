<?php

namespace Project\Manager;

use GuzzleHttp\Client;
use League\CommonMark\Converter;
use League\CommonMark\DocParser;
use League\CommonMark\Environment;
use League\CommonMark\HtmlRenderer;
use Project\Exception\ComponentStructureException;
use Symfony\Component\Finder\Finder;
use Webuni\CommonMark\TableExtension\TableExtension;

class RepositoryManager
{
    public const TMP_DIR = __DIR__ . '/../../tmp/';
    public const PUBLIC_DIR = __DIR__ . '/../../../public/';
    public const HTML_DIR = __DIR__ . '/../../layouts/html/';

    public $githubAuth = 'client_id=2b90f3380f225b1e4ead&client_secret=2d5b1cc12a0bdda9a3e8d50b1bc08b543f2751bc';

    protected $components;

    protected $packages;

    /**
     * RepositoryManager constructor.
     */
    public function __construct()
    {
        $this->packages = \json_decode(\file_get_contents(__DIR__ . '/packages.json'), true);
        $this->components = \json_decode(\file_get_contents(__DIR__ . '/components.json'))->components;
    }


    public function fetchTags(): array
    {
        $client = new Client();
        $res = [];
        foreach ($this->components as $key => $package) {
            if ($package->host === 'github') {
                $versions = [];
                $tags = \GuzzleHttp\json_decode($client->get('https://api.github.com/repos/' . $key . '/git/refs/tags?' . $this->githubAuth)->getBody()->getContents());
                foreach ($tags as $tag) {
                    // x.x.x
                    $v = \ltrim(\str_replace('refs/tags/', '', $tag->ref), 'v');

                    //On verifie que le tag est superieur a la minVersion et que ce n'est pas une version dev,alpha, etc
                    if (!\preg_match('/[a-z]/i', $v) && \version_compare($v, $this->components->$key->minVersion, '>=')) {
                        \preg_match("/(.*\..*)\./", $v, $o);
                        $versions[$o[1]] = $v;
                    }
                }
                $res[$key] = $versions;
            }
            \krsort($res[$key]);
        }
        return $res;
    }

    public function operateAll() : void
    { //only works for github repos
        $this->packages = [];
        foreach ($this->fetchTags() as $key => $versions) {
            foreach ($versions as $minor => $version) {
                $this->rmAll(self::TMP_DIR);
                $tarUrl = 'https://github.com/' . $key . '/archive/v' . $version . '.tar.gz';
                $repoPath = $this->fetchRepo($tarUrl, $repoName = \explode('/', $key)[1], $version);
                try {
                    $this->operate($repoPath, $repoName, $minor);
                } catch (ComponentStructureException $e) {
                    echo $e->getMessage();
                }
            }
        }
        $this->dataMenu();
    }

    //https://github.com/louis-cuny/application/archive/v2.0.1.tar.gz
    //application
    //2.0.0
    public function fetchRepo(string $tarUrl, string $componentName, string $version): string
    {
        $this->rmAll(self::TMP_DIR);
        \copy($tarUrl, $targz = ($path = self::TMP_DIR . $componentName . '-' . $version) . '.tar.gz');
        $repoPath = (new \PharData($targz))->decompress();
        (new \PharData($path . '.tar'))->extractTo(self::TMP_DIR);
        return $repoPath;
    }

    //application-2.0.1
    //application
    //2.0
    public function operate(string $repoPath, string $componentName, string $tag)
    {
        if (\is_dir($docsPath = self::TMP_DIR . $repoPath . '/docs')) {
            $finder = new Finder();
            $finder->files()->in($docsPath)->name('*.md');

            $pathToDocs = self::PUBLIC_DIR . 'doc/';

            foreach ($finder as $file) {
                if (!\is_dir($pathToDoc = $pathToDocs . $componentName . '/' . $tag . '/') && !\mkdir($pathToDoc, 0755, true) && !\is_dir($pathToDoc)) {
                    throw new \RuntimeException(sprintf('Directory "%s" was not created', $pathToDoc));
                }
                \file_put_contents($pathToDoc . $htmlName = $file->getBasename('.md') . '.html', $this->renderDoc($file->getContents(), ucfirst($componentName),  $tag));
                if (!($file->getFilename() === 'index.md')) {
                    $niceName = preg_replace('([0-9]*\.)', '', $file->getBasename('.md'), 1);
                    $niceName = str_replace(['-', '_'], ' ', $niceName);
                    $this->packages[$componentName][$tag][$niceName] = $htmlName;
                }
            }
        } else {
            throw new ComponentStructureException('No docs folder in ' . $componentName . "\n");
        }
        $json = \json_encode(['repoPath'  => $repoPath,
                              'compoName' => $componentName,
                              'version'   => $tag]);
        \file_put_contents(self::TMP_DIR . '/infos.json', $json, JSON_PRETTY_PRINT);

        return print_r(\exec('php ' . __DIR__ . '/sami.phar update -v ' . __DIR__ . '/sami-config.php --force'));
    }

    public function renderDoc(string $pageContent, $title, $version): string
    {
        $content = file_get_contents(self::HTML_DIR . 'doclayout.html');
        $environment = Environment::createCommonMarkEnvironment();
        $environment->addExtension(new TableExtension());
        //$environment->addBlockRenderer(Table::class, new OpTableRenderer());
        $converter = new Converter(new DocParser($environment), new HtmlRenderer($environment));
        $content = str_replace('{{PAGE-CONTENT}}', $converter->convertToHtml($pageContent), $content);
        $content = str_replace('{{TITLE}}', $title . ' - ' . $version . ' | Objective PHP Documentation', $content);
        $content = str_replace('{{VERSION}}', $version, $content);
        $content = str_replace('{{COMPONENT-NAME}}', $title, $content);

        return $content;
    }

    public function dataMenu(string $outputFile = self::PUBLIC_DIR . 'assets/dataMenu.js'): void
    {
        $docMenu = '<ul>';
        foreach ($this->packages as $compoName => $package) {
            $docMenu .= '<li class="opened" ><div class="hd"><i class="fas fa-angle-right fa-lg"></i>';
            $docMenu .= '<a href="/doc/' . $compoName . '/' . key($package) . '/index.html">' . $compoName . '</a>';
            $docMenu .= '</div><div class="bd"><ul>';
            foreach ($package as $minorVersion => $files) {
                reset($package);
                $docMenu .= '<li style="padding-left: 20px" class="' . ($minorVersion === key($package) ? 'opened' : 'nojs') . '"><div class="hd"><i class="fas fa-angle-right fa-lg"></i>';
                $docMenu .= '<a href="/doc/' . $compoName . '/' . $minorVersion . '/index.html">' . $minorVersion . '</a>';
                $docMenu .= '</div><div class="bd"><ul>';
                foreach ($files as $nice => $raw) {
                    $docMenu .= '<li><div class="hb leaf">';
                    $docMenu .= '<a href="/doc/' . $compoName . '/' . $minorVersion . '/' . $raw . '">' . $nice . '</a>';
                    $docMenu .= '</div></li>';
                }
                $docMenu .= '</ul></div></li>';
            }
            $docMenu .= '</ul></div></li>';
        }
        $docMenu .= '<ul>';
        \file_put_contents(self::PUBLIC_DIR . 'doctree.html', $docMenu);

        $js = 'dataMenu = ' . $json = \json_encode($this->packages, JSON_PRETTY_PRINT);
        \file_put_contents($outputFile, $js);
        \file_put_contents(__DIR__ . '/packages.json', $json);
    }

    /**
     * @param string $dir The directory to empty
     */
    public function rmAll(string $dir): void
    {
        if (\is_dir($dir)) {
            $objects = \scandir($dir, SCANDIR_SORT_NONE);
            foreach ($objects as $object) {
                if ($object !== '.' && $object !== '..') {
                    if (\filetype($dir . '/' . $object) === 'dir') {
                        $this->rmAll($dir . '/' . $object);
                    } else {
                        \unlink($dir . '/' . $object);
                    }
                }
            }
            \reset($objects);
            if (self::TMP_DIR !== $dir) {
                \rmdir($dir);
            }
        }
    }
}
