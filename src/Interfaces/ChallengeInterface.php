<?php

namespace TomCan\AcmeClient\Interfaces;

interface ChallengeInterface
{
    public function __construct(string $type, string $status, string $url, string $token, string $value);
    public function getType(): string;
    public function getStatus(): string;
    public function getUrl(): string;
    public function getToken(): string;
    public function getValue(): string;
}
