<?php

namespace DreamFactory\Core\Git\Components;

use Bitbucket\Api\Repositories\Users\Src;
use Http\Client\Common\HttpMethodsClient;


class BitbucketRepositorySrc extends Src
{
    /**
     * {@inheritDoc}
     */
    private $client;

    /**
     * {@inheritDoc}
     */
    private $perPage;

    /**
     * {@inheritDoc}
     */
    public function __construct(HttpMethodsClient $client, string $username, string $repo)
    {
        parent::__construct($client, $username, $repo);
        $this->client = $client;
    }

    /**
     * @param array $params
     * @param string $ref
     * @param string $path
     *
     * @throws \Http\Client\Exception
     *
     * @return array
     */
    public function listPath($ref, $path="/", array $params = [])
    {
        return ["values" => $this->get($this->buildSrcPath($ref, $path), $params)];
    }

    /**
     * Build the raw path from the given parts.
     *
     * @param string[] $parts
     *
     * @throws \Bitbucket\Exception\InvalidArgumentException
     *
     * @return string
     */
    protected function buildRawPath(string ...$parts)
    {
        return static::buildPath('repositories', $this->username, $this->repo, 'raw', ...$parts);
    }

    /**
     * @param string $ref
     * @param string $path
     * @param array  $params
     *
     * @throws \Http\Client\Exception
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function raw($ref, $path="/", array $params = [])
    {
        return $this->pureGet($this->buildRawPath($ref, $path), $params);
    }

    /**
     * {@inheritDoc}
     */
    public function pureGet(string $path, array $params = [], array $headers = [])
    {
        if ($this->perPage !== null && !isset($params['pagelen'])) {
            $params['pagelen'] = $this->perPage;
        }

        if ($params) {
            $path .= '?' . http_build_query($params);
        }

        return $this->client->get(self::computePath($path), $headers);
    }

    /**
     * {@inheritDoc}
     */
    public static function computePath(string $path)
    {
        return sprintf('/1.0/%s', $path);
    }
}