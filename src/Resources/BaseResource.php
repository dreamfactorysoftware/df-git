<?php

namespace DreamFactory\Core\Git\Resources;

use DreamFactory\Core\Exceptions\RestException;
use DreamFactory\Core\Resources\BaseRestResource;
use DreamFactory\Core\Enums\ApiOptions;
use DreamFactory\Core\Utility\ResourcesWrapper;
use Github\Exception\RuntimeException;

class BaseResource extends BaseRestResource
{
    /** A resource identifier used in swagger doc. */
    const RESOURCE_IDENTIFIER = 'name';

    /** @var \DreamFactory\Core\Git\Services\GitHub */
    protected $parent;

    /**
     * {@inheritdoc}
     */
    protected static function getResourceIdentifier()
    {
        return static::RESOURCE_IDENTIFIER;
    }

    /**
     * {@inheritdoc}
     */
    protected function handleGET()
    {
        $branch = $this->request->getParameter('branch', $this->request->getParameter('tag', 'master'));
        $getContent = $this->request->getParameterAsBool('content');
        $asList = $this->request->getParameterAsBool(ApiOptions::AS_LIST);
        $fields = $this->request->getParameter(ApiOptions::FIELDS, '*');
        $page = $this->request->getParameter('page', 1);
        $perPage = $this->request->getParameter('per_page', 50);

        $resourceArray = $this->resourceArray;
        $repo = array_get($resourceArray, 0);

        try {
            if (empty($repo)) {
                $content = $this->parent->getClient()->repoAll($page, $perPage);
            } else {
                array_shift($resourceArray);
                $path = implode('/', $resourceArray);
                if (empty($path)) {
                    $path = $this->request->getParameter('path');
                }

                if ($getContent && !empty($path)) {
                    $content = $this->parent->getClient()->repoGetFileContent($repo, $path, $branch);
                } elseif (!empty($path)) {
                    $content = $this->parent->getClient()->repoGetFileInfo($repo, $path, $branch);
                } else {
                    $content = $this->parent->getClient()->repoList($repo, $path, $branch);
                }
            }
        } catch (RuntimeException $e) {
            throw new RestException($e->getCode(), $e->getMessage());
        } catch (\Gitlab\Exception\RuntimeException $e) {
            throw new RestException($e->getCode(), $e->getMessage());
        }

        if (is_array($content)) {
            return ResourcesWrapper::cleanResources($content, $asList, ['name'], $fields);
        }

        return $content;
    }

    /** {@inheritdoc} */
    protected function getApiDocPaths()
    {
        $service = $this->getServiceName();
        $capitalized = camelize($service);
        $resourceName = strtolower($this->name);
        $path = '/' . $resourceName;

        $paths = [
            $path                         => [
                'get' => [
                    'summary'     => 'Get Repository List',
                    'description' => 'Fetches a list of repositories',
                    'operationId' => 'get' . $capitalized . 'RepositoryList',
                    'parameters'  => [
                        ApiOptions::documentOption(ApiOptions::AS_LIST),
                        [
                            'name'        => 'page',
                            'in'          => 'query',
                            'schema'      => ['type' => 'integer'],
                            'description' => 'Page number to fetch. Default is 1.'
                        ],
                        [
                            'name'        => 'per_page',
                            'in'          => 'query',
                            'schema'      => ['type' => 'integer'],
                            'description' => 'Number of entries per page. Default is 50.'
                        ]
                    ],
                    'responses'   => [
                        '200' => ['$ref' => '#/components/responses/GitReposResponse']
                    ],
                ],
            ],
            $path . '/{repo_name}'        => [
                'get' => [
                    'summary'     => 'Get Repository Files and Directories',
                    'description' => 'Fetches a repository files and directories',
                    'operationId' => 'get' . $capitalized . 'Repository',
                    'parameters'  => [
                        [
                            'name'        => 'repo_name',
                            'in'          => 'path',
                            'schema'      => ['type' => 'string'],
                            'description' => 'Repo name',
                            'required'    => true,
                        ],
                        [
                            'name'        => 'path',
                            'in'          => 'query',
                            'schema'      => ['type' => 'string'],
                            'description' => 'A file/folder path'
                        ],
                        ApiOptions::documentOption(ApiOptions::AS_LIST),
                    ],
                    'responses'   => [
                        '200' => ['$ref' => '#/components/responses/GitRepoFilesResponse'],
                    ],
                ],
            ],
            $path . '/{repo_name}/{path}' => [
                'get' => [
                    'summary'     => 'Get Repository File',
                    'description' => 'Fetches a repository file',
                    'operationId' => 'get' . $capitalized . 'RepositoryFile',
                    'parameters'  => [
                        [
                            'name'        => 'repo_name',
                            'in'          => 'path',
                            'schema'      => ['type' => 'string'],
                            'description' => 'Repo name',
                            'required'    => true,
                        ],
                        [
                            'name'        => 'path',
                            'in'          => 'path',
                            'schema'      => ['type' => 'string'],
                            'description' => 'A file/folder path',
                            'required'    => true,
                        ],
                        [
                            'name'        => 'content',
                            'in'          => 'query',
                            'schema'      => ['type' => 'boolean'],
                            'description' => 'Set true to get file content',
                        ],
                    ],
                    'responses'   => [
                        '200' => ['$ref' => '#/components/responses/GitRepoFileResponse'],
                    ],
                ],
            ],
        ];

        return $paths;
    }

    /** {@inheritdoc} */
    protected function getApiDocResponses()
    {
        return [
            'GitReposResponse'     => [
                'description' => 'Success',
                'content'     => [
                    'application/json' => [
                        'schema' => [
                            '$ref' => '#/components/schemas/GitRepos'
                        ]
                    ]
                ]
            ],
            'GitRepoFilesResponse' => [
                'description' => 'Success',
                'content'     => [
                    'application/json' => [
                        'schema' => [
                            '$ref' => '#/components/schemas/GitRepoFiles'
                        ]
                    ]
                ]
            ],
            'GitRepoFileResponse'  => [
                'description' => 'Success',
                'content'     => [
                    'application/json' => [
                        'schema' => [
                            '$ref' => '#/components/schemas/GitRepoFileDetails'
                        ]
                    ]
                ]
            ],
        ];
    }

    /** {@inheritdoc} */
    protected function getApiDocSchemas()
    {
        return [
            'GitRepos'           => [
                'type'       => 'object',
                'properties' => [
                    'resource' => [
                        'type'  => 'array',
                        'items' => [
                            '$ref' => '#/components/schemas/GitRepo'
                        ]
                    ]
                ]
            ],
            'GitRepo'            => static::getGitRepoDefinition(),
            'GitRepoFiles'       => [
                'type'       => 'object',
                'properties' => [
                    'resource' => [
                        'type'  => 'array',
                        'items' => ['$ref' => '#/components/schemas/GitRepoFile']
                    ],
                ],
            ],
            'GitRepoFile'        => [
                'type'       => 'object',
                'properties' => [
                    'name' => ['type' => 'string'],
                    'path' => ['type' => 'string'],
                    'type' => ['type' => 'string']
                ],
            ],
            'GitRepoFileDetails' => static::getResourceDefinition(),
        ];
    }

    /**
     * @return array
     */
    protected static function getGitRepoDefinition()
    {
        return [
            'type'       => 'object',
            'properties' => [
                'id'          => ['type' => 'integer'],
                'name'        => ['type' => 'string'],
                'description' => ['type' => 'string'],
            ]
        ];
    }

    /**
     * @return array
     */
    protected static function getResourceDefinition()
    {
        return [
            'type'       => 'object',
            'properties' => [
                'name'     => ['type' => 'string'],
                'path'     => ['type' => 'string'],
                'type'     => ['type' => 'string'],
                'content'  => ['type' => 'string'],
                'encoding' => ['type' => 'string']
            ],
        ];
    }
}