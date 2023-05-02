<?php


namespace TomCan\AcmeClient\Interfaces;

interface OrderInterface
{
    public function __construct(string $url, string $status, \DateTime $expires, array $identifiers, array $authorizations, string $finalize);
    public function getUrl(): string;
    public function getAuthorizations(): array;
    public function getStatus(): string;
    public function getExpires(): \DateTime;
    public function getIdentifiers(): array;
    public function getFinalize(): string;
}
