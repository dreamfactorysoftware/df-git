<?php

namespace DreamFactory\Core\Git\Components;

use DreamFactory\Core\Git\Contracts\ClientInterface as GitClientInterface;
use DreamFactory\Core\Exceptions\InternalServerErrorException;
use Bitbucket\Client;
use Bitbucket\ResultPager;
use Bitbucket\Api\Repositories\Workspaces;
use Bitbucket\Api\Repositories\Workspaces\Src;
use GrahamCampbell\Bitbucket\Auth\AuthenticatorFactory;
use Illuminate\Support\Arr;

/*
Method 'listPath' is similar to 'show' method from library, 
but it gives us full control over $params variable,
so we do not establish ['format' => 'meta'] into it 
*/
class CustomSrc extends Src
{
    public function listPath($ref, $path, $params = [])
    {
        return $this->get($this->buildSrcUri($ref, $path), $params);
    }
}

class BitbucketClient implements GitClientInterface
{
    /** @var \Bitbucket\Client */
    protected $client;

    /** @var String */
    protected $username;

    /** @var Bitbucket\Api\Repositories\AbstractRepositoriesApi */
    protected $workspace;

    /**
     * BitbucketClient constructor.
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
            $auth = $authFactory->make('oauth');
        } elseif (!empty($username) && !empty($password)) {
            $auth = $authFactory->make('password');
        }

        $client = new Client();
        $this->workspace = new Workspaces($client, $username);

        if (empty($auth)) {
            $this->client = $client;
        } else {
            $this->client = $auth->with($client)->authenticate($config);
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
            throw new InternalServerErrorException('No account/organization name provided for Bitbucket client.');
        }
    }

    /**
     * @param int $page
     * @param int $perPage
     *
     * @return array
     * @throws \DreamFactory\Core\Exceptions\RestException
     */
    public function repoAll($page = 1, $perPage = 50)
    {
        $pager = new ResultPager($this->client);
        $repositories = $pager->fetchAll($this->workspace, "list");
        return array_chunk($repositories, $perPage)[$page - 1];
    }

    /**
     * @param string $repo
     * @param null $path
     * @param null $ref
     *
     * @return array
     * @throws \DreamFactory\Core\Exceptions\RestException
     */
    public function repoList($repo, $path = null, $ref = null)
    {
        $src = $this->workspace->src($repo);
        $pager = new ResultPager($this->client);
        $params = [
            'fields' => 'values.commit.hash,values.commit.date,values.commit.revision,values.path,values.name,values.type,values.node,values.size'
        ];
        $list = $pager->fetchAll($src, "list", [$params]);

        return $this->cleanSrcList($list);
    }

    /**
     * @param string $repo
     * @param string $path
     * @param null $ref
     *
     * @return array
     * @throws \DreamFactory\Core\Exceptions\RestException
     */
    public function repoGetFileInfo($repo, $path, $ref = null)
    {
        $src = new CustomSrc($this->client, $this->username, $repo);

        $result = $src->show($ref, $path);
        if ('commit_directory' === $result['type']) {
            $pager = new ResultPager($this->client);
            $params = [
                'fields' => 'values.commit.hash,values.commit.date,values.path,values.name,values.type,values.node,values.size'
            ];
            $result = $pager->fetchAll($src, "listPath", [$ref, $path, $params]);
            $result = $this->cleanSrcList($result);
        } else {
            $file_content = $src->download($ref, $path);
            $result = $this->cleanSrcData($result, $file_content);
        }

        return $result;
    }

    /**
     * @param string $repo
     * @param string $path
     * @param null $ref
     *
     * @return mixed|string
     * @throws \DreamFactory\Core\Exceptions\RestException
     */
    public function repoGetFileContent($repo, $path, $ref = null)
    {
        $src = new CustomSrc($this->client, $this->username, $repo);

        $result = $src->show($ref, $path);

        if ('commit_directory' === $result['type']) {
            $pager = new ResultPager($this->client);
            $params = ['fields' => 'values.path,values.name'];
            $result = $pager->fetchAll($src, "listPath", [$ref, $path, $params]);
            return $this->cleanDirectoryContent($result);
        } else {
            return (string)$src->download($ref, $path);
        }

    }

    /**
     * @param $list
     *
     * @return array
     */
    protected function cleanDirectoryContent($list)
    {
        $names = [];
        foreach ($list as $obj) {
            $chuckedName = explode('/', $obj['path']);
            $name = array_pop($chuckedName);
            $names[] = $name;
        }
        return implode("\n", $names);
    }

    /**
     * @param $list
     *
     * @return array
     */
    protected function cleanSrcList($list)
    {
        $dirs = array();
        $files = array();

        foreach ($list as $obj) {
            if ("commit_directory" === $obj['type']) {
                $dirs[] = $obj;
            } elseif ("commit_file" === $obj['type']) {
                $files[] = $obj;
            }
        }

        foreach ($dirs as $key => $dir) {
            $chunked_path = explode('/', $dir['path']);
            $dirs[$key] = [
                'name' => array_pop($chunked_path),
                'path' => implode("/", $chunked_path) . '/',
                'type' => 'dir',
                'node' => $dir['commit']['hash']
            ];
        }

        foreach ($files as $key => $file) {
            $name = Arr::get($file, 'path');
            $date = new \DateTime($file['commit']['date']);
            $date->setTimeZone(new \DateTimeZone('UTC'));
            $files[$key] = [
                'size' => $file['size'],
                'path' => $file['path'],
                'timestamp' => date_format($date, 'Y-m-d\TH:i:s\Z'),
                'utctimestamp' => $date->format('Y-m-d H:i:sP'),
                'revision' => $file['commit']['hash'],
                'name' => $name,
                'type' => 'file',
                'node' => $file['commit']['hash']
            ];
        }

        return array_merge($dirs, $files);
    }

    /**
     * @param $data
     * @param $content
     *
     * @return array
     */
    protected function cleanSrcData($data, $content)
    {
        $content = base64_encode($content);
        $size = Arr::get($data, 'size');
        $path = Arr::get($data, 'path');
        $data = [
            'node' => $data['commit']['hash'],
            'path' => $path,
            'content' => $content,
            'encoding' => 'base64',
            'size' => $size,
        ];

        return $data;
    }
}