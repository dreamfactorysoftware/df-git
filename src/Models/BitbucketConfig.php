<?php

namespace DreamFactory\Core\Git\Models;

use DreamFactory\Core\Models\BaseServiceConfigModel;

class BitbucketConfig extends BaseServiceConfigModel
{
    /** @var string */
    protected $table = 'bitbucket_config';

    /** @var array */
    protected $fillable = [
        'service_id',
        'vendor',
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
            case 'vendor':
                $schema['label'] = 'Account/Organization';
                $schema['description'] = 'Bitbucket Account/Organization/Username for accessing a repository.';
                break;
            case 'username':
                $schema['label'] = 'Username';
                $schema['description'] = 'Your Bitbucket username goes here.';
                break;
            case 'password':
                $schema['type'] = 'password';
                $schema['label'] = 'Password';
                $schema['description'] = 'Your Bitbucket password. For Bitbucket cloud this will be the App Password. ' .
                    'If you use an App Password there is no need to enter a token below';
                break;
            case 'token':
                $schema['label'] = 'Bitbucket Token';
                $schema['description'] = 'You can use a Bitbucket token here to access your account.' .
                    ' If you use token then there is no need to enter the Password above.';
                break;
        }
    }
}