<?php

namespace TomCan\AcmeClient\Objects;

use TomCan\AcmeClient\Interfaces\OrderInterface;

class Order implements OrderInterface
{
    private string $url;
    private string $status;
    private \DateTime $expires;
    /**
     * @var mixed[]
     */
    private array $identifiers;
    /**
     * @var string[]
     */
    private array $authorizations;
    private string $finalize;

    /**
     * @param string $url
     * @param string $status
     * @param \DateTime $expires
     * @param mixed[] $identifiers
     * @param string[] $authorizations
     * @param string $finalize
     */
    public function __construct(string $url, string $status, \DateTime $expires, array $identifiers, array $authorizations, string $finalize)
    {
        $this->url = $url;
        $this->status = $status;
        $this->expires = $expires;
        $this->identifiers = $identifiers;
        $this->authorizations = $authorizations;
        $this->finalize = $finalize;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getExpires(): \DateTime
    {
        return $this->expires;
    }

    /**
     * @return mixed[]
     */
    public function getIdentifiers(): array
    {
        return $this->identifiers;
    }

    /**
     * @return string[]
     */
    public function getAuthorizations(): array
    {
        return $this->authorizations;
    }

    public function getFinalize(): string
    {
        return $this->finalize;
    }

    public function getIdFromUrl(): string
    {
        return substr($this->url, strrpos($this->url, '/') + 1);
    }
}
