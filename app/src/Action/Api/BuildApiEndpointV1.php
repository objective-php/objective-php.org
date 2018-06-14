<?php

namespace App\Action\Api;

use App\Manager\IndexManager;
use App\Manager\RepositoryManager;
use App\Model\Package;
use ObjectivePHP\Middleware\Action\RestAction\AbstractEndpoint;
use ObjectivePHP\ServicesFactory\Annotation\Inject;
use ObjectivePHP\ServicesFactory\Specification\InjectionAnnotationProvider;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\JsonResponse;

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

    /**
     * @param ServerRequestInterface $request
     *
     * @return bool|void|JsonResponse
     */
    public function get(ServerRequestInterface $request)
    {
        if (isset($request->getQueryParams()['whole'])) {
            $this->getRepositoryManager()->fetchWholeTags();
            return true;
        }
//        $this->getIndexManager()->generateAll();
        $this->getRepositoryManager()->operateAll();
        return new JsonResponse(['code' => 0, 'status' => 'Ok']);
    }


    /**
     * @param ServerRequestInterface $request
     * @throws \AlgoliaSearch\AlgoliaException
     * @throws \App\Exception\ComponentStructureException
     * @throws \Exception
     */
    public function post(ServerRequestInterface $request)
    {
        $log = [];
        if ($hookType = $request->getHeader('x-github-event')[0]) {
            $body = \json_decode($request->getBody()->getContents());
            switch ($hookType) {
                case 'ping':
                    if ($this->pingValidation($body)) {
                        $package = new Package(
                            $body->repository->name,
                            $body->repository->full_name,
                            array_key_exists('min-version', $request->getQueryParams()) ? $request->getQueryParams()['min-version'] : '0'
                        );//Si on suit deja le package, return false SINON Generer et tout ILU
                        $this->getRepositoryManager()->handlePing($package, true); //TODO virer force
//                        $this->getRepositoryManager()->fetchTags($package);
//                        $this->getRepositoryManager()->operateRepo($package);

                        die();
//                        print_r($package);
                        //Add to packages
                        //$this->getRepositoryManager()->OperatePackage($package);
                    } else {
                        throw new \Exception('Hook badly configurate !');
                    }
                    //  $this->getRepositoryManager()->
                    break;
                case 'create':
                    $body = \json_decode($request->getBody()->getContents());
                    if ($body->ref_type === 'tag') {
                        $tarUrl = 'https://github.com/' . $body->repository->full_name . '/archive/' . $body->ref . '.tar.gz';
                        $log[$body->repository->name] = [];
                        if ($repoPath = $this->getRepositoryManager()->fetchRepo($tarUrl, $body->repository->name, ltrim($body->ref, 'v'))) {
                            \preg_match("/(.*\..*)\./", \ltrim($body->ref, 'v'), $matches);
                            $log[$body->repository->name][] = $matches[1];
                            $this->getRepositoryManager()->operate($repoPath, $body->repository->name, $matches[1]);
                            $this->getRepositoryManager()->dataMenu();
                            return new JsonResponse(['code' => 0, 'status' => 'Ok', 'log' => $log]);
                        }
                        throw new \Exception('Unable to fetch targz file or to decompress it');
                    }
                    throw new \Exception('Not a tag');
                    break;
                case 'push':
                    break;
                default:
                    throw new \Exception('Bad hook type');
                    break;
            }
        }
        throw new \Exception('This request doesnt came from github');
    }

    /**
     * @param $body
     * @return bool
     */
    public function pingValidation($body): bool
    {
        $events = $body->hook->events;
        sort($events);

        if ($body->hook->type === 'Repository' &&
            $body->hook->active === true &&
            $events === ['create', 'push', 'release'] &&
            $body->hook->config->content_type === 'json' &&
            $body->repository->name &&
            $body->repository->full_name &&
            $body->hook->config->content_type === 'json' &&
            $body->repository->owner->login === 'louis-cuny' //TODO
        ) {
            return true;
        }
        return false;
    }

    /**
     * @return RepositoryManager
     */
    public function getRepositoryManager(): RepositoryManager
    {
        return $this->repositoryManager;
    }

    /**
     * @param RepositoryManager $repositoryManager
     * @return BuildApiEndpointV1
     */
    public function setRepositoryManager(RepositoryManager $repositoryManager): BuildApiEndpointV1
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
