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
                $schema['description'] = 'Your GitLab base url goes here. Example: http://git.yourdomain.com/api/v3/';
                break;
            case 'token':
                $schema['label'] = 'GitLab Token';
                $schema['description'] = 'Your GitLab access token goes here.';
                break;
        }
    }
}