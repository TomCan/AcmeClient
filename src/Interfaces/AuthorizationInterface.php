<?php

namespace TomCan\AcmeClient\Interfaces;

interface AuthorizationInterface
{
    public function __construct(string $url, string $identifier, string $status, \DateTime $expires, array $challenges);
    public function getUrl(): string;
    public function getIdentifier(): string;
    public function getStatus(): string;
    public function getExpires(): \DateTime;
    public function getChallenges(): array;
}
