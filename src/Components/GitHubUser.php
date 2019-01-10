<?php
namespace DreamFactory\Core\Git\Components;

use Github\Api\User;

class GitHubUser extends User
{
    public function repositories($username, $type = 'owner', $sort = 'full_name', $direction = 'asc', $visibility = 'all', $affiliation = 'owner,collaborator,organization_member', $extra = [])
    {
        $headers = array_merge([
            'type' => $type,
            'sort' => $sort,
            'direction' => $direction,
            'visibility' => $visibility,
            'affiliation' => $affiliation
        ], $extra);
        return $this->get('/users/'.rawurlencode($username).'/repos', $headers);
    }
}