<?php

namespace DreamFactory\Core\Git;

use DreamFactory\Core\Enums\ServiceTypeGroups;
use DreamFactory\Core\Git\Models\BitbucketConfig;
use DreamFactory\Core\Git\Models\GitHubConfig;
use DreamFactory\Core\Git\Services\Bitbucket;
use DreamFactory\Core\Git\Services\GitHub;
use DreamFactory\Core\Git\Services\GitLab;
use DreamFactory\Core\Git\Models\GitLabConfig;
use DreamFactory\Core\Services\ServiceManager;
use DreamFactory\Core\Services\ServiceType;

class ServiceProvider extends \Illuminate\Support\ServiceProvider
{
    public function register()
    {
        // Add our service types.
        $this->app->resolving('df.service', function (ServiceManager $df){
            $df->addType(
                new ServiceType([
                    'name'            => 'github',
                    'label'           => 'GitHub Service',
                    'description'     => 'A client service for GitHub',
                    'group'           => ServiceTypeGroups::SCM,
                    'config_handler'  => GitHubConfig::class,
                    'factory'         => function ($config){
                        return new GitHub($config);
                    },
                ])
            );
            $df->addType(
                new ServiceType([
                    'name'            => 'gitlab',
                    'label'           => 'GitLab Service',
                    'description'     => 'A client service for GitLab',
                    'group'           => ServiceTypeGroups::SCM,
                    'config_handler'  => GitLabConfig::class,
                    'factory'         => function ($config){
                        return new GitLab($config);
                    },
                ])
            );
            $df->addType(
                new ServiceType([
                    'name'            => 'bitbucket',
                    'label'           => 'Bitbucket Service',
                    'description'     => 'A client service for Bitbucket',
                    'group'           => ServiceTypeGroups::SCM,
                    'config_handler'  => BitbucketConfig::class,
                    'factory'         => function ($config){
                        return new Bitbucket($config);
                    },
                ])
            );
        });
    }

    public function boot()
    {
        // add migrations
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }
}