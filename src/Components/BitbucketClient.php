<?php

namespace DreamFactory\Core\Git\Components;

use DreamFactory\Core\Git\Contracts\ClientInterface;

class BitbucketClient implements ClientInterface
{
    public function __construct($config)
    {

    }

    public function repoAll($page = 1, $perPage = 50)
    {
        // TODO: Implement repoAll() method.
    }

    public function repoList($repo, $path = null, $ref = null)
    {
        // TODO: Implement repoList() method.
    }

    public function repoGetFileInfo($repo, $path, $ref = null)
    {
        // TODO: Implement repoGetFileInfo() method.
    }

    public function repoGetFileContent($repo, $path, $ref = null)
    {
        // TODO: Implement repoGetFileContent() method.
    }
}