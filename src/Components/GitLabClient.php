<?php

namespace DreamFactory\Core\Git\Components;

use DreamFactory\Core\Git\Contracts\ClientInterface;
use DreamFactory\Core\Exceptions\InternalServerErrorException;
use Gitlab\Exception\RuntimeException;

class GitLabClient implements ClientInterface
{
    /** @var \DreamFactory\Core\Git\Components\GitLabClientExtension */
    protected $client;

    /** @var array */
    protected $projectList = [];

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
        /** @var \DreamFactory\Core\Git\Components\GitLabClientExtension $this ->client */
        $this->client = new GitLabClientExtension(rtrim($config['base_url'], '/') . '/');

        $this->client->authenticate(
            $config['token'],
            array_get($config, 'method', GitLabClientExtension::AUTH_HTTP_TOKEN),
            array_get($config, 'sudo', null)
        );

        $namespace = array_get($config, 'namespace');
        if (empty($namespace)) {
            $userInfo = $this->client->users->me();
            if (empty($userInfo) || !isset($userInfo['username'])) {
                throw new InternalServerErrorException('No authenticated user found for GitLab client. Please check GitLab service configuration.');
            }
            $namespace = $userInfo['username'];
        }
        $this->namespace = $namespace;
    }

    /**
     * @param $config
     *
     * @throws \DreamFactory\Core\Exceptions\InternalServerErrorException
     */
    protected function validateConfig($config)
    {
        if (empty(array_get($config, 'base_url'))) {
            throw new InternalServerErrorException('No base url provided for GitLab client.');
        }
        if (empty(array_get($config, 'token'))) {
            throw new InternalServerErrorException('No token provided for GitLab client.');
        }
    }

    /**
     * @param int $page
     * @param int $perPage
     *
     * @return array
     */
    protected function getProjectList($page = 1, $perPage= 100)
    {
        $listRaw = $this->client->projects->accessible($page, $perPage);
        $list = [];
        foreach ($listRaw as $item) {
            if (array_get($item, 'namespace.name') === $this->namespace) {
                $list[] = $item;
            }
        }

        return $list;
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

    /** {@inheritdoc} */
    public function repoAll($page = 1, $perPage = 50)
    {
        return $this->getProjectList($page, $perPage);
    }

    /** {@inheritdoc} */
    public function repoList($repo, $path = null, $ref = null)
    {
        return $this->client->repo->tree($this->getProjectId($repo), ['path' => $path, 'ref' => $ref]);
    }

    /** {@inheritdoc} */
    public function repoGetFileInfo($repo, $path, $ref = null)
    {
        try {
            return $this->client->repo->getFile($this->getProjectId($repo), $path, $ref);
        } catch (RuntimeException $e) {
            if ($e->getCode() === 404) {
                // File not found possible a directory path. List directory.
                return $this->repoList($repo, $path, $ref);
            }
        }
    }

    /** {@inheritdoc} */
    public function repoGetFileContent($repo, $path, $ref = null)
    {
        return $this->client->repo->blob($this->getProjectId($repo), $ref, $path);
    }
}