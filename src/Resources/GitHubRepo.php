<?php

namespace DreamFactory\Core\Git\Resources;

class GitHubRepo extends BaseResource
{
    /** Resource name */
    const RESOURCE_NAME = '_repo';

    /** {@inheritdoc} */
    protected static function getResourceDefinition()
    {
        return [
            'type'       => 'object',
            'properties' => [
                'name'         => ['type' => 'string'],
                'path'         => ['type' => 'string'],
                'sha'          => ['type' => 'string'],
                'size'         => ['type' => 'integer'],
                'url'          => ['type' => 'string'],
                'html_url'     => ['type' => 'string'],
                'git_url'      => ['type' => 'string'],
                'download_url' => ['type' => 'string'],
                'type'         => ['type' => 'string'],
                'content'      => ['type' => 'string'],
                'encoding'     => ['type' => 'string'],
                '_links'       => [
                    'type'       => 'object',
                    'properties' => [
                        'self' => ['type' => 'string'],
                        'git'  => ['type' => 'string'],
                        'html' => ['type' => 'string'],
                    ]
                ],
            ]
        ];
    }
}