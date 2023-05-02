<?php

namespace TomCan\AcmeClient;

use TomCan\AcmeClient\Interfaces\AccountInterface;
use TomCan\AcmeClient\Interfaces\AuthorizationInterface;
use TomCan\AcmeClient\Interfaces\ChallengeInterface;
use TomCan\AcmeClient\Interfaces\OrderInterface;
use TomCan\AcmeClient\Objects\Order;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class AcmeClient
{
    private HttpClientInterface $httpClient;
    private string $directoryUrl;
    private ?array $directory = null;
    private ?string $nonce = null;

    private AccountInterface $account;
    private $accountKey;
    private array $accountKeyDetails;
    private string $accountKeyThumbprint;

    private array $classes;

    public function __construct(
        HttpClientInterface $httpClient,
        string $directoryUrl = 'https://acme-v02.api.letsencrypt.org/directory',
        array $classes = []
    )
    {
        $this->httpClient = $httpClient;
        $this->directoryUrl = $directoryUrl;

        $this->classes = [];
        foreach (['account', 'authorization', 'challenge', 'order'] as $type) {
            if (isset($classes[$type])) {
                $interface = 'TomCan\AcmeClient\Interfaces\\'.ucfirst($type).'Interface';
                $implemented = @class_implements($classes[$type]);
                if (!is_array($implemented) || !in_array($interface, $implemented)) {
                    throw new \Exception('Class '.$classes[$type].' does not implement '.$interface);
                } else {
                    $this->classes[$type] = $classes[$type];
                }
            } else {
                // default to our objects
                $this->classes[$type] = 'TomCan\AcmeClient\Objects\\'.ucfirst($type);
            }
        }
    }

    private function getDirectory(string $method): string
    {
        if (null === $this->directory) {
            $response = $this->httpClient->request('GET', $this->directoryUrl);
            $this->directory = json_decode($response->getContent(), true);
        }

        return $this->directory[$method];
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private function makeRequest(string $method, string $url, ?array $payload = null)
    {
        if ('POST' == $method) {
            if (null === $this->nonce) {
                // get initial/new nonce
                $this->makeRequest('HEAD', $this->getDirectory('newNonce'));
                if (null === $this->nonce) {
                    throw new \Exception('Unable to get nonce');
                }
            }
            if ($url == $this->getDirectory('newAccount')) {
                // newAccount used JWS JWK
                $response = $this->httpClient->request($method, $url, [
                    'headers' => [
                        'Content-Type' => 'application/jose+json',
                    ],
                    'json' => $this->signPayloadJWK($payload, $url),
                ]);
            } else {
                // the other calls use JWS KID
                $response = $this->httpClient->request($method, $url, [
                    'headers' => [
                        'Content-Type' => 'application/jose+json',
                    ],
                    'json' => $this->signPayloadKID($payload, $url),
                ]);
            }
        } else {
            $response = $this->httpClient->request($method, $url);
        }

        // extract new nonce from response and save for next request
        $headers = $response->getHeaders();
        $this->nonce = $headers['replay-nonce'][0];

        var_dump($response->getStatusCode(), $response->getHeaders(), $response->getContent());

        return $response;
    }

    public function getAccount(AccountInterface $account): AccountInterface
    {
        $this->account = $account;
        // Open key and extract all required information
        $this->accountKey = openssl_pkey_get_private($this->account->getKey());
        $this->accountKeyDetails = openssl_pkey_get_details($this->accountKey);
        // SHA-256 hash of JWK Thumbprint
        $this->accountKeyThumbprint = $this->base64UrlEncode(hash('sha256', json_encode([
            'e'   => $this->base64UrlEncode($this->accountKeyDetails['rsa']['e']),
            'kty' => 'RSA',
            'n'   => $this->base64UrlEncode($this->accountKeyDetails['rsa']['n']),
        ]), true));

        $response = $this->makeRequest(
            'POST',
            $this->getDirectory('newAccount'),
            [
                'termsOfServiceAgreed' => true,
                'contact' => [
                    'mailto:'.$account->getEmail(),
                ],
            ]
        );

        if (null === $account->getUrl()) {
            // new account, create new account object from response
            $this->account->setUrl($response->getHeaders()['location'][0]);
        }

        return $this->account;
    }

    public function createOrder(array $domains): OrderInterface
    {
        $items = [];
        foreach ($domains as $domain) {
            $items[] = [
                    'type'  => 'dns',
                    'value' => $domain,
            ];
        }

        $response = $this->makeRequest(
            'POST',
            $this->getDirectory('newOrder'),
            ['identifiers' => $items],
        );

        $headers = $response->getHeaders();
        $data = json_decode($response->getContent());

        var_dump(
            $response->getStatusCode(),
            $headers,
            $data,
        );

        $className = $this->classes['order'];
        return new $className(
            $headers['location'][0],
            $data->status,
            new \DateTime($data->expires),
            $data->identifiers,
            $data->authorizations,
            $data->finalize,
        );
    }

    public function authorize(OrderInterface $order): array
    {
        $authorizationClass = $this->classes['authorization'];
        $challengeClass = $this->classes['challenge'];
        $authorizations = [];
        foreach ($order->getAuthorizations() as $url) {
            $response = $this->makeRequest(
                'POST',
                $url,
                null
            );
            $data = json_decode($response->getContent());
            $challenges = [];
            foreach ($data->challenges as $challenge) {
                $challenges[] = new $challengeClass(
                    $challenge->type,
                    $challenge->status,
                    $challenge->url,
                    $challenge->token,
                    'http-01' == $challenge->type ? $challenge->token.'.'.$this->accountKeyThumbprint : $this->accountKeyThumbprint
                );
            }
            // string $url, string $identifier, string $status, \DateTime $expires, array $challenges
            $authorization = new $authorizationClass(
                $url,
                $data->identifier->value,
                $data->status,
                \DateTime::createFromFormat('Y-m-d\TH:i:s', $data->expires),
                $challenges
            );
            $authorizations[] = $authorization;
        }

        return $authorizations;
    }

    /**
     * START OF JWK functions
     */

    private function signPayloadJWK($payload, $url): array
    {
        $payload = is_array($payload) ? str_replace('\\/', '/', json_encode($payload)) : '';
        $payload = $this->base64UrlEncode($payload);
        $protected = $this->base64UrlEncode(json_encode($this->getJWKEnvelope($url)));

        if (false === openssl_sign($protected.'.'.$payload, $signature, $this->accountKey, "SHA256")) {
            throw new \Exception('Could not generate signature');
        }

        return [
            'protected' => $protected,
            'payload'   => $payload,
            'signature' => $this->base64UrlEncode($signature),
        ];
    }

    private function getJWKEnvelope(string $url): array
    {
        return [
            'alg'   => 'RS256',
            'jwk'   => [
                'e'   => $this->base64UrlEncode($this->accountKeyDetails['rsa']['e']),
                'kty' => 'RSA',
                'n'   => $this->base64UrlEncode($this->accountKeyDetails['rsa']['n']),
            ],
            'nonce' => $this->nonce,
            'url'   => $url
        ];
    }

    private function signPayloadKID($payload, $url): array
    {
        $payload = is_array($payload) ? str_replace('\\/', '/', json_encode($payload)) : '';
        $payload = $this->base64UrlEncode($payload);
        $protected = $this->base64UrlEncode(json_encode($this->getKIDEnvelope($url)));

        $result = openssl_sign($protected . '.' . $payload, $signature, $this->accountKey, "SHA256");
        if (false === $result) {
            throw new \Exception('Could not generate signature');
        }

        return [
            'protected' => $protected,
            'payload'   => $payload,
            'signature' => $this->base64UrlEncode($signature),
        ];
    }

    private function getKIDEnvelope(string $url): array
    {
        return [
            "alg"   => "RS256",
            "kid"   => $this->account->getUrl(),
            "nonce" => $this->nonce,
            "url"   => $url
        ];
    }

    /**
     * END OF JWK functions
     */
}