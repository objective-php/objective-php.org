<?php

namespace App\Manager;

use App\Config\AuthsConfig;
use App\Config\PathsConfig;
use App\Exception\ComponentStructureException;
use App\Exception\DocApiGenerationException;
use App\Exception\UnvalideHookException;
use App\Model\Package;
use App\Model\Version;
use Exception;
use League\CommonMark\Converter;
use League\CommonMark\DocParser;
use League\CommonMark\Environment;
use League\CommonMark\HtmlRenderer;
use RuntimeException;
use Symfony\Component\Finder\Finder;
use Webuni\CommonMark\TableExtension\TableExtension;

class RepositoryManager
{
    /**
     * @var Exception[]
     */
    protected $report = [];

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
     * @var DocApiGeneratorInterface
     */
    protected $docApiGenerator;

    /**
     * @param Package $package
     *
     * @throws UnvalideHookException
     */
    public function handlePing(Package $package): void
    {
        if ($this->getPackagesManager()->getPackage($package->getFullName())) {
            throw new UnvalideHookException(
                'The Package ' . $package->getFullName()
                . ' is already registered. Aborting...'
            );
        }
        $package = $this->fetchPackageVersions($package);
        foreach ($package->getVersions() as $version) {
            try {
                $repoPath = $this->fetchRepo($version->getTargz(), $package->getName());
                $this->operate($repoPath, $version->getMinor(), $package);
            } catch (Exception $exception) {
                $this->report[] = $exception;
                $package->removeVersion($version);
            }
        }
        $this->getPackagesManager()->save($package);
        $this->dataMenu($this->getPaths()['public'] . 'dist/dataMenu.js');
    }

    /**
     * @param Package $package
     * @param         $tag
     *
     * @throws RuntimeException
     * @throws ComponentStructureException
     * @throws Exception
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
     * @throws \LogicException
     *
     * @return Package
     */
    public function fetchPackageVersions(Package $package): Package
    {
        $tags = \GuzzleHttp\json_decode(
            $this->getClientsManager()
                ->getGithubClient()
                ->get(sprintf('/repos/%s/git/refs/tags', $package->getFullName()))
                ->getBody()
                ->getContents()
        );
        foreach ($tags as $tag) {
            $tag = str_replace('refs/tags/', '', $tag->ref);
            if (version_compare(ltrim($tag, 'v'), $package->getMinVersion(), '>=')) {
                try {
                    $this->addPackageVersion($package, $tag);
                } catch (Exception $exception) {
                    $this->report[] = $exception;
                }
            }
        }

        return $package;
    }

    /**
     * @param Package $package
     * @param mixed   $tag
     *
     * @return Package
     * @throws \LogicException
     */
    public function addPackageVersion(Package $package, $tag): Package
    {
        $patch = ltrim($tag, 'v');
        preg_match('/(.*\\..*)\\./', $patch, $matches);
        preg_match('/(^[0-9]+\\.[0-9]+)/', $patch, $matches);
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
     * @throws RuntimeException
     * @throws ComponentStructureException
     * @throws Exception
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
    /**
     * @param string $tarUrl
     * @param string $componentName
     *
     * @return string
     * @throws RuntimeException
     */
    public function fetchRepo(string $tarUrl, string $componentName): string
    {
        $md5 = md5($tarUrl);
        if (!copy($tarUrl, $targz = ($path = $this->getPaths()['tmp'] . $componentName . '-' . $md5) . '.tar.gz')) {
            $error = error_get_last();

            throw new \RuntimeException(sprintf('[%s] Unable to copy file : %s', $error['type'], $error['message']));
        }
        $repoPath = (new \PharData($targz))->decompress();
        unlink($targz);
        (new \PharData($tar = $path . '.tar'))->extractTo($this->getPaths()['tmp']);
        unlink($tar);

        return $repoPath;
    }

    /**
     * @param string $outputFile
     */
    public function dataMenu(string $outputFile): void
    {
        $docMenu = '<ul>';
        foreach ($packages = $this->getPackagesManager()->getDataMenu() as $compoName => $package) {
            $docMenu .= sprintf(
                '<li class="opened" ><div class="hd"><i class="fa fa-angle-right fa-lg"></i>
                    <a href="/doc/%1$s/%2$s/index.html"> %1$s </a>
                   </div><div class="bd"><ul>',
                $compoName,
                key($package)
            );
            foreach ($package as $minorVersion => $files) {
                reset($package);
                $docMenu .= sprintf(
                    '<li style="padding-left: 20px;" class="%1$s">
                        <div class="hd"><i class="fa fa-angle-right fa-lg"></i>
                            <a href="/doc/%2$s/%3$s/index.html">%3$s</a>
                        </div>
                        <div class="bd">
                            <ul>',
                    ($minorVersion === key($package) ? 'opened' : 'nojs'),
                    $compoName,
                    $minorVersion
                );
                foreach ($files as $nice => $raw) {
                    $docMenu .= sprintf(
                        '<li>    
                            <div class="hb leaf">
                                <a href="/doc/%1$s/%2$s/%3$s">%4$s</a>
                            </div>
                        </li>',
                        $compoName,
                        $minorVersion,
                        $raw,
                        $nice
                    );
                }
                $docMenu .= sprintf(
                    '        <li>
                                <div class="hb leaf">
                                    <a href="/doc/%1$s/%2$s/api/index.html">API</a>
                                </div>
                             </li>
                          </ul>
                       </div>
                    </li>',
                    $compoName,
                    $minorVersion
                );
            }
            $docMenu .= '</ul></div></li>';
        }
        $docMenu .= '</ul>';
        \file_put_contents($this->getPaths()['doc'] . 'doctree.html', $docMenu);
        $data = \json_encode($packages, JSON_PRETTY_PRINT);
        $js = 'dataMenu = ' . $data . PHP_EOL . 'md5Hash = "' . uniqid('', true) . '"';
        \file_put_contents($outputFile, $js);
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
                'trace'   => $exception->getTraceAsString(),
            ];
        }

        return $res;
    }

    /**
     * Function Operate.
     *
     * This method is the heart of the app, it generate the documentation relative to a version of a package
     *
     * @param string  $repoPath Could be application-2.0.1
     * @param string  $tag      Could be 2.0
     * @param Package $package
     *
     * @throws \App\Exception\DocApiGenerationException
     * @throws RuntimeException
     * @throws ComponentStructureException
     * @throws Exception
     */
    protected function operate(string $repoPath, string $tag, Package $package): void
    {
        if (is_dir($docsPath = $this->getPaths()['tmp'] . $repoPath . '/docs')) {
            $finder = new Finder();
            $finder->files()->in($docsPath)->name('*.md');
            $pathToDoc = $this->getPaths()['doc'] . $package->getName() . '/' . $tag . '/';
            if (!is_dir($pathToDoc) && !mkdir($pathToDoc, 0755, true) && !is_dir($pathToDoc)) {
                throw new RuntimeException(sprintf('Directory "%s" was not created', $pathToDoc));
            }
            //              FOR THE SEARCH RECORDS
            //            $docJson = [];

            $asset = json_decode(file_get_contents($this->getPaths()['public'] . 'dist/manifest.json'), true);
            $content = file_get_contents($this->getPaths()['base.twig']);
            $nice = ucfirst(str_replace('-', ' ', $package->getName()));
            $content = str_replace([
                '{% block content \'\' %}',
                '{% block title project.config(\'title\') %}',
                '{% block VERSION \'\' %}',
                '{% block COMPONENTNAME \'\' %}',
                '{{ componentrawname }}',
                '{{ githublinktext }}',
                '{{ style }}',
                '{{ app }}',
            ], [
                '<div class="markdown-body"><h1>Welcome to ' . $nice . '\'s Documentation</h1></div>',
                ucfirst($package->getName()) . ' - ' . $tag . ' | Objective PHP Documentation',
                $tag,
                $nice,
                $package->getName(),
                'This package on Github',
                $asset['theme.css'],
                $asset['app.js'],
            ], $content);
            \file_put_contents($pathToDoc . 'index.html', $content);
            foreach ($finder as $file) {
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
                    '{{ componentrawname }}',
                    '{{ githublinktext }}',
                    '{{ style }}',
                    '{{ app }}',
                ], [
                    $contents,
                    ucfirst($package->getName()) . ' - ' . $tag . ' | Objective PHP Documentation',
                    $tag,
                    ucfirst(str_replace('-', ' ', $package->getName())),
                    $package->getName(),
                    'This package on Github',
                    $asset['theme.css'],
                    $asset['app.js'],
                ], $content);
                \file_put_contents($pathToDoc . $htmlName, $content);

                if (!('index.md' === $file->getFilename())) {
                    $niceName = preg_replace('([0-9]*\.)', '', $file->getBasename('.md'), 1);
                    $niceName = str_replace(['-', '_'], ' ', $niceName);
                    $package->getVersion($tag)->addDoc([$niceName => $htmlName]);
                }
            }

            //              FOR THE SEARCH RECORDS
            //            \file_put_contents($pathToDoc . 'doc.json', \json_encode($docJson));
        } else {
            throw new ComponentStructureException('No docs folder in ' . $package->getName() . 'on tag ' . $tag . "\n");
        }

        $generationResult = $this->getDocApiGenerator()->generate(
            $repoPath,
            $package->getName(),
            $tag
        );

        if (!$generationResult) {
            throw new DocApiGenerationException(
                sprintf('Something went wrong while generating %s', $package->getName())
            );
        }

        $this->rmAll($this->getPaths()['tmp'] . '/' . $repoPath);
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
     * @param string $dir The directory to empty
     */
    protected function rmAll(string $dir): void
    {
        if (is_dir($dir)) {
            $objects = scandir($dir, SCANDIR_SORT_NONE);
            foreach ($objects as $object) {
                if ('.' !== $object && '..' !== $object) {
                    if ('dir' === filetype($dir . '/' . $object)) {
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
    public function setPaths($paths): self
    {
        $this->paths = $paths;

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
    public function setAuths(array $auths): self
    {
        $this->auths = $auths;

        return $this;
    }

    /**
     * @param $clientsManager
     *
     * @return RepositoryManager
     */
    public function setClientsManager($clientsManager): self
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
    public function setPackagesManager(PackagesManager $packagesManager): self
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
     * @return DocApiGeneratorInterface
     */
    public function getDocApiGenerator(): DocApiGeneratorInterface
    {
        return $this->docApiGenerator;
    }

    /**
     * @param DocApiGeneratorInterface $docApiGenerator
     *
     * @return RepositoryManager
     */
    public function setDocApiGenerator(DocApiGeneratorInterface $docApiGenerator): RepositoryManager
    {
        $this->docApiGenerator = $docApiGenerator;

        return $this;
    }
}
