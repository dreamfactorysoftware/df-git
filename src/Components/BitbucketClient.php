<?php

namespace DreamFactory\Core\Git\Components;

use DreamFactory\Core\Exceptions\RestException;
use DreamFactory\Core\Git\Contracts\ClientInterface as GitClientInterface;
use DreamFactory\Core\Exceptions\InternalServerErrorException;
use GrahamCampbell\Bitbucket\Authenticators\PasswordAuthenticator;
use GrahamCampbell\Bitbucket\Authenticators\OauthAuthenticator;
use Bitbucket\Client;
use Bitbucket\ResultPager;

class BitbucketClient implements GitClientInterface
{
    /** @var \Bitbucket\API\Api */
    protected $client;

    /** @var String */
    protected $username;

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
        $username = array_get($config, 'username');
        $password = array_get($config, 'password');
        $token = array_get($config, 'token');
        $auth = null;

        if (!empty($username) && !empty($token)) {
            $auth = new OauthAuthenticator();
        } elseif (!empty($username) && !empty($password)) {
            $auth = new PasswordAuthenticator();
        }

        $client = new Client();

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
        if (empty(array_get($config, 'vendor'))) {
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
        $repo = $this->client->repositories();

        $pager = new ResultPager($this->client);

        return $pager->fetchAll($repo->users($this->username), "list");
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

        $src = new BitbucketRepositorySrc($this->client->getHttpClient(), $this->username, $repo);
        $pager = new ResultPager($this->client);

        $list = $pager->fetchAll($src, "listPath", [ $ref ]);

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
        $src = new BitbucketRepositorySrc($this->client->getHttpClient(), $this->username, $repo);
        $pager = new ResultPager($this->client);

        $result = $pager->fetchAll($src, "listPath", [ $ref, $path ]);

        return $this->cleanSrcData($result);
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
        $src = new BitbucketRepositorySrc($this->client->getHttpClient(), $this->username, $repo);

        return (string) $src->raw($ref, $path)->getBody();
    }

    /**
     * @param \Buzz\Message\Response $response
     *
     * @return \Buzz\Message\Response
     * @throws \DreamFactory\Core\Exceptions\RestException
     */
    protected function checkResponse(Response $response)
    {
        $statusCode = $response->getStatusCode();

        if ($statusCode >= 300) {
            throw new RestException($statusCode, $response->getContent());
        }

        return $response;
    }

    /**
     * @param $list
     *
     * @return array
     */
    protected function cleanSrcList($list)
    {
        $dirs = array_get($list, 'directories');
        $files = array_get($list, 'files');
        $path = array_get($list, 'path');
        $node = array_get($list, 'node');

        foreach ($dirs as $key => $dir) {
            $dirs[$key] = [
                'name' => $dir,
                'path' => $path,
                'type' => 'dir',
                'node' => $node
            ];
        }

        foreach ($files as $key => $file) {
            $name = array_get($file, 'path');
            $files[$key]['name'] = $name;
            $files[$key]['type'] = 'file';
            $files[$key]['node'] = $node;
        }

        return array_merge($dirs, $files);
    }

    /**
     * @param $data
     *
     * @return array
     */
    protected function cleanSrcData($data)
    {
        if (isset($data['data'])) {
            $content = base64_encode($data['data']);
            $size = array_get($data, 'size');
            unset($data['size']);
            unset($data['data']); // Keeping response consistent with other SCM services using key 'content' instead of 'data'
            $data['content'] = $content;
            $data['encoding'] = 'base64';
            $data['size'] = $size;

            return $data;
        } elseif (isset($data['files']) || isset($data['directories'])) {
            return $this->cleanSrcList($data);
        }
    }
}