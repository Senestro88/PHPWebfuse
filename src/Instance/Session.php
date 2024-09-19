<?php

namespace PHPWebfuse\Instance;

use \PHPWebfuse\Utils;

/**
 * @author Senestro
 */
class Session
{
    // PRIVATE VARIABLES

    private string $samesite = "Lax";
    private bool $httponly = true;
    private string $path = "/";

    // PUBLIC VARIABLES

    /**
     * @var int The maximum days for the session to expire and reset
     */
    public int $maxDays = 7;

    // PUBLIC METHODS

    /**
     * Construct new Session instance
     * @param int $maxDays The maximum days for the session to expire and reset
     */
    public function __construct(int $maxDays = 7)
    {
        $this->maxDays = $maxDays;
    }

    /**
     * Get session domain
     * @return string
     */
    public function getDomain(): string
    {
        return "." . Utils::getDomain(getenv('HTTP_HOST'));
    }

    /**
     * Set the session path
     * @param string $string
     * @return void
     */
    public function setPath(string $string): void
    {
        $this->path = $string;
    }

    /**
     * Determine if session is in secured context
     * @return bool
     */
    public function isInSecuredContext(): bool
    {
        return (getenv('HTTPS') == 'on' || getenv('HTTPS') == '1' || getenv('SERVER_PORT') == '443') ? true : false;
    }

    /**
     * Set the httponly value
     * @param bool $bool
     * @return void
     */
    public function setHttpOnly(bool $bool): void
    {
        $this->httponly = $bool;
    }

    /**
     * Set the samesite value
     * @param string $string The value
     * @return void
     */
    public function setSameSite(string $string): void
    {
        $this->samesite = $string;
    }

    /**
     *  Start a session
     *  @return bool
     */
    public function startSession(): bool
    {
        $status = @session_status();
        switch($status) {
            case PHP_SESSION_DISABLED:
                throw new \Exception("Sessions are disabled");
                break;
            case PHP_SESSION_NONE:
                if($this->setParameters()) {
                    return $this->start();
                }
                break;
            case PHP_SESSION_ACTIVE:
                if(!$this->isValid() && $this->stopSession() && $this->setParameters()) {
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
    public function stopSession(): bool
    {
        if(isset($_SESSION)) {
            foreach($_SESSION as $key => $value) {
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
    private function isValid(): bool
    {
        $params = session_get_cookie_params();
        return (
            (int) $params['lifetime'] == 0 &&
            (string) $params['path'] == $this->path &&
            (string) $params['domain'] == $this->getDomain() &&
            (bool) $params['secure'] == $this->isInSecuredContext() &&
            (bool) $params['httponly'] == $this->httponly &&
            (string) $params['samesite'] == $this->samesite
        );
    }

    /**
     * Set the session expiration time
     * @return void
     */
    private function setExpirationTime(): void
    {
        $expirationDays = (int) $this->maxDays;
        $_SESSION['session-expires'] = strtotime('+ ' . $expirationDays . ' ' . ($expirationDays > 1 ? "days" : "day"));
    }

    /**
     * revalidate if session has expired
     * @return bool
     */
    private function revalidateElapsedTime(): bool
    {
        if(isset($_SESSION['session-expires']) && !empty($_SESSION['session-expires'])) {
            $sessionExpires = (int) $_SESSION['session-expires'];
            if($sessionExpires <= time()) {
                session_gc();
                $this->stopSession();
                $newId = session_create_id(substr(md5(time()), 0, 10));
                if($newId !== false && session_commit() === true && session_id($newId) !== false) {
                    if(Utils::isTrue(session_start())) {
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
    private function start(): bool
    {
        if(Utils::isTrue(session_start())) {
            $this->revalidateElapsedTime();
            return true;
        }
        return false;
    }

    /**
     * Set the session parameters
     * @return bool
     */
    private function setParameters(): bool
    {
        $lifetime = 0;
        $path = $this->path;
        $domain = $this->getDomain();
        $secure = $this->isInSecuredContext();
        $httponly = $this->httponly;
        $samesite = $this->samesite;
        if(PHP_VERSION_ID < 70300) {
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
