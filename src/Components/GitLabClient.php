<?php

namespace DreamFactory\Core\Git\Components;

use DreamFactory\Core\Exceptions\InternalServerErrorException;
use DreamFactory\Core\Git\Contracts\ClientInterface;
use GrahamCampbell\GitLab\Authenticators\GitLabAuthenticator;
use Gitlab\Client;
use Gitlab\ResultPager;
use Gitlab\Api\Users;
use mysql_xdevapi\Result;

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
        $auth = new GitLabAuthenticator('http_token');
        $this->client = $auth->with($client)->authenticate($config);
        $this->client->setUrl(array_get($config, 'base_url'));
        $namespace = array_get($config, 'namespace');
        if (empty($namespace)) {
            $userInfo = $this->client->api('users')->me();
            if (empty($userInfo) || !isset($userInfo['username'])) {
                throw new InternalServerErrorException('No authenticated user found for GitLab client. Please check GitLab service configuration.');
            }
            $namespace = $userInfo['username'];
        }
        $this->namespace = $namespace;
        return $config;
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
        if (empty(array_get($config, 'base_url'))) {
            throw new InternalServerErrorException('No base url provided for GitLab client.');
        }
        if (empty(array_get($config, 'token'))) {
            throw new InternalServerErrorException('No token provided for GitLab client.');
        }
    }

    public function repoAll($page = 1, $perPage = 50)
    {
        $listRaw = $this->client->projects()->all([ 'page' => $page , 'per_page' => $perPage ]);
        $list = [];
        foreach ($listRaw as $item) {
            if (array_get($item, 'namespace.name') === $this->namespace) {
                $list[] = $item;
            }
        }

        return $list;
    }

    public function repoList($repo, $path = null, $ref = null)
    {
        return $this->client->repositories()->tree($this->getProjectId($repo), [ 'path' => $path, 'ref' => $ref ]);
//        return $this->client->repo->tree($this->getProjectId($repo), ['path' => $path, 'ref' => $ref]);
    }

    public function repoGetFileInfo($repo, $path = null, $ref = null)
    {
        $result = $this->repoList($repo, $path, $ref);
        if(0 === count($result)){
            $result = $this->client->repositories()->getFile($this->getProjectId($repo), $path, $ref);
        }
        return $result;
        /*try {
            return $this->client->repo->getFile($this->getProjectId($repo), $path, $ref);
        } catch (RuntimeException $e) {
            if ($e->getCode() === 404) {
                // File not found possible a directory path. List directory.
                return $this->repoList($repo, $path, $ref);
            }
        }*/
    }

    public function repoGetFileContent($repo, $path = null, $ref = null)
    {
        return $this->client->repositories()->blob($this->getProjectId($repo), $ref, $path);
//        return $this->client->repositoryFiles()->getRawFile($this->getProjectId($repo), $path, $ref);
    }

}
