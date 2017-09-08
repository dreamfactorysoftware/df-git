<?php

namespace DreamFactory\Core\Git\Services;

use DreamFactory\Core\Git\Components\GitLabClient;
use DreamFactory\Core\Git\Resources\GitLabRepo;

class GitLab extends BaseService
{
    /** @type array Service Resources */
    protected static $resources = [
        GitLabRepo::RESOURCE_NAME => [
            'name'       => GitLabRepo::RESOURCE_NAME,
            'class_name' => GitLabRepo::class,
            'label'      => 'Repository'
        ]
    ];

    /** @inheritdoc */
    protected function setClient($config)
    {
        /** @var \DreamFactory\Core\Git\Components\GitLabClient */
        $this->client = new GitLabClient($config);
    }
}