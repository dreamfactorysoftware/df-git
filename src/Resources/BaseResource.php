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
        $asList = $this->request->getParameter(ApiOptions::AS_LIST);
        $fields = $this->request->getParameter(ApiOptions::FIELDS, '*');

        $resourceArray = $this->resourceArray;
        $repo = array_get($resourceArray, 0);
        //array_shift($resourceArray);
        //$path = implode('/', $resourceArray);

        try {
            if (empty($repo)) {
                $content = $this->parent->getClient()->repoAll();
            } else {
                $path = $this->request->getParameter('path');

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
        }

        if (is_array($content)) {
            return ResourcesWrapper::cleanResources($content, $asList, ['name'], $fields);
        }

        return $content;
    }
}