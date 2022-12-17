<?php

namespace DreamFactory\Core\Git\Components;

use Github\Client;
use Github\Api\User;
use DreamFactory\Core\Exceptions\InternalServerErrorException;
use DreamFactory\Core\Git\Contracts\ClientInterface;
use GrahamCampbell\GitHub\Auth\AuthenticatorFactory;
use Illuminate\Support\Arr;

class GitHubUser extends User
{
    public function repositories($username, $type = 'owner', $sort = 'full_name', $direction = 'asc', $visibility = 'all', $affiliation = 'owner,collaborator,organization_member', $extra = [])
    {
        $headers = array_merge([
            'type' => $type,
            'sort' => $sort,
            'direction' => $direction,
            'visibility' => $visibility,
            'affiliation' => $affiliation
        ], $extra);
        return $this->get('/users/'.rawurlencode($username).'/repos', $headers);
    }
}

class GitHubClient implements ClientInterface
{
    /** @var \Github\Client */
    protected $client;

    /** @var string */
    protected $username;

    /**
     * GitHubClient constructor.
     *
     * @param $config
     *
     * @throws \DreamFactory\Core\Exceptions\InternalServerErrorException
     */
    public function __construct($config)
    {
        $this->validateConfig($config);
        $this->username = $config['vendor'];
        $username = Arr::get($config, 'username');
        $password = Arr::get($config, 'password');
        $token = Arr::get($config, 'token');
        
        $authFactory = new AuthenticatorFactory();
        $auth = null;

        if (!empty($username) && !empty($token)) {
            $auth = $authFactory->make('token');
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
        if (empty(Arr::get($config, 'vendor'))) {
            throw new InternalServerErrorException('No account/organization name provided for GitHub client.');
        }
    }

    /** {@inheritdoc} */
    public function repoAll($page = 1, $perPage = 50)
    {
        $gitUser = new GitHubUser($this->client);

        return $gitUser->repositories($this->username, 'all', 'full_name', 'asc', 'all', 'owner,collaborator,organization_member',
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
        return $this->repoList($repo, rtrim($path, '/'), $ref);
    }

    /** {@inheritdoc} */
    public function repoGetFileContent($repo, $path, $ref = null)
    {
        return $this->client->repo()->contents()->download($this->username, $repo, rtrim($path, '/'), $ref);
    }
}