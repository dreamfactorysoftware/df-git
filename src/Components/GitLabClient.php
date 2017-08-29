<?php

namespace DreamFactory\Core\Git\Components;

use Cache;
use Gitlab\Client;
use DreamFactory\Core\Git\Contracts\ClientInterface;
use DreamFactory\Core\Exceptions\InternalServerErrorException;
use Gitlab\Exception\RuntimeException;

class GitLabClient implements ClientInterface
{
    /** @var \Gitlab\Client */
    protected $client;

    protected $projectList = [];

    protected $cacheKeyPrefix;

    public function __construct($config)
    {
        $this->validateConfig($config);
        /** @var Client $this->client */
        $this->client = new Client($config['base_url']);

        $this->client->authenticate(
            $config['token'],
            array_get($config, 'method', Client::AUTH_HTTP_TOKEN),
            array_get($config, 'sudo', null)
        );

        $this->cacheKeyPrefix = md5($config['token']);
    }

    protected function validateConfig($config)
    {
        if(empty(array_get($config, 'base_url'))){
            throw new InternalServerErrorException('No base url provided for GitLab Client.');
        }
        if(empty(array_get($config, 'token'))){
            throw new InternalServerErrorException('No token provided for GitLab client.');
        }
    }

    protected function getProjectList()
    {
        $list = Cache::remember($this->cacheKeyPrefix.':ALL', config('df.default_cache_ttl'), function (){
            return $this->client->projects->owned();
        });

        return $list;
    }

    protected function getProjectId($name)
    {
        $list = $this->getProjectList();
        return array_by_key_value($list, 'name', $name, 'id');
    }

    public function repoAll()
    {
        return $this->getProjectList();
    }

    public function repoList($repo, $path = null, $ref = null)
    {
        return $this->client->repo->tree($this->getProjectId($repo), ['path' => $path, 'ref' => $ref]);
    }

    public function repoGetFileInfo($repo, $path, $ref = null)
    {
        try {
            return $this->client->repo->getFile($this->getProjectId($repo), $path, $ref);
        } catch (RuntimeException $e) {
            if($e->getCode() === 404){
                // File not found possible a directory path. List directory.
                return $this->repoList($repo, $path, $ref);
            }
        }
    }

    public function repoGetFileContent($repo, $path, $ref = null)
    {
        return $this->client->repo->blob($this->getProjectId($repo), $ref, $path);
    }
}