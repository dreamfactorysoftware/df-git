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
    public static function getApiDocInfo($service, array $resource = [])
    {
        $base = parent::getApiDocInfo($service, $resource);
        $serviceName = strtolower($service);
        $class = trim(strrchr(static::class, '\\'), '\\');
        $resourceName = strtolower(array_get($resource, 'name', $class));
        $path = '/' . $serviceName . '/' . $resourceName;
        $base['paths'][$path]['get'] = [
            'tags'        => [$serviceName],
            'summary'     => 'getRepositoryList() - Get Repository List',
            'operationId' => 'getRepositoryList',
            'consumes'    => ['application/json', 'application/xml'],
            'produces'    => ['application/json', 'application/xml'],
            'description' => 'Fetches a list of repositories',
            'parameters'  => [
                ApiOptions::documentOption(ApiOptions::AS_LIST),
                [
                    'name'        => 'page',
                    'in'          => 'query',
                    'type'        => 'integer',
                    'description' => 'Page number to fetch. Default is 1.'
                ],
                [
                    'name'        => 'per_page',
                    'in'          => 'query',
                    'type'        => 'integer',
                    'description' => 'Number of entries per page. Default is 50.'
                ]
            ],
            'responses'   => [
                '200'     => [
                    'description' => 'Success',
                    'schema'      => [
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
                ],
                'default' => [
                    'description' => 'Error',
                    'schema'      => ['$ref' => '#/definitions/Error']
                ]
            ],
        ];

        $base['paths'][$path . '/{repo_name}']['get'] = [
            'tags'        => [$serviceName],
            'summary'     => 'getRepository() - Get Repository Files',
            'operationId' => 'getRepository',
            'consumes'    => ['application/json', 'application/xml'],
            'produces'    => ['application/json', 'application/xml'],
            'description' => 'Fetches a repository files',
            'parameters'  => [
                [
                    'name'        => 'repo_name',
                    'in'          => 'path',
                    'type'        => 'string',
                    'description' => 'Repo name',
                    'required'    => true,
                ],
                [
                    'name'        => 'path',
                    'in'          => 'query',
                    'type'        => 'string',
                    'description' => 'A file/folder path'
                ],
                [
                    'name'        => 'content',
                    'in'          => 'query',
                    'type'        => 'boolean',
                    'description' => 'Set true to get file content'
                ],
                ApiOptions::documentOption(ApiOptions::AS_LIST),
            ],
            'responses'   => [
                '200'     => [
                    'description' => 'Success',
                    'schema'      => [
                        'type'       => 'object',
                        'properties' => [
                            'resource' => [
                                'type'  => 'array',
                                'items' => [static::getResourceDefinition()]
                            ],
                        ]
                    ]
                ],
                'default' => [
                    'description' => 'Error',
                    'schema'      => ['$ref' => '#/definitions/Error']
                ]
            ],
        ];

        $base['paths'][$path . '/{repo_name}/{path}']['get'] = [
            'tags'        => [$serviceName],
            'summary'     => 'getRepository() - Get Repository Files',
            'operationId' => 'getRepository',
            'consumes'    => ['application/json', 'application/xml'],
            'produces'    => ['application/json', 'application/xml'],
            'description' => 'Fetches a repository files',
            'parameters'  => [
                [
                    'name'        => 'repo_name',
                    'in'          => 'path',
                    'type'        => 'string',
                    'description' => 'Repo name',
                    'required'    => true,
                ],
                [
                    'name'        => 'path',
                    'in'          => 'path',
                    'type'        => 'string',
                    'description' => 'A file/folder path',
                ],
                [
                    'name'        => 'content',
                    'in'          => 'query',
                    'type'        => 'boolean',
                    'description' => 'Set true to get file content',
                ],
            ],
            'responses'   => [
                '200'     => [
                    'description' => 'Success',
                    'schema'      => static::getResourceDefinition(),
                ],
                'default' => [
                    'description' => 'Error',
                    'schema'      => ['$ref' => '#/definitions/Error']
                ]
            ],
        ];

        return $base;
    }
}