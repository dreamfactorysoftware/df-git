<?php

namespace DreamFactory\Core\Git\Resources;

class BitbucketRepo extends BaseResource
{
    /** Resource name */
    const RESOURCE_NAME = '_repo';

    /** {@inheritdoc} */
    protected static function getGitRepoDefinition()
    {
        return [
            'type'       => 'object',
            'properties' => [
                'uuid'        => ['type' => 'string'],
                'name'        => ['type' => 'string'],
                'description' => ['type' => 'string'],
            ]
        ];
    }
}