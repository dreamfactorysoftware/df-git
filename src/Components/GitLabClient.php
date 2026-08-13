<?php

namespace DreamFactory\Core\Git\Components;

use DreamFactory\Core\Exceptions\InternalServerErrorException;
use DreamFactory\Core\Git\Contracts\ClientInterface;
use GrahamCampbell\GitLab\Auth\AuthenticatorFactory;
use Gitlab\Client;
use Illuminate\Support\Arr;

class GitLabClient implements ClientInterface
{
    /** @var \GitLab\Client */
    protected $client;


    /** @var string */
    protected $namespace;

    /** @var int|null */
    protected $userId;

    /**
     * GitLabClient constructor.
     *
     * @param $config
     *
     * @throws \DreamFactory\Core\Exceptions\InternalServerErrorException
     */
    public function __construct($config)
    {
        $this->validateConfig($config);
        $client = new Client();
        
        $authFactory = new AuthenticatorFactory();
        $auth = $authFactory->make('token');

        $this->client = $auth->with($client)->authenticate($config);
        $this->client->setUrl(Arr::get($config, 'base_url'));

        $namespace = Arr::get($config, 'namespace');
        if (empty($namespace)) {
            $userInfo = $this->client->users()->me();
            if (empty($userInfo) || !isset($userInfo['username'])) {
                throw new InternalServerErrorException('No authenticated user found for GitLab client. Please check GitLab service configuration.');
            }
            $namespace = $userInfo['username'];
            $this->userId = isset($userInfo['id']) ? (int) $userInfo['id'] : null;
        }
        $this->namespace = $namespace;
    }

    /**
     * @param $name
     *
     * @return null
     */
    protected function getProjectId($name)
    {
        return $this->namespace . '/' . $name;
    }

    /**
     * Falls back to the project's default branch when no ref is provided.
     *
     * @param string      $repo
     * @param string|null $ref
     *
     * @return string
     */
    protected function resolveRef($repo, $ref)
    {
        if (!empty($ref)) {
            return $ref;
        }

        return Arr::get($this->client->projects()->show($this->getProjectId($repo)), 'default_branch');
    }

    /**
     * @param $config
     *
     * @throws \DreamFactory\Core\Exceptions\InternalServerErrorException
     */
    protected function validateConfig($config)
    {
        if (empty(Arr::get($config, 'base_url'))) {
            throw new InternalServerErrorException('No base url provided for GitLab client.');
        }
        if (empty(Arr::get($config, 'token'))) {
            throw new InternalServerErrorException('No token provided for GitLab client.');
        }
    }

    /** {@inheritdoc} */
    public function repoAll($page = 1, $perPage = 50)
    {
        $userInfo = $this->client->users()->me();
        $username = $userInfo['username'] ?? null;
        $params = ['page' => (int)$page, 'per_page' => (int)$perPage];

        if ($username !== $this->namespace) {
            $groupList = $this->client->groups()->projects(rawurlencode($this->namespace), $params);
            return $groupList;
        }

        $userId = $this->userId ?? (isset($userInfo['id']) ? (int) $userInfo['id'] : null);
        if ($userId === null) {
            throw new InternalServerErrorException('Unable to determine GitLab user id for projects lookup.');
        }
        return $this->client->users()->usersProjects($userId, $params);
    }

    /** {@inheritdoc} */
    public function repoList($repo, $path = null, $ref = null)
    {
        return $this->client->repositories()->tree($this->getProjectId($repo), ['path' => $path, 'ref' => $ref]);
    }

    /** {@inheritdoc} */
    public function repoGetFileInfo($repo, $path = null, $ref = null)
    {
        $result = $this->repoList($repo, $path, $ref);
        if (0 === count($result)) {
            $result = $this->client->repositoryFiles()->getFile($this->getProjectId($repo), $path, $this->resolveRef($repo, $ref));
            $result['path'] = $result['file_path'];
        }

        return $result;
    }

    /** {@inheritdoc} */
    public function repoGetFileContent($repo, $path = null, $ref = null)
    {
        return $this->client->repositoryFiles()->getRawFile($this->getProjectId($repo), $path, $this->resolveRef($repo, $ref));
    }

}
