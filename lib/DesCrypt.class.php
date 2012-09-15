<?php
/**
 * Provides a class to en- and de-crypt a given text
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>
 * @package lib
 * @category Security
 */
class DesCrypt
{
	/**
	 * Define input types.
	 */
	const INPUT_PLAIN = '';
	const INPUT_BASE64 = 'base64';

	/**
	 * Encrypt a given text with 3-DES.
	 *
	 * @param string $text
	 *   the plain text
	 * @param string $key
	 *   the key to decrypt
	 * @param string $input
	 *   the input type, this determines how the text
	 *   is pre encoded for example use DesCrypt::INPUT_BASE64
	 *   if the provided plain text is base64 encrypted.
	 *   use one of DesCrypt::INPUT_* (optional, default = DesCrypt::INPUT_BASE64)
	 *
	 * @return string the encrypted text.
	 */
	public static function des_encode($text, $key, $output = "base64") {
		$td = mcrypt_module_open('tripledes', '', 'ecb', '');
		$key = substr($key, 0, mcrypt_enc_get_key_size($td));
		$iv_size = mcrypt_enc_get_iv_size($td);
		$iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);

		mcrypt_generic_init($td, $key, $iv);

		$c_t = mcrypt_generic($td, $text);

		mcrypt_generic_deinit($td);
		mcrypt_module_close($td);

		if ($output == "base64") {
			return base64_encode($c_t);
		}
		return $c_t;
	}

	/**
	 * Decrypts a given text with 3-DES.
	 *
	 * @param string $text
	 *   the encrypted text
	 * @param string $key
	 *   the key to decrypt
	 * @param string $input
	 *   the input type, this determines how the encrypted text
	 *   is pre encoded for example use DesCrypt::INPUT_BASE64
	 *   if the provided encrypted text is base64 encrypted.
	 *   use one of DesCrypt::INPUT_* (optional, default = DesCrypt::INPUT_BASE64)
	 *
	 * @return string the decrypted text, if decryption fails it returns an empty string.
	 */
	public static function des_decode($text, $key, $input = "base64") {
		if ($input == "base64") {
			$text = base64_decode($text);
		}
		$td = mcrypt_module_open('tripledes', '', 'ecb', '');
		$key = substr($key, 0, mcrypt_enc_get_key_size($td));
		$iv_size = mcrypt_enc_get_iv_size($td);
		$iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);

		mcrypt_generic_init($td, $key, $iv);

		$p_t = mdecrypt_generic($td, $text);

		mcrypt_generic_deinit($td);
		mcrypt_module_close($td);
		$p_t = trim($p_t);
		$string = "";
		for ($i = 0; $i < strlen($p_t); $i++) {
			if (ord($p_t{$i}) == 4) {
				continue;
			}
			$string .= $p_t{$i};
		}
		return trim($string);
	}

}

?>
