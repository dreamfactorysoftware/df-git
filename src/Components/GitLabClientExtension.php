<?php

namespace DreamFactory\Core\Git\Components;

use Buzz\Client\ClientInterface;
use Gitlab\Client;
use Buzz\Client\Curl;
use Gitlab\HttpClient\Listener\PaginationListener;

class GitLabClientExtension extends Client
{
    /**
     * @var array
     */
    private $options = array(
        'user_agent' => 'php-gitlab-api (http://github.com/m4tthumphrey/php-gitlab-api)',
        'timeout'    => 60
    );

    public function __construct($baseUrl, ClientInterface $httpClient = null)
    {
        parent::__construct($baseUrl, $httpClient);
        $httpClient = $httpClient ?: new Curl();
        $httpClient->setTimeout(60);
        $httpClient->setVerifyPeer(false);
        $this->setBaseUrl($baseUrl);
        $myClient = new GitLabHttpClientExtension($baseUrl, $this->options, $httpClient);
        $myClient->addListener(
            new PaginationListener()
        );
        $this->setHttpClient($myClient);
    }
}