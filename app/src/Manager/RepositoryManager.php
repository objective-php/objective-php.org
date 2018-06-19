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
     * @var Exception[]
     */
    protected $report = [];

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
     * @throws \Exception
     */
    public function handlePing(Package $package): void
    {
        if ($this->getPackagesManager()->getPackage($package->getFullName())) {
            throw new \Exception('The Package ' . $package->getFullName() . ' is allreadly registered. Aborting...');
        }
        $package = $this->fetchPackageVersions($package);
        foreach ($package->getVersions() as $version) {
            $repoPath = $this->fetchRepo($version->getTargz(), $package->getName());
            $this->operate($repoPath, $version->getMinor(), $package);
        }
        $this->getPackagesManager()->save($package);
        $this->dataMenu($this->getPaths()['public'] . 'dist/dataMenu.js');
    }

    /**
     * @param Package $package
     * @param         $tag
     *
     * @throws ComponentStructureException
     * @throws \Exception
     */
    public function handleCreate(Package $package, $tag): void
    {
        $package = $this->addPackageVersion($package, $tag);
        $version = $package->getVersion($tag);
        $repoPath = $this->fetchRepo($version->getTargz(), $package->getName());
        $this->operate($repoPath, $version->getMinor(), $package);
        $this->getPackagesManager()->save($package);
        $this->dataMenu($this->getPaths()['public'] . 'dist/dataMenu.js');
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
            $tag = str_replace('refs/tags/', '', $tag->ref);
            try {
                $this->addPackageVersion($package, $tag);
            } catch (\Exception $exception) {
                $this->report[] = $exception;
            }
        }

        return $package;
    }

    /**
     * @param Package $package
     * @param         $patch
     *
     * @return Package
     */
    public function addPackageVersion(Package $package, $tag): Package
    {
        $patch = ltrim($tag, 'v');
        preg_match("/(.*\..*)\./", $patch, $matches);
        preg_match("/(^[0-9]+\.[0-9]+)/", $patch, $matches);
        $package->addVersion(
            new Version(
                $matches[1],
                $patch,
                $tag,
                'https://github.com/' . $package->getFullName() . '/archive/' . $tag . '.tar.gz'
            )
        );

        return $package;
    }


    /**
     * @throws \RuntimeException
     * @throws ComponentStructureException
     * @throws \Exception
     */
    public function operateAll(): void
    {
        foreach ($this->getPackagesManager()->getPackages() as $package) {
            foreach ($package->getVersions() as $version) {
                $repoPath = $this->fetchRepo($version->getTargz(), $package->getName());
                $this->operate($repoPath, $version->getMinor(), $package);
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
     * Function Operate
     *
     * This method is the heart of the app, it generate the documentation relative to a version of a package
     *
     * @param string  $repoPath Could be application-2.0.1
     * @param string  $tag      Could be 2.0
     * @param Package $package
     *
     * @throws \RuntimeException
     * @throws ComponentStructureException
     * @throws \Exception
     */
    protected function operate(string $repoPath, string $tag, Package $package)
    {
        if (is_dir($docsPath = $this->getPaths()['tmp'] . $repoPath . '/docs')) {
            $finder = new Finder();
            $finder->files()->in($docsPath)->name('*.md');
            $pathToDoc = $this->getPaths()['doc'] . $package->getName() . '/' . $tag . '/';

            //              FOR THE SEARCH RECORDS
            //            $docJson = [];

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
                //                    'name'                  => ucwords($package->getName( . ' ' . $tag . ' ' . $file->getBasename('.md')),
                //                    'link'                  => '/doc/' . $package->getName( . '/' . $tag . '/' . $htmlName,
                //                    'version'               => $tag,
                //                    'component'             => $package->getName(,
                //                    'content'               => $contents,
                //                    'hierarchical_versions' => ['lvl0' => $package->getName(, 'lvl1' => $package->getName( . '>' . $tag]
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
                    ucfirst($package->getName()) . ' - ' . $tag . ' | Objective PHP Documentation',
                    $tag,
                    ucfirst($package->getName()),
                    $asset['theme.css'],
                    $asset['app.js']
                ], $content);
                \file_put_contents($pathToDoc . $htmlName, $content);

                if (!($file->getFilename() === 'index.md')) { //TODO Gerer si pas d'index.md ?
                    $niceName = preg_replace('([0-9]*\.)', '', $file->getBasename('.md'), 1);
                    $niceName = str_replace(['-', '_'], ' ', $niceName);
                    $package->getVersion($tag)->addDoc([$niceName => $htmlName]);
                }
            }

            //              FOR THE SEARCH RECORDS
            //            \file_put_contents($pathToDoc . 'doc.json', \json_encode($docJson));
        } else {
            throw new ComponentStructureException('No docs folder in ' . $package->getName() . "\n");
        }
        $json = \json_encode([
            'repoPath'  => $repoPath,
            'compoName' => $package->getName(),
            'version'   => $tag
        ]);
        \file_put_contents($this->getPaths()['tmp'] . '/infos.json', $json, JSON_PRETTY_PRINT);

        exec(
            'php ' . $this->getPaths()['public'] . '../sami.phar update -vvv ' . __DIR__ . '/sami-config.php --force',
            $output,
            $code
        );
        //                exec('php ' . $this->getPaths()['public'] . '../sami/sami.php update -v ' . __DIR__ . '/sami-config.php --force', $output, $code);

        if ($code !== 0) {
            throw new \Exception(
                sprintf('Something went wrong while generating %s (%s)', $package->getName(), print_r($output, true))
            );
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

    /**
     * @param string $outputFile
     */
    public function dataMenu(string $outputFile): void
    {
        //                  FOR NON-JS !!
        //        $docMenu = '<ul>';
        //        foreach ($this->packages as $compoName => $package) {
        //            $docMenu .= '<li class="opened" ><div class="hd"><i class="fa fa-angle-right fa-lg"></i>';
        //            $docMenu .= '<a href="/doc/' . $compoName . '/' . key($package) . '/index.html">' . $compoName . '</a>';
        //            $docMenu .= '</div><div class="bd"><ul>';
        //            foreach ($package as $minorVersion => $files) {
        //                reset($package);
        //                $docMenu .= '<li style="padding-left: 20px" class="' . ($minorVersion === key($package) ? 'opened' : 'nojs') . '"><div class="hd"><i class="fas fa-angle-right fa-lg"></i>';
        //                $docMenu .= '<a href="/doc/' . $compoName . '/' . $minorVersion . '/index.html">' . $minorVersion . '</a>';
        //                $docMenu .= '</div><div class="bd"><ul>';
        //                foreach ($files as $nice => $raw) {
        //                    $docMenu .= '<li><div class="hb leaf">';
        //                    $docMenu .= '<a href="/doc/' . $compoName . '/' . $minorVersion . '/' . $raw . '">' . $nice . '</a>';
        //                    $docMenu .= '</div></li>';
        //                }
        //                $docMenu .= '<li><div class="hb leaf"><a href="/doc/' . $compoName . '/' . $minorVersion . '/api/index.html">API</a></div></li>';
        //                $docMenu .= '</ul></div></li>';
        //            }
        //            $docMenu .= '</ul></div></li>';
        //        }
        //        $docMenu .= '<ul>';
        //        \file_put_contents($this->getPaths()['doc'] . 'doctree.html', $docMenu);
        $data = \json_encode($this->getPackagesManager()->getDataMenu(), JSON_PRETTY_PRINT);
        $js = 'dataMenu = ' . $data . PHP_EOL . 'md5Hash = "' . uniqid('', true) . '"';
        \file_put_contents($outputFile, $js);
    }


    /**
     * @param string $dir The directory to empty
     */
    protected function rmAll(string $dir): void
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

    /**
     * @return Exception[]
     */
    public function getReport(): array
    {
        return $this->report;
    }

    /**
     * @return array
     */
    public function getJsonReport(): array
    {
        $res = [];
        foreach ($this->getReport() as $exception) {
            $res[] = [
                'message' => $exception->getMessage(),
                'line'    => $exception->getLine(),
                'trace'   => $exception->getTraceAsString()
            ];
        }

        return $res;
    }
}
