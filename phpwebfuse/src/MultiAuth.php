<?php
namespace PHPWebFuse;
/**
 *
 */
class MultiAuth extends \PHPWebFuse\Methods {
	// PRIVATE VARIABLES

	/**
	 * @var int
	 */
	private $passCodeLength = 6;

	/**
	 * @var int
	 */
	private $keyLength = 10;

	/**
	 * @var int
	 */
	private $pinModulo;

	/**
	 * @var \DateTimeInterface
	 */
	private $instanceTime = null;

	/**
	 * @var int
	 */
	private $codePeriod = 30;

	/**
	 * @var int
	 */
	private $periodSize = 30;

	// PUBLIC METHODS

	/**
	 * @param int $passCodeLength
	 * @param int $keyLength - The length of the key when generating new key
	 * @param ?\DateTimeInterface $instanceTime
	 * @param int $codePeriod - The duration in seconds that the code is valid.
	 */
	public function __construct(int $passCodeLength = 6, int $keyLength = 10, ?\DateTimeInterface $instanceTime = null, int $codePeriod = 30) {
		// $codePeriod is the duration in seconds that the code is valid.
		// $periodSize is the length of a period to calculate periods since Unix epoch.
		// $periodSize cannot be larger than the codePeriod.
		$this->passCodeLength = $passCodeLength;
		$this->keyLength = $keyLength;
		$this->codePeriod = $codePeriod;
		$this->periodSize = $codePeriod < $this->periodSize ? $codePeriod : $this->periodSize;
		$this->pinModulo = 10 ** $passCodeLength;
		$this->instanceTime = $instanceTime ?? new \DateTimeImmutable();
		$srcDirname = parent::INSERT_DIR_SEPARATOR(PHPWebFuse['directories']['src']);
		$dirname = $srcDirname . "MultiAuth" . DIRECTORY_SEPARATOR;
		$scandir = parent::scanDir($dirname);
		if (parent::isNotEmptyArray($scandir)) {
			foreach ($scandir as $absolutePath) {
				if (parent::endsWith(".php", strtolower($absolutePath))) {
					$os = strtolower(PHPWebFuse['os']);
					if (($os == "unix" OR $os == "linux") OR $os == "unknown") {
						$exploded = array_filter(explode(DIRECTORY_SEPARATOR, $absolutePath));
						$absolutePath = DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $exploded);
					}
					if (is_file($absolutePath) && is_readable($absolutePath)) {require_once $absolutePath;}
					require_once $absolutePath;
				}
			}
		}
	}

	/**
	 * Generate a random key
	 */
	public function generateKey() : string {
		return (new \PHPWebFuse\MultiAuth\FixedBitNotation(5, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567', true, true))->encode(random_bytes($this->keyLength));
	}

	/**
	 * Generate a base64 image
	 *
	 * @param string $user
	 * @param string $hostname
	 * @param string $issuer
	 * @param string $key
	 *
	 * @return string
	 */
	public function createBase64Image(string $user, string $hostname, string $issuer, string $key): string {
		return (new \PHPWebFuse\MultiAuth\QrCodeImage())->createBase64Image(sprintf('%s@%s', $user, $hostname), $key, $issuer);
	}

	/**
	 * Output image to browser
	 *
	 * @param string $user
	 * @param string $hostname
	 * @param string $issuer
	 * @param string $key
	 *
	 * @return void
	 */
	public function createOuputImage(string $user, string $hostname, string $issuer, string $key): void {
		(new \PHPWebFuse\MultiAuth\QrCodeImage())->createOuputImage(sprintf('%s@%s', $user, $hostname), $key, $issuer);
	}

	/**
	 * Generate a url for the base64 image
	 *
	 * @param string $user
	 * @param string $hostname
	 * @param string $issuer
	 * @param string $key
	 *
	 * @return string
	 */
	public function createImageUrl(string $user, string $hostname, string $issuer, string $key): string {
		return (new \PHPWebFuse\MultiAuth\QrCodeUrl())->generate(sprintf('%s@%s', $user, $hostname), $key, $issuer);
	}

	/**
	 * Get the code
	 *
	 * @param string $key
	 * @param ?\DateTimeInterface|null $time
	 *
	 * @return string
	 */
	public function getCode(string $key, ?\DateTimeInterface $time = null) : string {
		if (null === $time) {$time = $this->instanceTime;}
		if ($time instanceof \DateTimeInterface) {$timeForCode = floor($time->getTimestamp() / $this->periodSize);} else { $timeForCode = $time;}
		$base32 = new \PHPWebFuse\MultiAuth\FixedBitNotation(5, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567', true, true);
		$key = $base32->decode($key);
		$timeForCode = str_pad(pack('N', $timeForCode), 8, \chr(0), \STR_PAD_LEFT);
		$hash = hash_hmac('sha1', $timeForCode, $key, true);
		$offset = \ord(substr($hash, -1));
		$offset &= 0xF;
		$truncatedHash = $this->hashToInt($hash, $offset) & 0x7FFFFFFF;
		return str_pad((string) ($truncatedHash % $this->pinModulo), $this->passCodeLength, '0', \STR_PAD_LEFT);
	}

	/**
	 * Check if code is valid
	 *
	 * @param string $secret
	 * @param string $code
	 * @param int $discrepancy
	 *
	 * @return bool
	 *
	 * Discrepancy is the factor of periodSize ($discrepancy * $periodSize) allowed on either side of the
	 * given codePeriod. For example, if a code with codePeriod = 60 is generated at 10:00:00, a discrepancy
	 * of 1 will allow a periodSize of 30 seconds on either side of the codePeriod resulting in a valid code
	 * from 09:59:30 to 10:00:29.
	 *
	 * The result of each comparison is stored as a timestamp here instead of using a guard clause
	 * (https://refactoring.com/catalog/replaceNestedConditionalWithGuardClauses.html). This is to implement
	 * constant time comparison to make side-channel attacks harder. See
	 * https://cryptocoding.net/index.php/Coding_rules#Compare_secret_strings_in_constant_time for details.
	 * Each comparison uses hash_equals() instead of an operator to implement constant time equality comparison
	 * for each code.
	 */
	public function isCodeValid(string $secret, string $code, int $discrepancy = 1): bool {
		$periods = floor($this->codePeriod / $this->periodSize);
		$result = 0;
		for ($i = -$discrepancy; $i < $periods + $discrepancy; ++$i) {
			$dateTime = new \DateTimeImmutable('@' . ($this->instanceTime->getTimestamp() - ($i * $this->periodSize)));
			$result = hash_equals($this->getCode($secret, $dateTime), $code) ? $dateTime->getTimestamp() : $result;
		}
		return $result > 0;
	}

	// PRIVATE METHODS

	private function hashToInt(string $bytes, int $start): int {
		return unpack('N', substr(substr($bytes, $start), 0, 4))[1];
	}
}