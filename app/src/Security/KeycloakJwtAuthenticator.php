<?php

namespace App\Security;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\InMemoryUser;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

class KeycloakJwtAuthenticator extends AbstractAuthenticator
{
    private string $issuer;
    private string $audience;
    private string $publicKeyPem;

    public function __construct(string $issuer, string $audience, string $publicKey)
    {
        $this->issuer = $issuer;
        $this->audience = $audience;
        $this->publicKeyPem = $this->normalizePublicKey($publicKey);
        JWT::$leeway = 60; // 1 minute leeway for nbf, iat, exp
    }

    public function supports(Request $request): ?bool
    {
        // file_put_contents(
        //     '/tmp/keycloak_auth.log',
        //     sprintf(
        //         "[%s] supports? path=%s, auth=%s\n",
        //         date('c'),
        //         $request->getPathInfo(),
        //         $request->headers->get('Authorization', 'NONE')
        //     ),
        //     FILE_APPEND
        // );

        return $request->headers->has('Authorization');
    }

    public function authenticate(Request $request): Passport
    {
        $authHeader = $request->headers->get('Authorization', '');
        if (!str_starts_with($authHeader, 'Bearer ')) {
            throw new AuthenticationException('No Bearer token');
        }

        $token = substr($authHeader, 7);
        $decoded = $this->decodeJwt($token);

        $username = $decoded->preferred_username ?? $decoded->sub ?? null;
        if (!$username) {
            throw new AuthenticationException('No username in token');
        }

        return new SelfValidatingPassport(
            new UserBadge($username, function () use ($username, $decoded) {
                $roles = [];

                // realm roles
                if (isset($decoded->realm_access->roles)) {
                    $roles = array_merge(
                        $roles,
                        array_map(
                            static fn(string $r) => 'ROLE_' . strtoupper($r),
                            $decoded->realm_access->roles
                        )
                    );
                }

                // client roles (np. app-api)
                if (isset($decoded->resource_access->{'app-front'}->roles)) {
                    $roles = array_merge(
                        $roles,
                        array_map(
                            static fn(string $r) => 'ROLE_' . strtoupper($r),
                            $decoded->resource_access->{'app-front'}->roles
                        )
                    );
                }

                $roles = array_values(array_unique($roles));


                return new InMemoryUser($username, '', $roles);
            })
        );
    }

    private function decodeJwt(string $token)
    {
        try {
            $decoded = JWT::decode($token, new Key($this->publicKeyPem, 'RS256'));
        } catch (\Throwable $e) {
            // file_put_contents(
            //     '/tmp/keycloak_auth.log',
            //     sprintf("[%s] decode error: %s\n", date('c'), $e->getMessage()),
            //     FILE_APPEND
            // );
            throw new AuthenticationException('Invalid token: ' . $e->getMessage(), 0, $e);
        }

        if (($decoded->iss ?? null) !== $this->issuer) {
            throw new AuthenticationException('Invalid issuer');
        }

        $aud = (array) ($decoded->aud ?? []);
        if (!in_array($this->audience, $aud, true)) {
            throw new AuthenticationException('Invalid audience');
        }

        return $decoded;
    }

    /**
     * Przyjmuje:
     *  - goły klucz z Keycloak (MIIBIjANBgkq...)
     *  - lub pełny PEM z BEGIN/END
     * i zwraca poprawny PEM do OpenSSL.
     */
    private function normalizePublicKey(string $key): string
    {
        $key = trim($key);

        if (str_contains($key, 'BEGIN PUBLIC KEY')) {
            // Zakładamy, że już jest poprawnym PEM-em
            return $key;
        }

        // To jest goły base64 z Keycloak -> owijamy w PEM
        $wrapped = chunk_split($key, 64, "\n");

        return "-----BEGIN PUBLIC KEY-----\n" .
            $wrapped .
            "-----END PUBLIC KEY-----\n";
    }

    public function onAuthenticationSuccess(Request $request, $token, string $firewallName): ?Response
    {
        return null; // lecimy do kontrolera
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return new JsonResponse(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
    }
}
