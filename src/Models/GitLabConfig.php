<?php

namespace DreamFactory\Core\Git\Models;

use DreamFactory\Core\Models\BaseServiceConfigModel;

class GitLabConfig extends BaseServiceConfigModel
{
    /** @var string */
    protected $table = 'gitlab_config';

    /** @var array */
    protected $fillable = [
        'service_id',
        'base_url',
        'namespace',
        'token',
    ];

    /** @var array */
    protected $casts = [
        'service_id' => 'integer',
    ];

    /** @var array */
    protected $encrypted = ['token'];

    /** @var array */
    protected $protected = ['token'];

    /**
     * {@inheritdoc}
     */
    protected static function prepareConfigSchemaField(array &$schema)
    {
        parent::prepareConfigSchemaField($schema);

        switch ($schema['name']) {
            case 'base_url':
                $schema['label'] = 'Base URL';
                $schema['description'] = 'Your GitLab base url goes here. Example: https://gitlab.com/api/v4/';
                break;
            case 'namespace':
                $schema['label'] = 'Namespace/Group';
                $schema['description'] = 'Enter your GitLab namespace/group path here. ' .
                    'If this is left blank then your username will be used as namespace. ' .
                    'You will only see projects that are under your namespace.';
                break;
            case 'token':
                $schema['label'] = 'GitLab Token';
                $schema['description'] = 'Your GitLab access token goes here.';
                $schema['required'] = true;
                break;
        }
    }
}