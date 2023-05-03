<?php

namespace TomCan\AcmeClient\Interfaces;

interface AuthorizationInterface
{
    /**
     * @param ChallengeInterface[] $challenges
     */
    public function __construct(string $url, string $identifier, string $status, \DateTime $expires, array $challenges);
    public function getUrl(): string;
    public function getIdentifier(): string;
    public function getStatus(): string;
    public function setStatus(string $status): void;
    public function getExpires(): \DateTime;

    /**
     * @return ChallengeInterface[]
     */
    public function getChallenges(): array;
}
