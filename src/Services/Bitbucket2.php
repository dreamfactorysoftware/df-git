<?php

namespace DreamFactory\Core\Git\Services;

use DreamFactory\Core\Git\Components\BitbucketClient2;
use DreamFactory\Core\Git\Resources\BitbucketRepo2;

class Bitbucket2 extends BaseService
{
    /** @type array Service Resources */
    protected static $resources = [
        BitbucketRepo2::RESOURCE_NAME => [
            'name'       => BitbucketRepo2::RESOURCE_NAME,
            'class_name' => BitbucketRepo2::class,
            'label'      => 'Repository'
        ]
    ];

    /** @inheritdoc */
    protected function setClient($config)
    {
        /** @var BitbucketClient client */
        $this->client = new BitbucketClient2($config);
    }
}