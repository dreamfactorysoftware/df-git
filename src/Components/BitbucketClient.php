<?php

namespace DreamFactory\Core\Git\Components;

use Bitbucket\API\Http\Response\Pager;
use Buzz\Message\Response;
use DreamFactory\Core\Exceptions\RestException;
use DreamFactory\Core\Git\Contracts\ClientInterface as GitClientInterface;
use DreamFactory\Core\Exceptions\InternalServerErrorException;
use GrahamCampbell\Bitbucket\Authenticators\PasswordAuthenticator;
use GrahamCampbell\Bitbucket\Authenticators\OauthAuthenticator;
use Bitbucket\Client;
use Bitbucket\Api\Repositories\Users\Src;
use Bitbucket\ResultPager;
use Bitbucket\HttpClient\Builder;
use Bitbucket\Api\ApiInterface;


//use Bitbucket\API\Api;
//use Bitbucket\Client;
//use Bitbucket\API\Http\ClientInterface;
//use Bitbucket\API\Http\Listener\NormalizeArrayListener;

class CustomSrc extends Src {
    public function listPath($ref, $path, $params) {
        return $this->get($this->buildSrcPath($ref, $path), $params);
    }
}

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

//        $httpClient = $this->getHttpClient();
//        $client = new Api();
//        $client->setClient($httpClient);
        $client = new Client(new Builder());
//        $client->setUrl('https://api.bitbucket.org');

        if (empty($auth)) {
            $this->client = $client;
        } else {
            $this->client = $auth->with($client)->authenticate($config);
        }
    }

    /**
     * Get the http client.
     *
     * @return ClientInterface
     */
//    protected function getHttpClient()
//    {
//        $options = [
//            'base_url'    => 'https://api.bitbucket.org',
//            'api_version' => '1.0',
//            'verify_peer' => true,
//        ];
//
//
//        $client = new Client();
//
//        $client->addListener(new NormalizeArrayListener());
//
//        return $client;
//    }

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

//        return $repo->users($this->username)->list()['values'];
//        return json_decode($response->getContent(), true)['values'];

//        $pager = new Pager($repo->getClient(), $repo->all($this->username));
//        /** @var \Buzz\Message\Response $response */
//
//        return json_decode($response->getContent(), true)['values'];

        /*$repo = $this->client->api('Repositories');
        $pager = new Pager($repo->getClient(), $repo->all($this->username));*/
//        /** @var \Buzz\Message\Response $response */
        /*$response = $this->checkResponse($pager->fetchAll());*/

        /*return json_decode($response->getContent(), true)['values'];*/
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

        $src = $this->client->repositories()->users($this->username)->src($repo);
        $pager = new ResultPager($this->client);
        $params = [
            'fields' => 'values.commit.hash,values.commit.date,values.commit.revision,values.path,values.name,values.type,values.node,values.size'
        ];
        $list = $pager->fetchAll($src, "list", [ $params ]);

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

        $src = new CustomSrc($this->client->getHttpClient(), $this->username, $repo);

        $result = $src->listPath($ref, $path, [ 'format' => 'meta' ]);
        if ('commit_directory' === $result['type']) {
            $pager = new ResultPager($this->client);
            $params = [ 'fields' => 'values.commit.hash,values.commit.date,values.path,values.name,values.type,values.node,values.size' ];
            $result = $pager->fetchAll($src, "listPath", [ $ref, $path, $params ]);
            $result = $this->cleanSrcList($result);
        } else {
            $file_content = $src->download($ref, $path);
            $result = $this->cleanSrcData($result, $file_content);
        }

        return $result;

        //        return $this->cleanSrcData(json_decode($response->getContent(), true));

        /*$src = $this->client->api('Repositories\Src');

        $response = $this->checkResponse($src->get($this->username, $repo, $ref, $path));

        return $this->cleanSrcData(json_decode($response->getContent(), true));*/
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


        $src = $this->client->repositories()->users($this->username)->src($repo);
        return (string) $src->download($ref, $path);


//        $src = $this->client->api('Repositories\Src');

//        $response = $this->checkResponse($src->raw($this->username, $repo, $ref, $path));
//
//        return $response->getContent();
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
        $dirs = array();
        $files = array();
//        return $list;
        foreach ($list as $obj) {
            if ($obj['type'] == "commit_directory") {
                $dirs[] = $obj;
            } elseif ($obj['type'] == "commit_file") {
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
//        "utctimestamp": "2019-01-15 15:35:56+00:00",
        foreach ($files as $key => $file) {
            $name = array_get($file, 'path');
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
        $size = array_get($data, 'size');
        $path = array_get($data, 'path');
        $data = [
            'node' => $data['commit']['hash'],
            'path' => $path,
            'content' => $content,
            'encoding' => 'base64',
            'size' => $size,
        ];
//        unset($data['size']);
//        unset($data['data']); // Keeping response consistent with other SCM services using key 'content' instead of 'data'
//        $data['content'] = $content;
//        $data['encoding'] = 'base64';
//        $data['size'] = $size;

        return $data;
    }
}