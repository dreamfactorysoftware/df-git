<?php

namespace DreamFactory\Core\Git\Components;

use Gitlab\HttpClient\Listener\ErrorListener;
use Buzz\Message\MessageInterface;
use Buzz\Message\RequestInterface;
use Gitlab\Exception\ErrorException;
use Gitlab\Exception\RuntimeException;

class _GitLabErrorListenerExtension extends ErrorListener
{
    /**
     * {@inheritDoc}
     */
    public function postSend(RequestInterface $request, MessageInterface $response)
    {
        /** @var $response \Gitlab\HttpClient\Message\Response */
        if ($response->isClientError() || $response->isServerError()) {
            $content = $response->getContent();
            if (is_array($content) && isset($content['message'])) {
                if (400 == $response->getStatusCode()) {
                    $message = $this->parseMessage($content['message']);

                    throw new ErrorException($message, 400);
                }
            }

            $errorMessage = null;
            if (isset($content['error'])) {
                if (is_array($content['error'])) {
                    $errorMessage = implode("\n", $content['error']);
                } else {
                    $errorMessage = $content['error'];
                }
            } elseif (isset($content['message'])) {
                $errorMessage = $this->parseMessage($content['message']);
            } else {
                $errorMessage = $content;
            }

            throw new RuntimeException($errorMessage, $response->getStatusCode());
        }
    }
}