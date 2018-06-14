<?php

namespace App\Manager;

use App\Config\AuthsConfig;
use App\Config\ComponentsConfig;
use App\Config\PathsConfig;
use App\Exception\ComponentStructureException;
use App\Model\Package;
use App\Model\Version;
use League\CommonMark\Converter;
use League\CommonMark\DocParser;
use League\CommonMark\Environment;
use League\CommonMark\HtmlRenderer;
use Symfony\Component\Finder\Finder;
use Webuni\CommonMark\TableExtension\TableExtension;

class RepositoryManager
{

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
     * @var ClientsManager
     */
    protected $clientsManager;

    /**
     * @var PackagesManager
     */
    protected $packagesManager;


    /**
     * @param Package $package
     *
     * @param bool    $force
     *
     * @throws \Exception
     */
    public function handlePing(Package $package, $force = false): void
    {
        if (!$force && $this->getPackagesManager()->getPackage($package->getFullName())) {
            throw new \Exception('The Package ' . $package->getFullName() . ' is allreadly registered. Aborting...');
        }
        $package = $this->fetchPackageVersions($package);
        $this->operateRepo($package);
        $this->getPackagesManager()->save($package);
    }


    /**
     * @param Package $package
     *
     * @return Package
     * @throws \LogicException
     */
    public function fetchPackageVersions(Package $package): Package
    {
        $tags = \GuzzleHttp\json_decode($this->getClientsManager()->getGithubClient()->get('/repos/' . $package->getFullName() . '/git/refs/tags')->getBody()->getContents());
        foreach ($tags as $tag) {
            try {
                $patch = ltrim(str_replace('refs/tags/', '', $tag->ref), 'v');
                preg_match("/(.*\..*)\./", $patch, $matches);
                $package->addVersion(
                    new Version(
                        $matches[1],
                        $patch,
                        'https://github.com/' . $package->getFullName() . '/archive/v' . $patch . '.tar.gz'
                    )
                );
            } catch (\Exception $e) {
                $e->getMessage();
            }
        }

        return $package;
    }


    /**
     * @return array
     */
    public function fetchTags(): array
    {
        $res = [];
        foreach ($this->components as $key => $package) {
            if ($package->getHost() === 'github') {
                $versions = [];
                $tags = \GuzzleHttp\json_decode($this->getClientsManager()->getGithubClient()->get('/repos/' . $key . '/git/refs/tags')->getBody()->getContents());
                foreach ($tags as $tag) {
                    // x.x.x
                    $v = ltrim(str_replace('refs/tags/', '', $tag->ref), 'v');
                    //On verifie que le tag est superieur a la minVersion et que ce n'est pas une version dev,alpha, etc
                    if (!preg_match('/[a-z]/i', $v)
                        && version_compare($v, $this->components[$key]->getMinVersion(), '>=')) {
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

    /**
     * @return array
     */
    public function fetchWholeTags(): array
    {
        $repos = \GuzzleHttp\json_decode($this->getClientsManager()->get->get('users/louis-cuny/repos')->getBody()->getContents());
        $repoList = [];
        foreach ($repos as $repo) {
            $tags = \GuzzleHttp\json_decode($this->githubClient->get($repo->url)->getBody()->getContents());

            print_r($tags);
            echo '</pre>';
        }

        $res = [];
        foreach ($this->components as $key => $package) {
            if ($package->getHost() === 'github') {
                $versions = [];
                // $githubAuths = $this->getAuths()['github-' . explode('/', $key)[0]];
                $tags = \GuzzleHttp\json_decode($this->githubClient->get('https://api.github.com/repos/' . $key . '/git/refs/tags')->getBody()->getContents());
                foreach ($tags as $tag) {
                    // x.x.x
                    $v = ltrim(str_replace('refs/tags/', '', $tag->ref), 'v');

                    //On verifie que le tag est superieur a la minVersion et que ce n'est pas une version dev,alpha, etc
                    if (!preg_match('/[a-z]/i', $v)
                        && version_compare($v, $this->components[$key]->getMinVersion(), '>=')) {
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

    //only works for github repos
    public function operateAll(): void
    {
        foreach ($this->fetchTags() as $key => $versions) {
            foreach ($versions as $minor => $version) {
                $this->rmAll($this->getPaths()['tmp']);
                //                $tarUrl = 'https://api.github.com/repos/louis-cuny/noitacol/tarball/v' . $version ;
                $tarUrl = 'https://github.com/' . $key . '/archive/v' . $version . '.tar.gz';
                $repoPath = $this->fetchRepo($tarUrl, $repoName = explode('/', $key)[1]);
                try {
                    $this->operate($repoPath, $repoName, $minor);
                } catch (ComponentStructureException $exception) {
                    echo $exception->getMessage();
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
            throw new \RuntimeException(sprintf('[%s] Unable to copy file : %s', $error['type'], $error['message']));
        }
        $repoPath = (new \PharData($targz))->decompress();
        (new \PharData($path . '.tar'))->extractTo($this->getPaths()['tmp']);

        return $repoPath;
    }

    /**
     * @param Package $package
     *
     * @throws \Exception
     */
    public function operateRepo(Package $package)
    {
        foreach ($package->getVersions() as $version) {
            $this->rmAll($this->getPaths()['tmp']);
            $repoPath = $this->fetchRepo($version->getTargz(), $package->getName());
            $this->operate($repoPath, $package->getName(), $version->getMinor());
        }
        $this->dataMenu($this->getPaths()['public'] . 'dist/dataMenu.js');
    }

    /**
     * @param string $repoPath      Could be application-2.0.1
     * @param string $componentName Could be application
     * @param string $tag           Could be 2.0
     *
     * @throws ComponentStructureException
     * @throws \AlgoliaSearch\AlgoliaException
     * @throws \Exception
     */
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
                $contents = '<div class="markdown-body">' . $converter->convertToHtml($file->getContents()) . '</div>';
                //              FOR THE SEARCH RECORDS
                //                $docJson[] = [
                //                    'name'                  => ucwords($componentName . ' ' . $tag . ' ' . $file->getBasename('.md')),
                //                    'link'                  => '/doc/' . $componentName . '/' . $tag . '/' . $htmlName,
                //                    'version'               => $tag,
                //                    'component'             => $componentName,
                //                    'content'               => $contents,
                //                    'hierarchical_versions' => ['lvl0' => $componentName, 'lvl1' => $componentName . '>' . $tag]
                //                ];
                $content = file_get_contents($this->getPaths()['base.twig']);
                $content = str_replace([
                    '{% block content \'\' %}',
                    '{% block title project.config(\'title\') %}',
                    '{% block VERSION \'\' %}',
                    '{% block COMPONENTNAME \'\' %}',
                    '{{ style }}',
                    '{{ app }}'
                ], [
                    $contents,
                    ucfirst($componentName) . ' - ' . $tag . ' | Objective PHP Documentation',
                    $tag,
                    ucfirst($componentName),
                    $asset['theme.css'],
                    $asset['app.js']
                ], $content);
                \file_put_contents($pathToDoc . $htmlName, $content);

                if (!($file->getFilename() === 'index.md')) { //TODO Gerer si pas d'index.md ?
                    $niceName = preg_replace('([0-9]*\.)', '', $file->getBasename('.md'), 1);
                    $niceName = str_replace(['-', '_'], ' ', $niceName);
                    $this->packages[$componentName][$tag][$niceName] = $htmlName;
                }
            }
            //              FOR THE SEARCH RECORDS
            //            \file_put_contents($pathToDoc . 'doc.json', \json_encode($docJson));
        } else {
            throw new ComponentStructureException('No docs folder in ' . $componentName . "\n");
        }
        $json = \json_encode([
            'repoPath'  => $repoPath,
            'compoName' => $componentName,
            'version'   => $tag
        ]);
        \file_put_contents($this->getPaths()['tmp'] . '/infos.json', $json, JSON_PRETTY_PRINT);

        exec('php ' . $this->getPaths()['public'] . '../sami.phar update -vvv ' . __DIR__ . '/sami-config.php --force',
            $output, $code);
        //                exec('php ' . $this->getPaths()['public'] . '../sami/sami.php update -v ' . __DIR__ . '/sami-config.php --force', $output, $code);

        if ($code != 0) {
            throw new \Exception(sprintf('Something went wrong while generating %s (%s)', $componentName,
                print_r($output, true)));
        }
        //              FOR THE SEARCH RECORDS
        //            $algoliaAuths = $this->getAuths()['algolia-louis-cuny'];
        //            $client = new \AlgoliaSearch\Client($algoliaAuths->getClientId(), $algoliaAuths->getClientKey());
        //            $index = $client->initIndex('objective_php_api');
        //            $objects = json_decode(file_get_contents($pathToDoc . '/api/sami.json'), false);
        //
        //            $index2 = $client->initIndex('objective_php_doc');
        //            $objects2 = json_decode(file_get_contents($pathToDoc . 'doc.json'), false);
        //            print_r($objects);
        //            print_r($objects2);
        //            //                $index->addObjects($objects);
        //            //                $index2->addObjects($objects2);
    }

    public function dataMenu(string $outputFile): void
    {
        $docMenu = '<ul>';
        foreach ($this->packages as $compoName => $package) {
            $docMenu .= '<li class="opened" ><div class="hd"><i class="fa fa-angle-right fa-lg"></i>';
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
     *
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
     *
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
     *
     * @return RepositoryManager
     */
    public function setAuths(array $auths): RepositoryManager
    {
        $this->auths = $auths;

        return $this;
    }

    /**
     * @param $clientsManager
     *
     * @return RepositoryManager
     */
    public function setClientsManager($clientsManager): RepositoryManager
    {
        $this->clientsManager = $clientsManager;

        return $this;
    }

    /**
     * @return ClientsManager
     */
    public function getClientsManager(): ClientsManager
    {
        return $this->clientsManager;
    }

    /**
     * @param PackagesManager $packagesManager
     *
     * @return RepositoryManager
     */
    public function setPackagesManager(PackagesManager $packagesManager): RepositoryManager
    {
        $this->packagesManager = $packagesManager;

        return $this;
    }

    /**
     * @return PackagesManager
     */
    public function getPackagesManager(): PackagesManager
    {
        return $this->packagesManager;
    }


}
