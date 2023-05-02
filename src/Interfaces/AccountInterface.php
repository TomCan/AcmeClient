<?php

namespace TomCan\AcmeClient\Interfaces;

interface AccountInterface
{
    public function __construct(string $email, string $url, ?string $key);
    public function getEmail(): string;
    public function getUrl(): ?string;
    public function setUrl(string $url): void;
    public function getKey(): ?string;
}