<?php

namespace App\Action\Api;

use App\Manager\IndexManager;
use App\Manager\RepositoryManager;
use ObjectivePHP\Middleware\Action\RestAction\AbstractEndpoint;
use ObjectivePHP\ServicesFactory\Annotation\Inject;
use ObjectivePHP\ServicesFactory\Specification\InjectionAnnotationProvider;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Class BuildApiEndpointV1
 *
 * @package App\Action\DocApi
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
                    return "It worked";
                }
                throw new \Exception('Unable to fetch targz file or to decompress it');
            }
            throw new \Exception('Not a tag');
        }
        throw new \Exception('Bad hook type');
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
