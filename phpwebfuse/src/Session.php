<?php
namespace PHPWebFuse;
/**
 *
 */
class Session extends \PHPWebFuse\Methods {
	// PUBLIC VARIABLES

	// Session ID expiration days
	public $expirationDays = 7;

	// PUBLIC METHODS

	public function __construct(int $expirationDays = 7) {
		$this->expirationDays = $expirationDays;
	}

	/**
	 *  Get session domain 
	 */
	public function getSessionDomain(): string {return "." . parent::getDomain(getenv('HTTP_HOST'));}
	/**
	 *  Get session path 
	 */
	public function getSessionPath(): string {return "/";}
	/**
	 *  Determine if session is in secured context
	 */
	public function isInSecuredContext(): bool {return (getenv('HTTPS') == 'on' || getenv('HTTPS') == '1' || getenv('SERVER_PORT') == '443') ? true : false;}
	/**
	 *  Set the seeion to http only
	 */
	public function setHttpOnly(): bool {return true;}
	/**
	 *  Get the seeion samesite value
	 */
	public function getSessionSameSite(): string {return "Lax";}

	/**
	 *  Start a session
	 * 
	 *  @return bool
	 */
	public function startSession(): bool {
		$status = @session_status();
		switch ($status) {
		case PHP_SESSION_DISABLED:
			throw new \Exception("Sessions are disabled");
			break;
		case PHP_SESSION_NONE:
			if ($this->setSessionParameters()) {return $this->startTheSession();}
			break;
		case PHP_SESSION_ACTIVE:
			if (!$this->isSessionValid()) {if ($this->stopSession() && $this->setSessionParameters()) {return $this->startTheSession();}}
			return $this->startTheSession();
		}
		return $this->startTheSession();
	}

	/**
	 * Stop a session
	 */
	public function stopSession(): bool {
		if (isset($_SESSION)) {
			foreach ($_SESSION as $key => $value) {unset($_SESSION[$key]);}
		}
		return session_destroy();
	}

	// PRIVATE METHODS
	
	private function isSessionValid(): bool {
		$params = session_get_cookie_params();
		return (
			(int) $params['lifetime'] == 0 &&
			(string) $params['path'] == $this->getSessionPath() &&
			(string) $params['domain'] == $this->getSessionDomain() &&
			(bool) $params['secure'] == $this->isSessionSecured() &&
			(bool) $params['httponly'] == $this->isSessionOnHttpOnly() &&
			(string) $params['samesite'] == $this->getSessionSameSite()
		);
	}

	private function setSessionExpirationTime(): void {
		$expirationDays = (int) $this->expirationDays;
		$_SESSION['session-expires'] = strtotime('+ ' . $expirationDays . ' ' . ($expirationDays > 1 ? "days" : "day"));
	}

	private function revalidateSessionElapsedTime(): bool {
		if (isset($_SESSION['session-expires']) && !empty($_SESSION['session-expires'])) {
			$sessionExpires = (int) $_SESSION['session-expires'];
			if ($sessionExpires <= time()) {
				session_gc();
				$this->stopSession();
				$newId = session_create_id(substr(md5(time()), 0, 10));
				if ($newId !== false && session_commit() === true && session_id($newId) !== false) {
					if (parent::isTrue(session_start())) {
						$this->setSessionExpirationTime();
						return true;
					}
				}
			}
		} else { $this->setSessionExpirationTime();}
		return false;
	}

	private function startTheSession(): bool {
		if (parent::isTrue(session_start())) {
			$this->revalidateSessionElapsedTime();
			return true;
		}
		return false;
	}

	private function setSessionParameters(): bool {
		$lifetime = 0;
		$path = $this->getSessionPath();
		$domain = $this->getDomain();
		$secure = $this->isSessionSecured();
		$httponly = $this->isSessionOnHttpOnly();
		$samesite = $this->getSessionSameSite();
		if (PHP_VERSION_ID < 70300) {return session_set_cookie_params($lifetime, $path, $domain, $secure, $httponly);} else {
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