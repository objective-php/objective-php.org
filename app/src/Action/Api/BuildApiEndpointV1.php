<?php

namespace App\Action\Api;

use App\Exception\UnvalideHookException;
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
     * @throws \InvalidArgumentException
     */
    public function get(ServerRequestInterface $request): JsonResponse
    {
        try {
            $this->getRepositoryManager()->operateAll();
        } catch (\Exception $exception) {
            return new JsonResponse([
                'code'           => 1,
                'status'         => 'Not ok',
                'log'            => $this->getRepositoryManager()->getJsonReport(),
                'main exception' => [
                    'message' => $exception->getMessage(),
                    'line'    => $exception->getLine(),
                    'trace'   => $exception->getTraceAsString()
                ]
            ], 400);
        }

        return new JsonResponse([
            'code'   => 0,
            'status' => 'Ok',
            'log'    => $this->getRepositoryManager()->getJsonReport()
        ]);
    }

    /**
     * @param ServerRequestInterface $request
     *
     * @return JsonResponse
     * @throws \InvalidArgumentException
     */
    public function post(ServerRequestInterface $request): JsonResponse
    {
        try {
            if (!$hookType = $request->getHeader('x-github-event')[0]) {
                throw new UnvalideHookException('This request doesnt came from github');
            }
            $body = \json_decode($request->getBody()->getContents());
            switch ($hookType) {
                case 'ping':
                    if (!$this->pingValidation($body)) {
                        throw new UnvalideHookException('Hook badly configurate !');
                    }
                    $package = new Package(
                        $body->repository->name,
                        $body->repository->full_name,
                        array_key_exists(
                            'min-version',
                            $request->getQueryParams()
                        ) ? $request->getQueryParams()['min-version'] : '0'
                    );
                    $this->getRepositoryManager()->handlePing($package);
                    break;
                case 'create':
                    if (!$package = $this->createValidation($body)) {
                        throw new UnvalideHookException('The hook isnt a tag hook or the package is not register');
                    }
                    $this->getRepositoryManager()->handleCreate($package, $body->ref);
                    break;
                default:
                    throw new UnvalideHookException('Bad hook type');
                    break;
            }
        } catch (\Exception $exception) {
            return new JsonResponse([
                'code'           => 1,
                'status'         => 'Not ok',
                'log'            => $this->getRepositoryManager()->getJsonReport(),
                'main exception' => [
                    'message' => $exception->getMessage(),
                    'line'    => $exception->getLine(),
                    'trace'   => $exception->getTraceAsString()
                ]
            ], 400);
        }

        return new JsonResponse([
            'code'   => 0,
            'status' => 'Ok',
            'log'    => $this->getRepositoryManager()->getJsonReport()
        ]);
    }

    public function createValidation($body): ?Package
    {
        if ($body->ref_type === 'tag' &&
            $package = $this->getRepositoryManager()->getPackagesManager()->getPackage($body->repository->full_name)
        ) {
            return $package;
        }

        return null;
    }

    /**
     * @param $body
     *
     * @return bool
     */
    public function pingValidation($body): bool
    {
        $events = $body->hook->events;
        sort($events);

        if ($body->hook->type === 'Repository' &&
            $body->hook->active === true &&
            $body->hook->config->content_type === 'json' &&
            $body->repository->name &&
            $body->repository->full_name &&
            $body->hook->config->content_type === 'json' &&
            \in_array('create', $events, true)
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
     *
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
     *
     * @return BuildApiEndpointV1
     */
    public function setIndexManager(
        IndexManager $indexManager
    ): BuildApiEndpointV1 {
        $this->indexManager = $indexManager;

        return $this;
    }
}
