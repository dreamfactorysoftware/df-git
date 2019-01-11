<?php

namespace DreamFactory\Core\Git\Components;

use DreamFactory\Core\Exceptions\InternalServerErrorException;
use DreamFactory\Core\Git\Contracts\ClientInterface;

class GitLabClient implements ClientInterface
{
    /** @var \GitLab\Client */
    protected $client;

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
        $this->client = null;
    }

    /**
     * @param $config
     *
     * @throws \DreamFactory\Core\Exceptions\InternalServerErrorException
     */
    protected function validateConfig($config)
    {
        // TODO:
        if (false)
            throw new InternalServerErrorException('invalid config');
    }

}
