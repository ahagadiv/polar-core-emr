<?php

/*
 * SessionConfigurationBuilder.php
 * @package openemr
 * @link      http://www.open-emr.org
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

namespace OpenEMR\Common\Session;

class SessionConfigurationBuilder
{
    private array $config = [];

    public function __construct()
    {
        // Set default values that are common across all session types
        $this->config = [
            'gc_maxlifetime' => SessionUtil::DEFAULT_GC_MAXLIFETIME,
            'cookie_lifetime' => SessionUtil::DEFAULT_GC_MAXLIFETIME, // Set cookie to match session lifetime
            'use_strict_mode' => true,
            'use_cookies' => true,
            'use_only_cookies' => true,
            'cookie_samesite' => 'Lax',
            'cookie_secure' => false,
            'cookie_httponly' => true
        ];

        // Add PHP version-specific settings
        if (version_compare(phpversion(), '8.4.0', '<')) {
            $this->config['sid_bits_per_character'] = 6;
            $this->config['sid_length'] = 48;
        }
    }

    public function setName(string $name): self
    {
        $this->config['name'] = $name;
        return $this;
    }

    public function setCookiePath(string $path): self
    {
        $this->config['cookie_path'] = $path;
        return $this;
    }

    public function setCookieSameSite(string $sameSite): self
    {
        $this->config['cookie_samesite'] = $sameSite;
        return $this;
    }

    public function setCookieSecure(bool $secure): self
    {
        $this->config['cookie_secure'] = $secure;
        return $this;
    }

    public function setCookieHttpOnly(bool $httpOnly): self
    {
        $this->config['cookie_httponly'] = $httpOnly;
        return $this;
    }

    public function setReadOnly(bool $readOnly): self
    {
        $this->config['read_and_close'] = $readOnly;
        return $this;
    }

    public function build(): array
    {
        return $this->config;
    }

    // Preset configurations for different session types
    public static function forCore(string $webRoot = '', bool $readOnly = true): array
    {
        return (new self())
            ->setName(SessionUtil::CORE_SESSION_ID)
            ->setCookiePath('/') // POLAR Healthcare: Use root path for session cookies
            ->setCookieHttpOnly(false)
            ->setReadOnly($readOnly)
            ->build();
    }

    public static function forOAuth(string $webRoot = ''): array
    {
        return (new self())
            ->setName(SessionUtil::OAUTH_SESSION_ID)
            ->setCookiePath('/') // POLAR Healthcare: Use root path for OAuth session cookies
            ->setCookieSameSite('None')
            ->setCookieSecure(true)
            ->build();
    }

    public static function forApi(string $webRoot = ''): array
    {
        return (new self())
            ->setName(SessionUtil::API_SESSION_ID)
            ->setCookiePath('/') // POLAR Healthcare: Use root path for API session cookies
            ->setCookieSecure(true)
            ->build();
    }

    public static function forPortal(): array
    {
        return (new self())
            ->setName('PortalOpenEMR')
            ->build();
    }

    public static function forSetup(): array
    {
        return (new self())
            ->setName('setupOpenEMR')
            ->build();
    }
}
