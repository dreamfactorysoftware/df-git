<?php

namespace DreamFactory\Core\Git\Components;

use Github\Client;
use DreamFactory\Core\Exceptions\InternalServerErrorException;
use DreamFactory\Core\Git\Contracts\ClientInterface;
use GrahamCampbell\GitHub\Authenticators\TokenAuthenticator;
use GrahamCampbell\GitHub\Authenticators\PasswordAuthenticator;

class GitHubClient implements ClientInterface
{
    /** @var \Github\Client */
    protected $client;

    /** @var string */
    protected $username;

    /**
     * GitHubClient constructor.
     *
     * @param array $config
     */
    public function __construct($config)
    {
        $this->validateConfig($config);
        $this->username = $config['vendor'];
        $username = array_get($config, 'username');
        $password = array_get($config, 'password');
        $token = array_get($config, 'token');
        $auth = null;

        if (!empty($username) && !empty($token)) {
            $auth = new TokenAuthenticator();
        } elseif (!empty($username) && !empty($password)) {
            $auth = new PasswordAuthenticator();
        }

        if (empty($auth)) {
            $this->client = new Client();
        } else {
            $this->client = $auth->with(new Client())->authenticate($config);
        }
    }

    /**
     * @param $config
     *
     * @throws \DreamFactory\Core\Exceptions\InternalServerErrorException
     */
    protected function validateConfig($config)
    {
        if (empty(array_get($config, 'vendor'))) {
            throw new InternalServerErrorException('No account/organization name provided for GitHub client.');
        }
    }

    /** {@inheritdoc} */
    public function repoAll($page = 1, $perPage = 50)
    {
        $gitUser = new GitHubUser($this->client);

        return $gitUser->repositories($this->username, 'all', 'full_name', 'asc',
            ['page' => $page, 'per_page' => $perPage]);
    }

    /** {@inheritdoc} */
    public function repoList($repo, $path = null, $ref = null)
    {
        return $this->client->repo()->contents()->show($this->username, $repo, $path, $ref);
    }

    /** {@inheritdoc} */
    public function repoGetFileInfo($repo, $path, $ref = null)
    {
        return $this->repoList($repo, $path, $ref);
    }

    /** {@inheritdoc} */
    public function repoGetFileContent($repo, $path, $ref = null)
    {
        return $this->client->repo()->contents()->download($this->username, $repo, $path, $ref);
    }
}