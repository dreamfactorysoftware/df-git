<?php

namespace DreamFactory\Core\Git\Services;

use DreamFactory\Core\Git\Components\BitbucketClient;
use DreamFactory\Core\Git\Resources\BitbucketRepo;

class Bitbucket extends BaseService
{
    /** @type array Service Resources */
    protected static $resources = [
        BitbucketRepo::RESOURCE_NAME => [
            'name'       => BitbucketRepo::RESOURCE_NAME,
            'class_name' => BitbucketRepo::class,
            'label'      => 'Repository'
        ]
    ];

    /** @inheritdoc */
    protected function setClient($config)
    {
        /** @var BitbucketClient client */
        $this->client = new BitbucketClient($config);
    }
}