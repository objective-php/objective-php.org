<?php

namespace Project\Action\Api;

use ObjectivePHP\Middleware\Action\RestAction\AbstractEndpoint;
use ObjectivePHP\ServicesFactory\Annotation\Inject;
use ObjectivePHP\ServicesFactory\Specification\InjectionAnnotationProvider;
use Project\Manager\RepositoryManager;
use Project\Manager\IndexManager;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Class BuildApiEndpointV1
 *
 * @package Project\Action\DocApi
 */
class BuildApiEndpointV1 extends AbstractEndpoint implements InjectionAnnotationProvider
{
    /**
     * @var RepositoryManager $repositoryManager
     * @Inject(service="manager.repository")
     */
    protected $repositoryManager;

    /**
     * @var IndexManager $indexManager
     * @Inject(service="manager.index")
     */
    protected $indexManager;


    public function get(ServerRequestInterface $request)
    {
        echo '<pre>';
        $this->getIndexManager()->generateAll();
        return $this->getRepositoryManager()->operateAll();
    }


    public function post(ServerRequestInterface $request)
    {
        if ($request->getHeader('x-github-event')[0] === 'create') { // a Branch or Tag is created.
            $body = \json_decode($request->getBody()->getContents());
            if ($body->ref_type === 'tag') {
                $tarUrl = 'https://github.com/' . $body->repository->full_name . '/archive/' . $body->ref . '.tar.gz';

                if ($repoPath = $this->getRepositoryManager()->fetchRepo($tarUrl, $body->repository->name, ltrim($body->ref, 'v'))) {
                    \preg_match("/(.*\..*)\./", \ltrim($body->ref, 'v'), $o);

                    $this->getRepositoryManager()->operate($repoPath, $body->repository->name, $o[1]);
                    $this->getRepositoryManager()->dataMenu();
                    return "Code 4xx Bro !";
                }
                throw new \Exception('Unable to fetch targz file or to decompress it');
            }
            throw new \Exception('Not a tag');
        }
        throw new \Exception('Bad hook type');

        //        //Code to build full doc-api
        //
        //$repo = GitRepository::cloneRepository('https://github.com/objective-php/starter-kit.git',tmpDir . 'starter-kit');
        //        $composerContent = json_decode(file_get_contents($tmpDir . 'starter-kit/composer.json'));
        //
        //        $mainComponents = [];
        //        foreach ($composerContent->require as $key => $value) {
        //            if (0 === strpos($key, 'objective-php')) {
        //                $mainComponents[$key] = $value;
        //            }
        //        }
        //        //TODO add others find another way to find them
        //        $components['objective-php/starter-kit'] = 'master';
        //        do {
        //            $repo = GitRepository::cloneRepository('https://github.com/'. $key .'.git',tmpDir . substr($key, 13));
        //
        //        } while (\count($components));
        //
        //        foreach ($mainComponents as $key => $value){
        //            $repo = GitRepository::cloneRepository('https://github.com/'. $key .'.git',tmpDir . substr($key, 13));
        //
        //        }
        //
        //        $versions = [];
        //        foreach ($starterKit->getTags() as $tag) {
        //            $versions[substr($tag, 1, 3)] = $tag;
        //        }
        //
        //        return $versions;
        //
        //        //        echo '<pre>';
        //        //        print_r($repo->getTags());
        //        //        echo '</pre>';
        //        //        return print_r($repo,false);
        //
        //        return 'bleu';
    }

    /**
     * @return mixed
     */
    public function getRepositoryManager()
    {
        return $this->repositoryManager;
    }

    /**
     * @param mixed $repositoryManager
     * @return BuildApiEndpointV1
     */
    public function setRepositoryManager($repositoryManager): BuildApiEndpointV1
    {
        $this->repositoryManager = $repositoryManager;
        return $this;
    }

    /**
     * @return IndexManager
     */
    public function getIndexManager(): IndexManager
    {
        return $this->indexManager;
    }

    /**
     * @param IndexManager $indexManager
     * @return BuildApiEndpointV1
     */
    public function setIndexManager(IndexManager $indexManager): BuildApiEndpointV1
    {
        $this->indexManager = $indexManager;
        return $this;
    }


}
