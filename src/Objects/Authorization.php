<?php

namespace TomCan\AcmeClient\Objects;

use TomCan\AcmeClient\Interfaces\AuthorizationInterface;

class Authorization implements AuthorizationInterface
{
    private string $url;
    private string $identifier;
    private string $status;
    private \DateTime $expires;
    private array $challenges;
    public function __construct(string $url, string $identifier, string $status, \DateTime $expires, array $challenges)
    {
        $this->url = $url;
        $this->identifier = $identifier;
        $this->status = $status;
        $this->expires = $expires;
        $this->challenges = $challenges;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getExpires(): \DateTime
    {
        return $this->expires;
    }

    public function getChallenges(): array
    {
        return $this->challenges;
    }
}
