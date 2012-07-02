<?php

/**
 * Provides SSL en/de-cryption
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @package lib
 */
class SSL
{
	/**
	 * Define ssl key types
	 */
	const KEY_PUBLIC = "public";
	const KEY_PRIVATE = "private";
	const KEY_CERT = "cert";

	/**
	 * The public key
	 * @var string
	 */
	public $public_key = "";

	/**
	 * The private key
	 * @var string
	 */
	private $private_key = "";

	/**
	 * The certificate
	 * @var string
	 */
	private $cert = "";

	/**
	 * Set the given $key of $type.
	 * $key can also be an array, if it is it must have all the key types included
	 * example:
	 * array(
	 * 		"public" => '...',
	 * 		"private" => '...',
	 * 		"cert" => '...',
	 * )
	 *
	 * @param mixed $key in single mode the key, can also be an array with all information
	 * @param string $type A key type, use one of ssl::KEY_*
	 */
	public function set_keys($key, $type = '') {
		//If $key is an array
		if (is_array($key)) {
			//Check if all key types provided, if not return
			if (!isset($key[self::KEY_PRIVATE]) || !isset($key[self::KEY_PUBLIC]) || !isset($key[self::KEY_CERT])) {
				return;
			}
			//Set the keys;
			$this->private_key = $key[self::KEY_PRIVATE];
			$this->public_key = $key[self::KEY_PUBLIC];
			$this->cert = $key[self::KEY_CERT];
		}

		//Set the key based up on the given key_type
		switch ($type) {
			case self::KEY_PRIVATE:
				$this->private_key = $key;
				break;
			case self::KEY_PUBLIC:
				$this->public_key = $key;
				break;
			case self::KEY_CERT:
				$this->cert = $key;
				break;
		}
	}

	/**
	 * Generate SSL-Keys.
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
	 * @param array $values override the default values for the certificate (optional, default = array())
	 * @return array an array with private=>key, public=>key, cert=>cert
	 */
	public function generate_ssl_keys(Array $values = array()) {
		//Set the default values and override it with provided $values
		$dn = array_merge(array(
			"countryName" => 'XX',
			"stateOrProvinceName" => 'State',
			"localityName" => 'SomewhereCity',
			"organizationName" => 'MySelf',
			"organizationalUnitName" => 'Whatever',
			"commonName" => 'mySelf',
			"emailAddress" => 'user@domain.com'), $values);

		//Creata a new random private key ressource
		$privkey = openssl_pkey_new();

		//Get the CSR-Certificate with the given $values and the generated key
		$csr = openssl_csr_new($dn, $privkey);

		//Sign the CSR-Certificate with the private key, key will expire after 1 year
		$sscert = openssl_csr_sign($csr, null, $privkey, 365);

		//Generate the final public key
		openssl_x509_export($sscert, $publickey);

		//Generate the final private key
		openssl_pkey_export($privkey, $privatekey);

		//Generate the final CSR-Certificate
		openssl_csr_export($csr, $csr_str);

		//Set the generate keys
		$this->private_key = $privatekey;
		$this->public_key = $publickey;
		$this->cert = $csr_str;

		//Return the keys
		return array("private" => $privatekey, "public" => $publickey, "cert" => $csr_str);
	}

	/**
	 * Encpryts the given text with openssl.
	 * normaly it will use the key which was setup with ssl::set_keys
	 * but a key can be provided which will used instead.
	 *
	 * @param string $txt The text to be encrytped
	 * @param string $key_type the key type to be used, (optional, default = KEY_PRIVATE)
	 * @param string $key the key (optional, default = '')
	 * @return string The encrypted string
	 */
	public function encrypt($txt, $key_type = self::KEY_PRIVATE, $key = "") {
		$encrypted_data = "";
		switch ($key_type) {
			//If $key_type is public we encrypt it with the public key
			case self::KEY_PUBLIC:
				//Get the used key, if $key is not empty this will be used, else the internal public key
				$decrypt_key = (empty($key)) ? $this->public_key : $key;
				openssl_public_encrypt($txt, $encrypted_data, $decrypt_key);
				break;
			//If $key_type is private we encrypt it with the private key
			case self::KEY_PRIVATE:
				//Get the used key, if $key is not empty this will be used, else the internal private key
				$decrypt_key = (empty($key)) ? $this->private_key : $key;
				openssl_private_encrypt($txt, $encrypted_data, $decrypt_key);
				break;
		}
		//Return the encryted string
		return $encrypted_data;
	}

	/**
	 * Decrypts the given text with openssl.
	 * normaly it will use the key which was setup with ssl::set_keys
	 * but a key can be provided which will used instead.
	 *
	 * @param string $txt The text to be decrypted
	 * @param string $key_type the key type to be used, (optional, default = PUBLIC_KEY)
	 * @param string $key the key (optional, default = '')
	 * @return string The decrypted string
	 */
	public function decrypt($txt, $key_type = self::PUBLIC_KEY, $key = "") {
		$decrypted_data = "";
		switch ($key_type) {
			//If $key_type is public we decrypt it with the public key
			case self::KEY_PUBLIC:
				//Get the used key, if $key is not empty this will be used, else the internal public key
				$decrypt_key = (empty($key)) ? $this->public_key : $key;
				openssl_public_decrypt($txt, $decrypted_data, $decrypt_key);
				break;
			//If $key_type is private we decrypt it with the private key
			case self::KEY_PRIVATE:
				//Get the used key, if $key is not empty this will be used, else the internal private key
				$decrypt_key = (empty($key)) ? $this->private_key : $key;
				openssl_private_decrypt($txt, $decrypted_data, $decrypt_key);
				break;
		}
		//Return the decryted string
		return $decrypted_data;
	}

}

?>