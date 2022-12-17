<?php

namespace DreamFactory\Core\Git\Components;

use DreamFactory\Core\Exceptions\InternalServerErrorException;
use DreamFactory\Core\Git\Contracts\ClientInterface;
use GrahamCampbell\GitLab\Auth\AuthenticatorFactory;
use Gitlab\Client;
use Gitlab\Api\Users;
use Illuminate\Support\Arr;

class CustomUsers extends Users
{
    /**
     * @param int $id
     * @param array $params
     * @return mixed
     */
    public function usersProjects($id, array $params = [])
    {
        $resolver = $this->createOptionsResolver();

        return $this->get('users/' . $this->encodePath($id) . '/projects', $resolver->resolve($params));
    }
}

class GitLabClient implements ClientInterface
{
    /** @var \GitLab\Client */
    protected $client;


    /** @var string */
    protected $namespace;

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
        $username = $this->client->users()->me()['username'];
        $params = ['page' => (int)$page, 'per_page' => (int)$perPage];

        if ($username !== $this->namespace) {
            $groupList = $this->client->groups()->projects(rawurlencode($this->namespace), $params);
            return $groupList;
        } else {
            $cu = new CustomUsers($this->client);
            $userList = $cu->usersProjects($username, $params);
            return $userList;
        }
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
            $result = $this->client->repositoryFiles()->getFile($this->getProjectId($repo), $path, $ref);
            $result['path'] = $result['file_path'];
        }

        return $result;
    }

    /** {@inheritdoc} */
    public function repoGetFileContent($repo, $path = null, $ref = null)
    {
        return $this->client->repositoryFiles()->getRawFile($this->getProjectId($repo), $path, $ref);
    }

}
