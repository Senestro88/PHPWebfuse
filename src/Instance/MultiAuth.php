<?php

namespace PHPWebfuse\Instance;

use \PHPWebfuse\Http;

/**
 * @author Senestro
 */
class MultiAuth
{
    // PRIVATE VARIABLES

    /**
     * @var int The length of the code
     */
    private int $passcodelength = 6;

    /**
     * @var int The length of the key when generating new key
     */
    private int $keylength = 10;

    /**
     * @var int
     */
    private $pinmodulo;

    /**
     * @var \DateTimeInterface
     */
    private ?\DateTimeInterface $time = null;

    /**
     * @var int The duration in seconds that the code is valid.
     */
    private int $codeperiod = 30;

    /**
     * @var int The length of a period to calculate periods since Unix epoch and cannot be larger than the $codeperiod
     */
    private int $periodsize = 30;

    // PUBLIC METHODS

    /**
     * Construct new MultiAuth instance
     * @param int $passcodelength The length of the code
     * @param int $keylength The length of the key when generating new key
     * @param \DateTimeInterface|null $time
     * @param int $codeperiod The duration in seconds that the code is valid.
     */
    public function __construct(int $passcodelength = 6, int $keylength = 10, ?\DateTimeInterface $time = null, int $codeperiod = 30)
    {
        $this->passcodelength = $passcodelength;
        $this->keylength = $keylength;
        $this->codeperiod = $codeperiod;
        $this->periodsize = $codeperiod < $this->periodsize ? $codeperiod : $this->periodsize;
        $this->pinmodulo = 10 ** $passcodelength;
        $this->time = $time ?? new \DateTimeImmutable();
    }

    /**
     * Generate a random key
     * @return string
     */
    public function generateKey(): string
    {
        return (new \PHPWebfuse\Instance\MultiAuth\FixedBitNotation(5, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567', true, true))->encode(random_bytes($this->keylength));
    }

    /**
     * Generate a base64 image
     * @param string $user
     * @param string $hostname
     * @param string $issuer
     * @param string $key
     * @return string
     */
    public function createBase64Image(string $user, string $hostname, string $issuer, string $key): string
    {
        return (new \PHPWebfuse\Instance\MultiAuth\QrCodeImage())->createBase64Image(sprintf('%s@%s', $user, $hostname), $key, $issuer);
    }

    /**
     * Output image to browser
     * @param string $user
     * @param string $hostname
     * @param string $issuer
     * @param string $key
     * @return void
     */
    public function createOuputImage(string $user, string $hostname, string $issuer, string $key): void
    {
        (new \PHPWebfuse\Instance\MultiAuth\QrCodeImage())->createOuputImage(sprintf('%s@%s', $user, $hostname), $key, $issuer);
    }

    /**
     * Generate a url for the base64 image
     * @param string $user
     * @param string $hostname
     * @param string $issuer
     * @param string $key
     * @return string
     */
    public function createImageUrl(string $user, string $hostname, string $issuer, string $key): string
    {
        return (new \PHPWebfuse\Instance\MultiAuth\QrCodeUrl())->generate(sprintf('%s@%s', $user, $hostname), $key, $issuer);
    }
    
    /**
     * Get the code
     * @param string $key
     * @param \DateTimeInterface|null $time
     * @return string
     */
    public function getCode(string $key, ?\DateTimeInterface $time = null): string
    {
        if (null === $time) {
            $time = $this->time;
        }
        if ($time instanceof \DateTimeInterface) {
            $timeForCode = floor($time->getTimestamp() / $this->periodsize);
        } else {
            $timeForCode = $time;
        }
        $notation = new \PHPWebfuse\Instance\MultiAuth\FixedBitNotation(5, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567', true, true);
        $key = $notation->decode($key);
        $timeForCode = str_pad(pack('N', $timeForCode), 8, \chr(0), \STR_PAD_LEFT);
        $hash = hash_hmac('sha1', $timeForCode, $key, true);
        $offset = \ord(substr($hash, -1));
        $offset &= 0xF;
        $truncatedHash = $this->hashToInt($hash, $offset) & 0x7FFFFFFF;
        return str_pad((string) ($truncatedHash % $this->pinmodulo), $this->passcodelength, '0', \STR_PAD_LEFT);
    }

    /**
     * Check if code is valid
     * @param string $key
     * @param string $code
     * @param int $discrepancy
     * @return bool
     *
     * Discrepancy is the factor of periodsize ($discrepancy * periodsize) allowed on either side of the
     * given codeperiod. For example, if a code with codeperiod = 60 is generated at 10:00:00, a discrepancy
     * of 1 will allow a periodsize of 30 seconds on either side of the codeperiod resulting in a valid code
     * from 09:59:30 to 10:00:29.
     * The result of each comparison is stored as a timestamp here instead of using a guard clause
     * (https://refactoring.com/catalog/replaceNestedConditionalWithGuardClauses.html). This is to implement
     * constant time comparison to make side-channel attacks harder. See
     * https://cryptocoding.net/index.php/Coding_rules#Compare_secret_strings_in_constant_time for details.
     * Each comparison uses hash_equals() instead of an operator to implement constant time equality comparison
     * for each code.
     */
    public function isCodeValid(string $key, string $code, int $discrepancy = 1): bool
    {
        $periods = floor($this->codeperiod / $this->periodsize);
        $result = 0;
        for ($i = -$discrepancy; $i < $periods + $discrepancy; ++$i) {
            $dateTime = new \DateTimeImmutable('@' . ($this->time->getTimestamp() - ($i * $this->periodsize)));
            $result = hash_equals($this->getCode($key, $dateTime), $code) ? $dateTime->getTimestamp() : $result;
        }
        return $result > 0;
    }

    // PRIVATE METHODS

    /**
     * Unpack $bytes from $start position
     * @param string $bytes
     * @param int $start
     * @return int
     */
    private function hashToInt(string $bytes, int $start): int
    {
        return unpack('N', substr(substr($bytes, $start), 0, 4))[1];
    }
}
