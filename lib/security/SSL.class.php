<?php

/**
 * Provides SSL en/de-cryption
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @category Security
 */
class SSL
{
	/**
	 * Define ssl key types.
	 */
	const KEY_PUBLIC = "public";
	const KEY_PRIVATE = "private";
	const KEY_CERTIFICATE = "certificate";
	const KEY_CSR = "csr";

	/**
	 * The public key.
	 *
	 * @var string
	 */
	public $public_key = "";

	/**
	 * The private key.
	 *
	 * @var string
	 */
	private $private_key = "";

	/**
	 * The certificate.
	 *
	 * @var string
	 */
	private $certificate = "";

	/**
	 * The csr-request.
	 *
	 * @var string
	 */
	private $csr = "";

	/**
	 * Set the given $key of $type.
	 *
	 * $key can also be an array, if it is it must have all the key types included
	 * example:
	 * array(
	 * 		"public" => '...',
	 * 		"private" => '...',
	 * 		"certificate" => '...',
	 * 		"csr" => '...',
	 * )
	 *
	 * @param mixed $key
	 *   In single mode the key, can also be an array with all information.
	 * @param string $type
	 *   A key type, use one of SSL::KEY_*
	 *   Only optional if $key is an array which holds all needed keys
	 *   (optional, default = '')
	 */
	public function set_keys($key, $type = '') {

		// If $key is an array.
		if (is_array($key)) {

			// Check if all key types provided, if not return.
			if (!isset($key[self::KEY_PRIVATE]) || !isset($key[self::KEY_PUBLIC]) || !isset($key[self::KEY_CERTIFICATE]) || !isset($key[self::KEY_CSR])) {
				return;
			}

			// Set the keys.
			$this->private_key = $key[self::KEY_PRIVATE];
			$this->public_key = $key[self::KEY_PUBLIC];
			$this->certificate = $key[self::KEY_CERTIFICATE];
			$this->csr = $key[self::KEY_CSR];
		}

		// Set the key based up on the given key_type.
		switch ($type) {
			case self::KEY_PRIVATE:
				$this->private_key = $key;
				break;
			case self::KEY_PUBLIC:
				$this->public_key = $key;
				break;
			case self::KEY_CERTIFICATE:
				$this->certificate = $key;
				break;
			case self::KEY_CSR:
				$this->csr = $key;
				break;
		}
	}

	/**
	 * Generate SSL-Keys.
	 *
	 * Possible $values keys: (these are also the default values)
	 *
	 * array(	"countryName"				=> 'XX',
	 * 			"stateOrProvinceName"		=> 'State',
	 * 			"localityName"				=> 'SomewhereCity',
	 * 			"organizationName"			=> 'MySelf',
	 * 			"organizationalUnitName"	=> 'Whatever',
	 * 			"commonName"				=> 'mySelf',
	 * 			"emailAddress"				=> 'user@domain.com'
	 * )
	 *
	 * @param array $values
	 *   Override the default values for the certificate (optional, default = array())
	 * @param boolean $sign
	 *   If set to false the public key will be not signed.
	 *   You will get back the CSR which you can take to sign it wherever you want. (optional, default = true)
	 * @param int $days
	 *   After which days the certificate will be expire.
	 * @param string $cacert
	 *   The ca-certificate which will be used to sign the certificate.
	 *   If provided the $ca_private_key param is MANDATORY
	 *   If left empty or provided but $ca_private_key left empty, it will be a self signed certificate
	 *   (optional, default = null)
	 * @param string|array $ca_private_key
	 *   The private key which corresponds to $cacert
	 *
	 *   If your private key needs a passphrase you need to provide an array where first value is the filepath or the certificate self and the second
	 *   value is the passphrase.
	 *
	 *   For example:
	 *     Without passphrase:
	 *        'file://ca.key'
	 *     With passphrase:
	 *        array('file://ca.key', 'secret_passphrase')
	 *
	 *   Notice: If you provide a path you need to provide the full path and must start with file://
	 *
	 *   This is MANDATORY if $cacert is provided, else it will be unused and a self signed certificate will be generated.
	 *   (optional, default = null)
	 *
	 * @return array Returns an array with
	 *   array(
	 *		'private' => 'key',
	 *      'public' => key,
	 *		'certificate' => 'signed certificate',
	 *		'csr' => 'csr'
	 *   )
	 *
	 *   certificate - Certificate will be only returned if $sign was set to true
	 *   csr - CSR-Request will only be returned if $sign was set to false.
	 * 
	 *   The array keys will exist but left empty.
	 *
	 * @throws SoopfwSecurityException Will be thrown if the certificate could not be created, see exception message for details.
	 */
	public function generate_ssl_keys(Array $values = array(), $sign = true, $days = 365, $cacert = null, $ca_private_key = null) {

		// Set the default values and override it with provided $values.
		$dn = array_merge(array(
			"countryName" => 'XX',
			"stateOrProvinceName" => 'State',
			"localityName" => 'SomewhereCity',
			"organizationName" => 'MySelf',
			"organizationalUnitName" => 'Whatever',
			"commonName" => 'mySelf',
			"emailAddress" => 'user@domain.com'), $values);

		// Create a new random private key ressource.
		$privkey = openssl_pkey_new();

		// Get the CSR-Certificate with the given $values and the generated key.
		$csr = openssl_csr_new($dn, $privkey);

		// Predefine keys.
		$publickey = $privatekey = $csr_str = $certificate = "";

		// Generate the private key.
		openssl_pkey_export($privkey, $privatekey);

		// Generate the public key.
		$details = openssl_pkey_get_details($privkey);
		if (isset($details['key'])) {
			$publickey = $details['key'];
		}

		// Generate the CSR certificate request if $sign is set to false, else get the signed certificate.
		if ($sign === true) {

			// Default sign key. (self signed)
			$sign_private_key = $privkey;

			// Verify mandatory parameters.
			if (!empty($cacert) && !empty($ca_private_key)) {
				$sign_private_key = $ca_private_key;
			}
			// Invalid ca-parameters, set $cacert back to null.
			else {
				$cacert = null;
			}

			// Sign the CSR-Certificate with the private key, certificate will expire after $days days.
			$certificate = $this->sign_csr($csr, $sign_private_key, $days, $cacert);

			// Verify that the certifcate could be signed.
			if (empty($certificate)) {
				throw new SoopfwSecurityException(t('Could not sign certificate'));
			}

		}
		else {
			// Get the CSR-Request.
			openssl_csr_export($csr, $csr_str);
		}

		// Validate the private key could be created.
		if (empty($privatekey)) {
			throw new SoopfwSecurityException(t('Private key could not be generated'));
		}

		// Validate the public key could be created.
		if (empty($publickey)) {
			throw new SoopfwSecurityException(t('Public key could not be generated'));
		}

		// Validate the CSR-Request could be created.
		if (empty($csr_str) && $sign === false) {
			throw new SoopfwSecurityException(t('CSR-Requset could not be generated'));
		}

		// Validate the signed certificate could be created.
		if (empty($certificate) && $sign === true) {
			throw new SoopfwSecurityException(t('Signed certificate could not be generated'));
		}

		// Set the generated keys/certificates.
		$this->private_key = $privatekey;
		$this->public_key = $publickey;
		$this->certificate = $certificate;
		$this->csr = $csr_str;

		// Return the keys.
		return array(
			"private" => $privatekey,
			"public" => $publickey,
			"certificate" => $certificate,
			"csr" => $csr_str,
		);
	}

	/**
	 * Sign the given csr with the provided ca.
	 *
	 * @param string $csr
	 *   The CSR-Request.
	 *   Can be the certificate self as a string or a file path
	 *   Notice: If you provide a path you need to provide the full path and must start with file://
	 * @param string|array $ca_private_key
	 *   The private key which corresponds to $cacert
	 *
	 *   If your private key needs a passphrase you need to provide an array where the first value is the filepath or the certificate self and the second
	 *   value is the passphrase.
	 *
	 *   For example:
	 *     Without passphrase:
	 *        'file://ca.key'
	 *     With passphrase:
	 *        array('file://ca.key', 'secret_passphrase')
	 *
	 *   Notice: If you provide a path you need to provide the full path and must start with file://
	 * @param int $days
	 *   After which days the certificate will be expire.
	 * @param string $cacert
	 *   The ca-certificate which will be used to sign the certificate.
	 *   If left empty it will be a self signed certificate
	 *
	 *   Notice: If you provide a path you need to provide the full path and must start with file://
	 *
	 *   (optional, default = null)
	 *
	 * @return boolean|string on success it will return the signed certificate. Returns false on error.
	 */
	public function sign_csr($csr, $ca_private_key, $days = 365, $cacert = null) {
		// Sign the CSR-Certificate with the private key, certificate will expire after $days days.
		$sscert = openssl_csr_sign($csr, $cacert, $ca_private_key, $days);

		// Verify that the certifcate could be signed.
		if ($sscert === false) {
			return false;
		}

		// Preinit var.
		$certificate = false;

		// Get the sined certificate.
		openssl_x509_export($sscert, $certificate);

		// Return the certificate.
		return $certificate;
	}

	/**
	 * Encpryts the given text with openssl.
	 *
	 * Normaly it will use the key which was setup with ssl::set_keys, but a key can be provided which will used instead.
	 *
	 * @param string $data
	 *   The data to be encrytped
	 * @param string $key_type
	 *   The key type to be used, (optional, default = SSL::KEY_PRIVATE)
	 * @param string $key
	 *   The key (optional, default = '')
	 *
	 * @return string The encrypted string
	 */
	public function encrypt($data, $key_type = self::KEY_PRIVATE, $key = "") {

		$encrypted_data = "";

		switch ($key_type) {

			// If $key_type is public we encrypt it with the public key.
			case self::KEY_PUBLIC:
				// Get the used key, if $key is not empty this will be used, else the internal public key.
				openssl_public_encrypt($data, $encrypted_data, (empty($key)) ? $this->public_key : $key);
				break;

			// If $key_type is private we encrypt it with the private key.
			case self::KEY_PRIVATE:
				// Get the used key, if $key is not empty this will be used, else the internal private key.
				openssl_private_encrypt($data, $encrypted_data, (empty($key)) ? $this->private_key : $key);
				break;

		}

		// Return the encryted string.
		return $encrypted_data;
	}

	/**
	 * Decrypts the given text with openssl.
	 *
	 * Normaly it will use the key which was setup with ssl::set_keys, but a key can be provided which will used instead.
	 *
	 * @param string $data
	 *   The text to be decrypted.
	 * @param string $key_type
	 *   The key type to be used, (optional, default = PUBLIC_KEY)
	 * @param string $key
	 *   The key. (optional, default = '')
	 *
	 * @return string The decrypted string
	 */
	public function decrypt($data, $key_type = self::KEY_PUBLIC, $key = "") {

		$decrypted_data = "";

		switch ($key_type) {

			// If $key_type is public we decrypt it with the public key.
			case self::KEY_PUBLIC:
				// Get the used key, if $key is not empty this will be used, else the internal public key.
				openssl_public_decrypt($data, $decrypted_data, (empty($key)) ? $this->public_key : $key);
				break;

			// If $key_type is private we decrypt it with the private key.
			case self::KEY_PRIVATE:
				// Get the used key, if $key is not empty this will be used, else the internal private key.
				openssl_private_decrypt($data, $decrypted_data, (empty($key)) ? $this->private_key : $key);
				break;

		}

		// Return the decryted string.
		return $decrypted_data;
	}
}