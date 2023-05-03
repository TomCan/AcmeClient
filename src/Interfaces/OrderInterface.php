<?php


namespace TomCan\AcmeClient\Interfaces;

interface OrderInterface
{
    /**
     * @param string[] $identifiers
     * @param string[] $authorizations
     */
    public function __construct(string $url, string $status, \DateTime $expires, array $identifiers, array $authorizations, string $finalize);
    public function getUrl(): string;
    /**
     * @return string[]
     */
    public function getAuthorizations(): array;
    public function getStatus(): string;
    public function getExpires(): \DateTime;
    /**
     * @return string[]
     */
    public function getIdentifiers(): array;
    public function getFinalize(): string;
}
