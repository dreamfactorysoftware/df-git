<?php

namespace DreamFactory\Core\Git\Components;

use Buzz\Client\ClientInterface;
use Gitlab\HttpClient\HttpClient;
use Gitlab\HttpClient\Listener\ErrorListener;

class _GitLabHttpClientExtension extends HttpClient
{
    public function __construct($baseUrl, array $options, ClientInterface $client)
    {
        parent::__construct($baseUrl, $options, $client);
        unset($this->listeners[ErrorListener::class]);
        $this->addListener(new GitLabErrorListenerExtension($this->options));
    }
}