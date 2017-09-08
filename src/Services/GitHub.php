<?php

namespace DreamFactory\Core\Git\Services;

use DreamFactory\Core\Git\Components\GitHubClient;
use DreamFactory\Core\Git\Resources\GitHubRepo;

class GitHub extends BaseService
{
    /** @type array Service Resources */
    protected static $resources = [
        GitHubRepo::RESOURCE_NAME => [
            'name'       => GitHubRepo::RESOURCE_NAME,
            'class_name' => GitHubRepo::class,
            'label'      => 'Repository'
        ]
    ];

    /** @inheritdoc */
    protected function setClient($config)
    {
        /** @var GitHubClient client */
        $this->client = new GitHubClient($config);
    }
}