<?php
namespace DreamFactory\Core\Git\Components;

use Github\Api\User;

class GitHubUser extends User
{
    public function repositories($username, $type = 'owner', $sort = 'full_name', $direction = 'asc', $extra = [])
    {
        $headers = array_merge([
            'type' => $type,
            'sort' => $sort,
            'direction' => $direction,
        ], $extra);
        return $this->get('/users/'.rawurlencode($username).'/repos', $headers);
    }
}