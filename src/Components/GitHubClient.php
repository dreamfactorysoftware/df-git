<?php

namespace DreamFactory\Core\Git\Components;

use DreamFactory\Core\Exceptions\InternalServerErrorException;
use Github\Client;
use DreamFactory\Core\Git\Contracts\ClientInterface;
use GrahamCampbell\GitHub\Authenticators\TokenAuthenticator;
use GrahamCampbell\GitHub\Authenticators\PasswordAuthenticator;

class GitHubClient implements ClientInterface
{
    /** @var \Github\Client */
    protected $client;

    protected $username;

    public function __construct($config)
    {
        $this->validateConfig($config);
        $this->setUsername($config['username']);
        if(!empty(array_get($config, 'token'))){
            $auth = new TokenAuthenticator();
        } else {
            $auth = new PasswordAuthenticator();
        }

        $this->client = $auth->with(new Client())->authenticate($config);
    }

    public function setUsername($username)
    {
        $this->username = $username;
    }

    protected function validateConfig($config)
    {
        if(empty(array_get($config, 'username'))){
            throw new InternalServerErrorException('No username provided for GitHub client.');
        }
        if(empty(array_get($config, 'token')) && empty(array_get($config, 'password'))){
            throw new InternalServerErrorException('No token or password for GitHub client.');
        }
    }

    public function repoAll()
    {
        return $this->client->user()->repositories($this->username);
    }

    public function repoList($repo, $path = null, $ref = null)
    {
        return $this->client->repo()->contents()->show($this->username, $repo, $path, $ref);
    }

    public function repoGetFileInfo($repo, $path, $ref = null)
    {
        return $this->repoList($repo, $path, $ref);
    }

    public function repoGetFileContent($repo, $path, $ref = null)
    {
        return $this->client->repo()->contents()->download($this->username, $repo, $path, $ref);
    }
}