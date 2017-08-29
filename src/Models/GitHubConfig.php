<?php

namespace DreamFactory\Core\Git\Models;

use DreamFactory\Core\Models\BaseServiceConfigModel;

class GitHubConfig extends BaseServiceConfigModel
{
    /** @var string */
    protected $table = 'github_config';

    /** @var array */
    protected $fillable = [
        'service_id',
        'username',
        'password',
        'token',
    ];

    /** @var array */
    protected $casts = [
        'service_id' => 'integer',
    ];

    /** @var array */
    protected $encrypted = ['token', 'password'];

    /** @var array */
    protected $protected = ['token', 'password'];

    /**
     * {@inheritdoc}
     */
    protected static function prepareConfigSchemaField(array &$schema)
    {
        parent::prepareConfigSchemaField($schema);

        switch ($schema['name']) {
            case 'username':
                $schema['label'] = 'Username';
                $schema['description'] = 'Your GitHub username goes here.';
                break;
            case 'password':
                $schema['type'] = 'password';
                $schema['label'] = 'Password';
                $schema['description'] = 'Your GitHub password goes here.';
                break;
            case 'token':
                $schema['label'] = 'GitHub Token';
                $schema['description'] = 'You can use a GitHub token here to access your account.' .
                    ' If you use token then there is no need to enter the Password above.';
                break;
        }
    }
}