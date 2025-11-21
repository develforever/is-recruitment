<?php

namespace App\Security;

use Symfony\Component\Security\Core\User\EquatableInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class InMemoryUser implements UserInterface, PasswordAuthenticatedUserInterface, EquatableInterface, \Stringable
{
    private string $username;

    public function __construct(
        ?string $username,
        private ?string $password,
        private array $roles = [],
        private bool $enabled = true,
        private array $attributes = [],
    ) {
        if ('' === $username || null === $username) {
            throw new \InvalidArgumentException('The username cannot be empty.');
        }

        $this->username = $username;
    }
    public function __toString(): string
    {
        return $this->getUserIdentifier();
    }
    public function getRoles(): array
    {
        return $this->roles;
    }
    public function getPassword(): ?string
    {
        return $this->password;
    }
    /**
     * Returns the identifier for this user (e.g. its username or email address).
     * */

    public function getUserIdentifier(): string
    {
        return $this->username;
    }
    /**
     * Checks whether the user is enabled.
     *
     * Internally, if this method returns false, the authentication system
     * will throw a DisabledException and prevent login.
     *
     * @return bool true if the user is enabled, false otherwise
     *
     * @see DisabledException
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }
    /**
     * @deprecated since Symfony 7.3
     */
    #[\Deprecated(since: 'symfony/security-core 7.3')]
    public function isEqualTo(UserInterface $user): bool
    {
        if (!$user instanceof InMemoryUser) {
            return false;
        }

        if ($this->getPassword() !== $user->getPassword()) {
            return false;
        }

        if ($this->isEnabled() !== $user->isEnabled()) {
            return false;
        }

        if ($this->getUserIdentifier() !== $user->getUserIdentifier()) {
            return false;
        }

        if (count(array_diff($this->getRoles(), $user->getRoles())) > 0) {
            return false;
        }

        return true;
    }

    #[\Deprecated(since: 'symfony/security-core 7.3')]
    public function eraseCredentials(): void
    {
    }

    public function getAttribute(string $name): mixed
    {
        return $this->attributes[$name] ?? null;
    }
}
