<?php

namespace DreamFactory\Core\Git\Services;

use DreamFactory\Core\Exceptions\InternalServerErrorException;
use DreamFactory\Core\Services\BaseRestService;
use DreamFactory\Core\Utility\Session;

abstract class BaseService extends BaseRestService
{
    /** @var  \DreamFactory\Core\Git\Contracts\ClientInterface */
    protected $client;

    public function __construct(array $settings)
    {
        parent::__construct($settings);

        $config = array_get($settings, 'config');
        Session::replaceLookups($config, true);

        if (empty($config)) {
            throw new InternalServerErrorException('No service configuration found for mqtt service.');
        }

        $this->setClient($config);
    }

    /**
     * @param $config
     *
     * @return mixed
     */
    abstract protected function setClient($config);

    /**
     * @return \DreamFactory\Core\Git\Contracts\ClientInterface
     */
    public function getClient()
    {
        return $this->client;
    }
}