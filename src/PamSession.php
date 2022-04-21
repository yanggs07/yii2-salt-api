<?php

namespace yanggs07\saltapi;

class PamSession
{
    private ApiClient $client;
    public string $token = '';
    public int $expireAt = 0;

    public static function start(ApiClient $client, string $token, int $expireAt): self
    {
        $session = new self();
        $session->client = $client;
        $session->token = $token;
        $session->expireAt = $expireAt;

        return $session;
    }

    public function auth(ApiClient $client): ApiClient
    {
        $client->setHeader('X-Auth-Token', $this->token);
        return $client;
    }
}