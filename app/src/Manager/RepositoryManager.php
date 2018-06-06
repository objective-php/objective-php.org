<?php

namespace App\Manager;

use App\Config\AuthsConfig;
use App\Config\ComponentsConfig;
use App\Config\PathsConfig;
use GuzzleHttp\Client;
use League\CommonMark\Converter;
use League\CommonMark\DocParser;
use League\CommonMark\Environment;
use League\CommonMark\HtmlRenderer;
use App\Exception\ComponentStructureException;
use Symfony\Component\Finder\Finder;
use Webuni\CommonMark\TableExtension\TableExtension;

class RepositoryManager
{

    protected $packages;

    /**
     * @var ComponentsConfig[]
     */
    protected $components;

    /**
     * @var PathsConfig[]
     */
    protected $paths;

    /**
     * @var AuthsConfig[]
     */
    protected $auths;

    /**
     * RepositoryManager constructor.
     */
    public function __construct()
    {
        try {
            $this->packages = \json_decode(file_get_contents(__DIR__ . '/../../data/packages.json'), true);
        } catch (\Exception $e) {
            if (!is_dir(__DIR__ . '/../../data/packages.json') && !mkdir(__DIR__ . '/../../data/packages.json', 0755, true) && !is_dir(__DIR__ . '/../../data/packages.json')) {
                throw new \RuntimeException(sprintf('Directory "%s" was not created', __DIR__ . '/../../data/packages.json'));
            }
        }
    }


    public function fetchTags(): array
    {
        $client = new Client();
        $res = [];

        foreach ($this->components as $key => $package) {
            if ($package->getHost() === 'github') {
                $versions = [];
                $githubAuths = $this->getAuths()['github-' . explode('/', $key)[0]];
                $tags = \GuzzleHttp\json_decode($client->get('https://api.github.com/repos/' . $key . '/git/refs/tags?client_id=' . $githubAuths->getClientId() . '&client_secret=' . $githubAuths->getClientKey())->getBody()->getContents());
                foreach ($tags as $tag) {
                    // x.x.x
                    $v = ltrim(str_replace('refs/tags/', '', $tag->ref), 'v');

                    //On verifie que le tag est superieur a la minVersion et que ce n'est pas une version dev,alpha, etc
                    if (!preg_match('/[a-z]/i', $v) && version_compare($v, $this->components[$key]->getMinVersion(), '>=')) {
                        preg_match("/(.*\..*)\./", $v, $o);
                        $versions[$o[1]] = $v;
                    }
                }
                $res[$key] = $versions;
            }
            krsort($res[$key]);
        }
        return $res;
    }

    public function operateAll(): void
    { //only works for github repos

        $this->packages = [];
        foreach ($this->fetchTags() as $key => $versions) {
            foreach ($versions as $minor => $version) {
                $this->rmAll($this->getPaths()['tmp']);
                $tarUrl = 'https://github.com/' . $key . '/archive/v' . $version . '.tar.gz';
                $repoPath = $this->fetchRepo($tarUrl, $repoName = explode('/', $key)[1]);
                try {
                    $this->operate($repoPath, $repoName, $minor);
                } catch (ComponentStructureException $e) {
                    echo $e->getMessage();
                }
            }
        }
        $this->dataMenu($this->getPaths()['public'] . 'dist/dataMenu.js');
    }

    //https://github.com/louis-cuny/application/archive/v2.0.1.tar.gz
    //application
    //2.0.0
    public function fetchRepo(string $tarUrl, string $componentName): string
    {
        $this->rmAll($this->getPaths()['tmp']);
        $md5 = md5($tarUrl);
        if (!copy($tarUrl, $targz = ($path = $this->getPaths()['tmp'] . $componentName . '-' . $md5) . '.tar.gz')) {
            $error = error_get_last();
            throw new \RuntimeException(
                sprintf('[%s] Unable to copy file : %s', $error['type'], $error['message'])
            );
        }
        $repoPath = (new \PharData($targz))->decompress();
        (new \PharData($path . '.tar'))->extractTo($this->getPaths()['tmp']);
        return $repoPath;
    }

    //application-2.0.1
    //application
    //2.0
    public function operate(string $repoPath, string $componentName, string $tag)
    {
        if (is_dir($docsPath = $this->getPaths()['tmp'] . $repoPath . '/docs')) {
            $finder = new Finder();
            $finder->files()->in($docsPath)->name('*.md');
            $pathToDoc = $this->getPaths()['doc'] . $componentName . '/' . $tag . '/';
            $docJson = [];

            $asset = json_decode(file_get_contents($this->getPaths()['public'] . 'dist/manifest.json'), true);

            foreach ($finder as $file) {
                if (!is_dir($pathToDoc) && !mkdir($pathToDoc, 0755, true) && !is_dir($pathToDoc)) {
                    throw new \RuntimeException(sprintf('Directory "%s" was not created', $pathToDoc));
                }
                $htmlName = $file->getBasename('.md') . '.html';

                $environment = Environment::createCommonMarkEnvironment();
                $environment->addExtension(new TableExtension());
                $converter = new Converter(new DocParser($environment), new HtmlRenderer($environment));
                $contents = $converter->convertToHtml($file->getContents());
                $docJson[] = [
                    'name'                  => ucwords($componentName . ' ' . $tag . ' ' . $file->getBasename('.md')),
                    'link'                  => '/doc/' . $componentName . '/' . $tag . '/' . $htmlName,
                    'version'               => $tag,
                    'component'             => $componentName,
                    'content'               => $contents,
                    'hierarchical_versions' => ['lvl0' => $componentName, 'lvl1' => $componentName . '>' . $tag]
                ];
                $content = file_get_contents($this->getPaths()['html'] . 'doclayout.html');
                $content = str_replace('{{PAGE-CONTENT}}', $contents, $content);
                $content = str_replace('{{TITLE}}', ucfirst($componentName) . ' - ' . $tag . ' | Objective PHP Documentation', $content);
                $content = str_replace('{{VERSION}}', $tag, $content);
                $content = str_replace('{{COMPONENT-NAME}}', ucfirst($componentName), $content);
                $content = str_replace('{{STYLE}}', $asset['theme.css'], $content);
                $content = str_replace('{{SCRIPT}}', $asset['app.js'], $content);
                \file_put_contents($pathToDoc . $htmlName, $content);

                if (!($file->getFilename() === 'index.md')) { //TODO Gerer si pas d'index.md
                    $niceName = preg_replace('([0-9]*\.)', '', $file->getBasename('.md'), 1);
                    $niceName = str_replace(['-', '_'], ' ', $niceName);
                    $this->packages[$componentName][$tag][$niceName] = $htmlName;
                }
            }
            \file_put_contents($pathToDoc . 'doc.json', \json_encode($docJson));
        } else {
            throw new ComponentStructureException('No docs folder in ' . $componentName . "\n");
        }
        $json = \json_encode(['repoPath'  => $repoPath,
                              'compoName' => $componentName,
                              'version'   => $tag]);
        \file_put_contents($this->getPaths()['tmp'] . '/infos.json', $json, JSON_PRETTY_PRINT);

        exec('php ' . $this->getPaths()['public'] . '../sami/sami.phar update -vvv ' . __DIR__ . '/sami-config.php --force', $output, $code);
//        exec('php ' . $this->getPaths()['public'] . '../sami/sami/sami.php update -v ' . __DIR__ . '/sami-config.php --force', $output, $code);

        if ($code != 0) {
            throw new \Exception('Something went wrong while generating ' . $componentName);
        }

        if (false) {
            $algoliaAuths = $this->getAuths()['algolia-louis-cuny'];
            $client = new \AlgoliaSearch\Client($algoliaAuths->getClientId(), $algoliaAuths->getClientKey());
            $index = $client->initIndex('objective_php_api');
            $objects = json_decode(file_get_contents($pathToDoc . '/api/sami.json'), false);

            $index2 = $client->initIndex('objective_php_doc');
            $objects2 = json_decode(file_get_contents($pathToDoc . 'doc.json'), false);
            print_r($objects);
            print_r($objects2);
            //                $index->addObjects($objects);
            //                $index2->addObjects($objects2);
        }
    }


    public function dataMenu(string $outputFile): void
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
                ////////////   API   //////////
                //                $path =self::PUBLIC_DIR . 'doc/' . $compoName . '/' . $minorVersion . '/api/';
                //                $docMenu .= $this->menuApi($path);
                $docMenu .= '<li><div class="hb leaf"><a href="/doc/' . $compoName . '/' . $minorVersion . '/api/index.html">API</a></div></li>';
                $docMenu .= '</ul></div></li>';
            }
            $docMenu .= '</ul></div></li>';
        }
        $docMenu .= '<ul>';
        \file_put_contents($this->getPaths()['doc'] . 'doctree.html', $docMenu);

        $js = 'dataMenu = ' . $json = \json_encode($this->packages, JSON_PRETTY_PRINT);
        \file_put_contents($outputFile, $js);
        \file_put_contents($this->getPaths()['app'] . 'data/packages.json', $json);
    }

    public function menuApi(string $path): string
    {
        $finder = new Finder();
        $res = '<ul>';
        foreach ($finder->in($path)->directories()->depth('== 0') as $element) {
            $res .= '<li class="nojs">
                    <div class="hd"><i class="fas fa-angle-right fa-lg"></i>
                        <a href="link bleu">' . $element->getFilename() . '</a>
                    </div>
                    <div class="bd">';
            $res .= $this->menuApi($element->getPathname());
            $res .= '</div>
                </li>';
        }
        foreach ($finder->files()->name('*.html') as $element) {
            $res .= ' <li>
                    <div class="hd leaf">
                        <a href="link bleu">' . $element->getFilename() . '</a>
                    </div>
                </li>';
        }

        $res .= '</ul>';

        return $res;
    }

    /**
     * @param string $dir The directory to empty
     */
    public function rmAll(string $dir): void
    {
        if (is_dir($dir)) {
            $objects = scandir($dir, SCANDIR_SORT_NONE);
            foreach ($objects as $object) {
                if ($object !== '.' && $object !== '..') {
                    if (filetype($dir . '/' . $object) === 'dir') {
                        $this->rmAll($dir . '/' . $object);
                    } else {
                        unlink($dir . '/' . $object);
                    }
                }
            }
            reset($objects);
            if ($this->getPaths()['tmp'] !== $dir) {
                rmdir($dir);
            }
        }
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
     * @return $this
     */
    public function setPaths($paths): RepositoryManager
    {
        $this->paths = $paths;

        return $this;
    }

    /**
     * @return ComponentsConfig[]
     */
    public function getComponents(): array
    {
        return $this->components;
    }

    /**
     * @param array $components
     * @return RepositoryManager
     */
    public function setComponents($components): RepositoryManager
    {
        $this->components = $components;

        return $this;
    }

    /**
     * @return AuthsConfig[]
     */
    public function getAuths(): array
    {
        return $this->auths;
    }

    /**
     * @param AuthsConfig[] $auths
     * @return RepositoryManager
     */
    public function setAuths(array $auths): RepositoryManager
    {
        $this->auths = $auths;
        return $this;
    }


}
