<?php

namespace DreamFactory\Core\Git\Services;

use DreamFactory\Core\Git\Components\Bitbucket2Client;
use DreamFactory\Core\Git\Resources\Bitbucket2Repo;

class Bitbucket2 extends BaseService
{
    /** @type array Service Resources */
    protected static $resources = [
        Bitbucket2Repo::RESOURCE_NAME => [
            'name'       => Bitbucket2Repo::RESOURCE_NAME,
            'class_name' => Bitbucket2Repo::class,
            'label'      => 'Repository'
        ]
    ];

    /** @inheritdoc */
    protected function setClient($config)
    {
        /** @var BitbucketClient client */
        $this->client = new Bitbucket2Client($config);
    }
}