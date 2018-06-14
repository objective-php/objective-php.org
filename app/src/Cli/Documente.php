<?php

namespace App\Cli;

use App\Manager\IndexManager;
use App\Manager\RepositoryManager;
use League\CLImate\CLImate;
use ObjectivePHP\Application\ApplicationInterface;
use ObjectivePHP\Cli\Action\AbstractCliAction;
use ObjectivePHP\Cli\Action\Parameter\Toggle;
use ObjectivePHP\ServicesFactory\Annotation\Inject;
use ObjectivePHP\ServicesFactory\Specification\InjectionAnnotationProvider;

class Documente extends AbstractCliAction implements InjectionAnnotationProvider
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

    protected $command = 'documente';
    protected $description = 'Documente Objective PHP';

    /**
     * @var CliRouter
     */
    protected $router;


    public function __construct()
    {
        $this->expects(new Toggle(['a' => 'all'], 'Redocumente all'));
        //        $this->expects(new Param(['p' => 'package'], 'Redocumente this package'));
    }

    /**
     * @param AbstractCliAction $app
     */
    public function run(ApplicationInterface $app, CLImate $c = null, $requestedCommand = null)
    {
        $c->br()->underline('<bold>Objective PHP</bold> Command Line Interface')->br();

        if ($this->getParam('all')) {
            $c->bold('Go generating the indexes files...')->br();
            $this->getIndexManager()->generateAll();
            $c->bold('Done !')->br(2);
            $c->bold('Go generating the entire documentation...')->br();
            $this->getRepositoryManager()->operateAll();
            $c->bold('Done ! All right indeed ?')->br(2);
        } else {
            $c->bold('Mhmm.. Try to use -a  ;)')->br();
        }
        $c->br();
    }

    /**
     * @return CliRouter
     */
    public function getRouter()
    {
        return $this->router;
    }

    /**
     * @param $this $router
     */
    public function setRouter(CliRouter $router)
    {
        $this->router = $router;

        return $this;
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
     * @return Documente
     */
    public function setRepositoryManager(RepositoryManager $repositoryManager): Documente
    {
        $repositoryManager->initClient();
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
     * @return Documente
     */
    public function setIndexManager(IndexManager $indexManager): Documente
    {
        $this->indexManager = $indexManager;
        return $this;
    }

}
