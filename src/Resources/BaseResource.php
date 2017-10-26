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
     * @return array
     */
    protected static function getResourceDefinition()
    {
        return [
            'type'       => 'object',
            'properties' => [
                'name' => ['type' => 'string'],
                'path' => ['type' => 'string'],
            ]
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function handleGET()
    {
        $branch = $this->request->getParameter('branch', $this->request->getParameter('tag', 'master'));
        $getContent = $this->request->getParameterAsBool('content');
        $asList = $this->request->getParameter(ApiOptions::AS_LIST);
        $fields = $this->request->getParameter(ApiOptions::FIELDS, '*');

        $resourceArray = $this->resourceArray;
        $repo = array_get($resourceArray, 0);

        try {
            if (empty($repo)) {
                $content = $this->parent->getClient()->repoAll();
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
        $base = parent::getApiDocPaths();
        $resourceName = strtolower($this->name);
        $path = '/' . $resourceName;
        $base[$path]['get'] = [
            'summary'     => 'getRepositoryList() - Get Repository List',
            'operationId' => 'getRepositoryList',
            'description' => 'Fetches a list of repositories',
            'parameters'  => [
                ApiOptions::documentOption(ApiOptions::AS_LIST),
            ],
            'responses'   => [
                '200' => [
                    'description' => 'Success',
                    'content'     => [
                        'application/json' => [
                            'schema' => [
                                'type'       => 'object',
                                'properties' => [
                                    'resource' => [
                                        'type'  => 'array',
                                        'items' => [
                                            [
                                                'type'       => 'object',
                                                'properties' => [
                                                    'id'          => ['type' => 'integer'],
                                                    'name'        => ['type' => 'string'],
                                                    'description' => ['type' => 'string'],
                                                ]
                                            ]
                                        ]
                                    ],
                                ]
                            ]
                        ]
                    ]
                ],
            ],
        ];

        $base[$path . '/{repo_name}']['get'] = [
            'summary'     => 'getRepository() - Get Repository Files',
            'operationId' => 'getRepository',
            'description' => 'Fetches a repository files',
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
                [
                    'name'        => 'content',
                    'in'          => 'query',
                    'schema'      => ['type' => 'boolean'],
                    'description' => 'Set true to get file content'
                ],
                ApiOptions::documentOption(ApiOptions::AS_LIST),
            ],
            'responses'   => [
                '200' => [
                    'description' => 'Success',
                    'content'     => [
                        'application/json' => [
                            'schema' => [
                                'type'       => 'object',
                                'properties' => [
                                    'resource' => [
                                        'type'  => 'array',
                                        'items' => [static::getResourceDefinition()]
                                    ],
                                ]
                            ]
                        ]
                    ]
                ],
            ],
        ];

        $base[$path . '/{repo_name}/{path}']['get'] = [
            'summary'     => 'getRepository() - Get Repository Files',
            'operationId' => 'getRepository',
            'description' => 'Fetches a repository files',
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
                '200' => [
                    'description' => 'Success',
                    'content'     => [
                        'application/json' => [
                            'schema' => static::getResourceDefinition(),
                        ]
                    ]
                ],
            ],
        ];

        return $base;
    }
}