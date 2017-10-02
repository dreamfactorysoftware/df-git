<?php

namespace DreamFactory\Core\Git\Contracts;

interface ClientInterface
{
    /**
     * Returns all repos/projects owned by the configured user.
     *
     * @return array
     */
    public function repoAll($page = 1, $perPage = 50);

    /**
     * Lists contents of a repository.
     *
     * @param string      $repo
     * @param null|string $path
     * @param null|string $ref
     *
     * @return array
     */
    public function repoList($repo, $path = null, $ref = null);

    /**
     * Returns a specific file info from a repository.
     *
     * @param string      $repo
     * @param string      $path
     * @param null|string $ref
     *
     * @return array
     */
    public function repoGetFileInfo($repo, $path, $ref = null);

    /**
     * Returns content of a file from a repository.
     *
     * @param string      $repo
     * @param string      $path
     * @param null|string $ref
     *
     * @return mixed
     */
    public function repoGetFileContent($repo, $path, $ref = null);
}