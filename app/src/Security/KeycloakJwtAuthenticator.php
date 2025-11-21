<?php

namespace App\Security;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Symfony\Component\HttpClient\HttpClient;
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
    private array $jwks;
    private string $issuer;
    private string $audience;

    public function __construct(string $jwksUrl, string $issuer, string $audience)
    {
        $this->issuer = $issuer;
        $this->audience = $audience;

        $client = HttpClient::create();
        $response = $client->request('GET', $jwksUrl);
        $this->jwks = $response->toArray();
    }

    public function supports(Request $request): ?bool
    {
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

                if (isset($decoded->realm_access->roles)) {
                    $roles = array_map(
                        static fn (string $r) => 'ROLE_' . strtoupper($r),
                        $decoded->realm_access->roles
                    );
                }

                return new InMemoryUser($username, null, $roles);
            })
        );
    }

    private function decodeJwt(string $token)
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            throw new AuthenticationException('Invalid JWT structure');
        }

        $header = json_decode(base64_decode($parts[0]), true, 512, JSON_THROW_ON_ERROR);
        $kid = $header['kid'] ?? null;

        $keyData = null;
        foreach ($this->jwks['keys'] as $jwk) {
            if (($jwk['kid'] ?? null) === $kid) {
                $keyData = $jwk;
                break;
            }
        }

        if (!$keyData) {
            throw new AuthenticationException('Unknown key id');
        }

        $publicKey = $this->jwkToPem($keyData);
        $decoded = JWT::decode($token, new Key($publicKey, 'RS256'));

        if ($decoded->iss !== $this->issuer) {
            throw new AuthenticationException('Invalid issuer');
        }

        $aud = (array) $decoded->aud;
        if (!in_array($this->audience, $aud, true)) {
            throw new AuthenticationException('Invalid audience');
        }

        return $decoded;
    }

    private function jwkToPem(array $jwk): string
    {
        $n = strtr($jwk['n'], '-_', '+/');
        $e = strtr($jwk['e'], '-_', '+/');

        $modulus = base64_decode($n);
        $exponent = base64_decode($e);

        $components = [
            'modulus'        => pack('Ca*a*', 2, $this->encodeLength(strlen($modulus)), $modulus),
            'publicExponent' => pack('Ca*a*', 2, $this->encodeLength(strlen($exponent)), $exponent),
        ];

        $rsaPublicKey = pack(
            'Ca*a*a*',
            48,
            $this->encodeLength(strlen($components['modulus'] . $components['publicExponent'])),
            $components['modulus'],
            $components['publicExponent']
        );

        $publicKeyInfo = pack(
            'Ca*a*Ca*a*',
            48,
            $this->encodeLength(strlen("\x30\x0D\x06\x09\x2A\x86\x48\x86\xF7\x0D\x01\x01\x01\x05\x00") + strlen($rsaPublicKey) + 2),
            "\x30\x0D\x06\x09\x2A\x86\x48\x86\xF7\x0D\x01\x01\x01\x05\x00",
            3,
            $this->encodeLength(strlen($rsaPublicKey) + 1),
            "\x00" . $rsaPublicKey
        );

        return "-----BEGIN PUBLIC KEY-----\n" .
            chunk_split(base64_encode($publicKeyInfo), 64, "\n") .
            "-----END PUBLIC KEY-----\n";
    }

    private function encodeLength(int $length): string
    {
        if ($length <= 0x7F) {
            return chr($length);
        }

        $temp = ltrim(pack('N', $length), "\x00");
        return chr(0x80 | strlen($temp)) . $temp;
    }

    public function onAuthenticationSuccess(Request $request, $token, string $firewallName): ?Response
    {
        // null = przepuszczamy dalej do kontrolera
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return new JsonResponse(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
    }
}
