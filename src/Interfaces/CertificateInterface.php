<?php

namespace TomCan\AcmeClient\Interfaces;

interface CertificateInterface
{
    public function __construct(string $key, string $csr, string $certificate);
    public function getKey(): string;
    public function getCsr(): string;
    public function getCertificate(): string;
}
