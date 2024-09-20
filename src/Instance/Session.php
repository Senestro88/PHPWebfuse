<?php

namespace PHPWebfuse\Instance;

use \PHPWebfuse\Utils;

/**
 * @author Senestro
 */
class Session {
    // PRIVATE VARIABLES

    /**
     * Same-site policy for the session cookie
     * @var string
     */
    private string $samesite = "Lax";

    /**
     * HTTP-only flag for the session cookie
     * @var bool
     */
    private bool $httponly = true;

    /**
     * Path for the session cookie
     * @var string
     */
    private string $path = "/";

    /**
     * Domain for the session cookie
     * @var string
     */
    private string $domain;

    // PUBLIC VARIABLES

    /**
     * Maximum days for the session to expire and reset
     * @var int
     */
    public int $maxDays = 7;

    // PUBLIC METHODS

    /**
     * Construct new Session instance
     * @param int $maxDays The maximum days for the session to expire and reset
     */
    public function __construct(int $maxDays = 7) {
        $this->maxDays = $maxDays;
        $this->domain = getenv('HTTP_HOST'); // Set domain from environment variable
    }

    /**
     * Get session domain
     * @return string
     */
    public function getDomain(): string {
        return $this->domain;
    }

    /**
     * Set session domain
     * @param string $domain
     * @return void
     */
    public function setDomain(string $domain): void {
        $this->domain = $domain;
    }

    /**
     * Set session path
     * @param string $path
     * @return void
     */
    public function setPath(string $path): void {
        $this->path = $path;
    }

    /**
     * Get session path
     * @return string
     */
    public function getPath(): string {
        return $this->path;
    }

    /**
     * Determine if session is in secured context
     * @return bool
     */
    public function inSecuredContext(): bool {
        return (getenv('HTTPS') == 'on' || getenv('HTTPS') == '1' || getenv('SERVER_PORT') == '443');
    }

    /**
     * Set HTTP-only flag
     * @param bool $httponly
     * @return void
     */
    public function setHttpOnly(bool $httponly): void {
        $this->httponly = $httponly;
    }

    /**
     * Get HTTP-only flag
     * @return bool
     */
    public function isHttpOnly(): bool {
        return $this->httponly;
    }

    /**
     * Set same-site policy
     * @param string $samesite
     * @return void
     */
    public function setSameSite(string $samesite): void {
        $this->samesite = $samesite;
    }

    /**
     * Get same-site policy
     * @return string
     */
    public function getSameSite(): string {
        return $this->samesite;
    }

    /**
     * Start a session
     * @return bool
     */
    public function startSession(): bool {
        $status = @session_status();
        switch ($status) {
            case PHP_SESSION_DISABLED:
                throw new \Exception("Sessions are disabled");
                break;
            case PHP_SESSION_NONE:
                if ($this->setParameters()) {
                    return $this->start();
                }
                break;
            case PHP_SESSION_ACTIVE:
                if (!$this->isValid() && $this->stopSession() && $this->setParameters()) {
                    return $this->start();
                }
                return $this->start();
        }
        return $this->start();
    }

    /**
     * Stop a session
     * @return bool
     */
    public function stopSession(): bool {
        if (isset($_SESSION)) {
            foreach ($_SESSION as $key => $value) {
                unset($_SESSION[$key]);
            }
        }
        return session_destroy();
    }

    // PRIVATE METHODS

    /**
     * Check if session is valid
     * @return bool
     */
    private function isValid(): bool {
        $params = session_get_cookie_params();
        return (
            (int) $params['lifetime'] == 0 &&
            (string) $params['path'] == $this->path &&
            (string) $params['domain'] == $this->getDomain() &&
            (bool) $params['secure'] == $this->inSecuredContext() &&
            (bool) $params['httponly'] == $this->httponly &&
            (string) $params['samesite'] == $this->samesite
        );
    }

    /**
     * Set session expiration time
     * @return void
     */
    private function setExpirationTime(): void {
        $expirationDays = (int) $this->maxDays;
        $_SESSION['session-expires'] = strtotime('+ ' . $expirationDays . ' ' . ($expirationDays > 1 ? "days" : "day"));
    }

    /**
     * Revalidate if session has expired
     * @return bool
     */
    private function revalidateElapsedTime(): bool {
        if (isset($_SESSION['session-expires']) && !empty($_SESSION['session-expires'])) {
            $sessionExpires = (int) $_SESSION['session-expires'];
            if ($sessionExpires <= time()) {
                session_gc();
                $this->stopSession();
                $newId = session_create_id(substr(md5(time()), 0, 10));
                if ($newId !== false && session_commit() === true && session_id($newId) !== false) {
                    if (Utils::isTrue(session_start())) {
                        $this->setExpirationTime();
                        return true;
                    }
                }
            }
        } else {
            $this->setExpirationTime();
        }
        return false;
    }

    /**
     * Start the session
     * @return bool
     */
    private function start(): bool {
        if (Utils::isTrue(session_start())) {
            $this->revalidateElapsedTime();
            return true;
        }
        return false;
    }

    /**
     * Set session parameters
     * @return bool
     */
    private function setParameters(): bool {
        $lifetime = 0;
        $path = $this->path;
        $domain = $this->getDomain();
        $secure = $this->inSecuredContext();
        $httponly = $this->httponly;
        $samesite = $this->samesite;
        if (PHP_VERSION_ID < 70300) {
            return session_set_cookie_params($lifetime, $path, $domain, $secure, $httponly);
        } else {
            return session_set_cookie_params([
                'lifetime' => $lifetime,
                'path' => $path,
                'domain' => $domain,
                'secure' => $secure,
                'httponly' => $httponly,
                'samesite' => $samesite,
            ]);
        }
    }
}
