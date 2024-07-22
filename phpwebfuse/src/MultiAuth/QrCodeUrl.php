<?php
namespace PHPWebFuse\MultiAuth;
/**
 * Responsible for QR image url generation.
 *
 * @see http://goqr.me/api/
 * @see http://goqr.me/api/doc/
 * @see https://github.com/google/google-authenticator/wiki/Key-Uri-Format
 */
class QrCodeUrl {
	// PUBLIC METHODS
	
	public function __construct(){}

	/**
	 * Generates a URL that is used to show a QR code.
	 *
	 * Account names may not contain a double colon (:). Valid account name
	 * examples:
	 *  - "John.Doe@gmail.com"
	 *  - "John Doe"
	 *  - "John_Doe_976"
	 *
	 * The Issuer may not contain a double colon (:). The issuer is recommended
	 * to pass along. If used, it will also be appended before the accountName.
	 *
	 * The previous examples with the issuer "Acme inc" would result in label:
	 *  - "Acme inc:John.Doe@gmail.com"
	 *  - "Acme inc:John Doe"
	 *  - "Acme inc:John_Doe_976"
	 *
	 * The contents of the label, issuer and secret will be encoded to generate
	 * a valid URL.
	 *
	 * @param string $accountName The account name to show and identify
	 * @param string $secret The secret is the generated secret unique to that user
	 * @param string|null $issuer Where you log in to
	 */
	public function generate(string $accountName, string $secret, ?string $issuer = null): string {
		if ($accountName === "" || strpos($accountName, ':') !== false) {throw \PHPWebFuse\MultiAuth\QrException::InvalidAccountName($accountName);}
        if ($secret === "") {throw \PHPWebFuse\MultiAuth\QrException::InvalidSecret();}
        $label = $accountName;
        $content = 'otpauth://totp/%s?secret=%s';
        if ($issuer !== null) {
            if ($issuer === "" || strpos($issuer, ':') !== false) {throw \PHPWebFuse\MultiAuth\QrException::InvalidIssuer($issuer);}
            // Use both the issuer parameter and label prefix as recommended by Google for BC reasons
            $label = $issuer . ':' . $label;
            $content .= '&issuer=%s';
        }
		$content = rawurlencode(sprintf($content, $label, $secret, $issuer));
		return sprintf('https://api.qrserver.com/v1/create-qr-code/?size=%1$dx%1$d&data=%2$s&ecc=M', 212, $content);
	}
}