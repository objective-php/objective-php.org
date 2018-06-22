<?php

namespace App\Manager;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;

class ClientsManager
{
    /**
     * @var AuthsConfig[]
     */
    protected $auths;

    /**
     * @var ClientInterface
     */
    protected $githubClient;

    protected function createGithubClient(array $config = []): ClientsManager
    {
        $config['base_uri'] = 'https://api.github.com/repos';
        $config['query'] = [
            'client_id'     => $this->getAuths()['github-objective-php']->getClientId(),
            'client_secret' => $this->getAuths()['github-objective-php']->getClientKey()
        ];
        $this->githubClient = new Client($config);

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
     * @return ClientsManager
     */
    public function setAuths(array $auths): ClientsManager
    {
        $this->auths = $auths;
        $this->createGithubClient();

        return $this;
    }

    /**
     * @return ClientInterface
     */
    public function getGithubClient(): ClientInterface
    {
        return $this->githubClient;
    }
}
