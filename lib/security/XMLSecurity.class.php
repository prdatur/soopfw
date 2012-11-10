<?php

/**
 * Sign and verify xml data,
 * requried the installing libxmlsec from http://www.aleksey.com/xmlsec
 *
 * Notice: This class needs the php extension "SimpleXML"
 * See: http://ch2.php.net/manual/de/simplexml.installation.php
 *
 * Also it requires the command line tool "xmlsec1"
 * Install on
 *   debian/ubuntu: apt-get install xmlsec1
 *
 * @copyright Christian Ackermann (c) 2010 - End of life
 * @author Christian Ackermann <prdatur@gmail.com>, Original by: Alexandr M. Kalendarev <akalend@mail.ru>
 * @link http://www.phpclasses.org/package/3025-PHP-Encrypt-and-decrypt-XML-documents.html (Original source)
 * @category Security
 */
class XMLSecurity {

	/**
	 * The last error message.
	 *
	 * @var string
	 */
	public $error_msg = '';

	/**
	 * The command which will be executed.
	 *
	 * @var string
	 */
	private $cmd;

	/**
	 * The path where we store temp files.
	 * @var string
	 */
	private $temp_path = '';

	/**
	 * Types of key.
	 */

	const XMLSEC_KEYTYPE_AES = 1;
	const XMLSEC_KEYTYPE_DES = 2;
	const XMLSEC_KEYTYPE_PRIV_PEM = 8;
	const XMLSEC_KEYTYPE_PRIV_DER = 9;
	const XMLSEC_KEYTYPE_PUB_PEM = 10;
	const XMLSEC_KEYTYPE_PUB_DER = 11;
	const XMLSEC_KEYTYPE_PKCS_DER = 20;
	const XMLSEC_KEYTYPE_PKCS_PEM = 21;
	const XMLSEC_KEYTYPE_ROOT_CA_PEM = 31;
	const XMLSEC_KEYTYPE_ROOT_CA_DER = 32;
	const XMLSEC_KEYTYPE_UNTRAST_CA_DER = 33;
	const XMLSEC_KEYTYPE_UNTRAST_CA_PEM = 34;
	const XMLSEC_KEYTYPE_PUB_CERT_PEM = 40;
	const XMLSEC_KEYTYPE_PUB_CERT_DER = 41;

	/**
	 * Types of symmetric algorithm.
	 */
	const XMLSEC_ALGO_AES_128 = 'aes128-cbc';
	const XMLSEC_ALGO_AES_256 = 'aes256-cbc';
	const XMLSEC_ALGO_AES_192 = 'aes192-cbc';
	const XMLSEC_ALGO_DES_128 = 'des128-cbc';
	const XMLSEC_ALGO_DES_256 = 'des256-cbc';
	const XMLSEC_ALGO_3DES = 'tripledes-cbc';

	/**
	 * Types of dsignature algorithm.
	 */
	const XMLSEC_DSIG_ALGO_DSA_SHA1 = 'dsa-sha1';
	const XMLSEC_DSIG_ALGO_RSA_SHA1 = 'rsa-sha1';

	/**
	 * Key Info *
	 */
	const XMLSEC_KEYINFO_X509DATA = 'X509Data';
	const XMLSEC_KEYINFO_X509CERTIFICATE = 'X509Certificate';

	/**
	 * Xmlsec constructor.
	 *
	 * @param string $temp_path
	 *   set writeable directory for temp files	 *
	 */
	public function __construct($temp_path = '') {
		if ($temp_path != '') {
			$this->temp_path = $temp_path;
		}
	}

	/**
	 * Sign xml data.
	 *
	 * This method will add a signature at the end of the first xml element.
	 *
	 * @param string|SimpleXMLElement &$xml_data
	 *   input xml string, a filename to the xml file or a SimpleXMLElement.
	 * @param string $private_key
	 *   The private key which will be used for the signing.
	 *   Can be the key as a string or a filename where the key is stored.
	 * @param string $private_key_pass
	 *   The passphrase for the private key (optional, default = '')
	 * @param array $certificates
	 *   An array which holds all certificates.
	 *   (optional, default = array())
	 * @param string $type
	 *   the type of digital signature algorithm.
	 *   Use one of XMLSecurity::XMLSEC_DSIG_ALGO_*
	 *   (optional, default = XMLSecurity::XMLSEC_DSIG_ALGO_RSA_SHA1)
	 *
	 * @return boolean|string the signed xml data as a string on success. else false see $this->error_msg
	 */
	public function sign(&$xml_data, $private_key, $private_key_pass = '', $certificates = array(), $type = self::XMLSEC_DSIG_ALGO_RSA_SHA1) {

		// Verify that private key exist.
		if (!file_exists($private_key)) {
			$this->error_msg = t('Invalid private key.');
			return false;
		}

		// If it is not already an SimpleXMLElement element.
		if (!($xml_data instanceof SimpleXMLElement)) {

			// If the provide $xml_data is a file.
			if (file_exists($xml_data)) {
				$xml_data = simplexml_load_file($xml_data);
			}
			// If the provide $xml_data is a string.
			else {
				$xml_data = simplexml_load_string($xml_data);
			}
		}

		// Get the sign algorithm.
		$algorithm = '';
		if ($type == self::XMLSEC_DSIG_ALGO_DSA_SHA1) {
			$algorithm = 'http://www.w3.org/2000/09/xmldsig#' . self::XMLSEC_DSIG_ALGO_DSA_SHA1;
		}
		else if ($type == self::XMLSEC_DSIG_ALGO_RSA_SHA1) {
			$algorithm = 'http://www.w3.org/2000/09/xmldsig#' . self::XMLSEC_DSIG_ALGO_RSA_SHA1;
		}

		if ($algorithm == '') {
			$this->error_msg = t("Undefined algorithm type: @type", array('@type' => $type));
			return false;
		}

		// The signature template. ({cert_infos} will be replaced with the provided certificates)
		$xml_template = '
	<Signature xmlns="http://www.w3.org/2000/09/xmldsig#">
		<SignedInfo>
			<CanonicalizationMethod Algorithm="http://www.w3.org/TR/2001/REC-xml-c14n-20010315"/>
			<SignatureMethod Algorithm="' . $algorithm . '"/>
			<Reference URI="">
				<Transforms>
					<Transform Algorithm="http://www.w3.org/2000/09/xmldsig#enveloped-signature" />
				</Transforms>
				<DigestMethod Algorithm="http://www.w3.org/2000/09/xmldsig#sha1"/>
				<DigestValue></DigestValue>
			</Reference>
		</SignedInfo>
		<SignatureValue />
		<KeyInfo>
			{cert_infos}
		</KeyInfo>
	</Signature>';

		// Generate our certificate info.
		$cert_info = '';

		// Check if we have an array, if not produce one.
		if (!is_array($certificates)) {
			$certificates = array($certificates);
		}

		$keys = array();

		// Go through all provided certificates.
		foreach ($certificates AS $info) {
			// Check if the provided info is a file, if so get the content.
			if (file_exists($info)) {
				$info = file_get_contents($info);
			}

			// Remove certificate comments.
			$info = preg_replace("/[\s]*-----(BEGIN|END) CERTIFICATE-----[\s]*/is", "", $info);

			// Check if the file is not empty.
			if (empty($info)) {
				continue;
			}

			// Add the Certificate to the infos.
			$keys[] = '<X509Certificate>' . $info . '</X509Certificate>';
		}

		// If we have added some certificates, provide the X509Data element.
		if (!empty($keys)) {
			$cert_info = "<X509Data>\n" . implode("\n", $keys) ."\n</X509Data>";
		}

		// Replace the cert_infos with our generated cert infos.
		$xml_template = str_replace('{cert_infos}', $cert_info, $xml_template);

		// Add the signature to the xml data.
		$xml_string = preg_replace("/(<\s*\/\s*[^>]+>)$/s", $xml_template. "\n\${1}", $xml_data->asXML());

		if (!$this->set_path()) {
			return false;
		}

		// Save the xml data in a temp file.
		$tmpl_name = $this->save_xml(new SimpleXMLElement($xml_string), "in");

		// Validate the save process.
		if (!$tmpl_name) {
			return false;
		}

		// Validate temp path.
		if (isset($this->temp_path)) {
			$output_name = tempnam($this->temp_path, "out");
		}
		else {
			$this->error_msg = t("Can't define temp path");
			return false;
		}

		// Generate command.
		$cmd = 'xmlsec1 --sign ';
		$cmd .= '--output ' . escapeshellarg($output_name) . ' ';
		$cmd .= '--privkey-pem  ' . escapeshellarg($private_key) . ' ';
		if (!empty($private_key_pass)) {
			$cmd .= '--pwd ' . escapeshellarg($private_key_pass) . ' ';
		}
		$cmd .= escapeshellarg($tmpl_name) . ' 2>&1';

		$this->cmd = $cmd;

		// Sign.
		$read = $this->exec();

		// Get the signed output.
		$out_document = file_get_contents($output_name);

		// Remove temp files.
		@unlink($output_name);
		@unlink($tmpl_name);

		// Check that our signed data is not empty.
		if ($out_document == '') {
			$this->error_msg = t("Error in the crypto conversion:\n@error", array('@error', $read));
			return false;
		}

		// Return signed data.
		return $out_document;
	}

	/**
	 * Verify data.
	 *
	 * @param string|SimpleXMLElement $xml_data
	 *   Input signed SimpleXMLElement object or the direct string or a filepath to the data.
	 * @param string $cert
	 *   The certificate, can be the certificate as a string or a filename which holds the certificate.
	 *
	 * @return boolean Returns true if provided DomDocument can be validated with the signature, else false.
	 */
	public function verify($xml_data, $cert) {

		$unlink = true;

		// If the $xml_data is a SimpleXMLElement get the data as a xml string.
		if ($xml_data instanceof SimpleXMLElement) {
			$xml_data = $xml_data->asXML();
		}

		if (!$this->set_path()) {
			return false;
		}

		// If data is a file, we do not need to save data, we can just use the file directly.
		if(file_exists($xml_data)) {
			// The provided $xml_data is a direct file so we can not delete it, because we did not create it ...
			$unlink = false;
			$input_name = $xml_data;
		}
		else {
			// Create the temporary xml file which holds the current data.
			$input_name = $this->save_xml(new SimpleXMLElement($xml_data), "in");
			if (!$input_name) {
				return false;
			}
		}

		// Check if $cert is not a file store it into a temp one.
		$delete_temp_cert_file = false;
		if (!file_exists($cert)) {
			$this->set_path();
			$cert_path = $this->temp_path . '/' . uniqid();
			file_put_contents($cert_path, $cert);
			$cert = $cert_path;
			$delete_temp_cert_file = true;
		}

		// Generate cmd.
		$cmd = 'xmlsec1 --verify ';
		$cmd .= '--ignore-manifests ';
		$cmd .= ' --trusted-pem ' . escapeshellarg($cert) . ' ';
		$cmd .= escapeshellarg($input_name) . ' 2>&1';

		$this->cmd = $cmd;

		// Verify.
		$res = $this->exec();

		// Unlink only if $xml_data was not the file directly and we needed to create a temp file.
		if ($unlink) {
			@unlink($input_name);
		}

		// Delete the self created temp certificate file.
		if ($delete_temp_cert_file === true) {
			@unlink($cert);
		}

		// Verify that the verification succeed.
		if (trim($res) != 'OK') {
			$this->error_msg = $res;
			return false;
		}

		return true;
	}

	/**
	 * Set temp path for temp files.
	 *
	 * @return boolean Returns true if the path could be set, else false.
	 */
	private function set_path() {
		if ($this->temp_path != '') {
			return true;
		}

		$temp_path = getenv('TMPDIR');
		if (!isset($temp_path) or $temp_path == '') {
			$temp_path = getenv('TMP');
		}

		if (isset($temp_path)) {
			$this->temp_path = $temp_path;
		}
		else {
			$this->error_msg = "can't define temp path";
			return false;
		}

		return true;
	}

	/**
	 * Save dom xml into temp file.
	 *
	 * @param SimpleXMLElement $dom
	 *   The dom object.
	 * @param string $prefix
	 *   The prefix.
	 *
	 * @return string|boolean returns the file path on success, else boolean false.
	 */
	private function save_xml(SimpleXMLElement &$dom, $prefix) {

		$tmpfname = tempnam($this->temp_path, $prefix);

		if (!is_writable($tmpfname)) {
			$this->error_msg = "the file $tmpfname d't writable";
			return false;
		}

		$f = fopen($tmpfname, 'w');
		if (!$f) {
			$this->error_msg = "can't create temp file " . $tmpfname;
			return false;
		}

		$xmlstr = $dom->asXML();
		if (fwrite($f, $xmlstr) === FALSE) {
			$this->error_msg = "can't write template file";
			fclose($f);
			return false;
		}

		fclose($f);

		return $tmpfname;
	}

	/**
	 * Execute command.
	 *
	 * @return string The return data.
	 *
	 * @throws SoopfwErrorException Will be thrown if xmlsec1 binary could not be found.
	 */
	private function exec() {
		static $xmlsec1_present = null;

		if ($xmlsec1_present === null) {
			$xmlsec1_present = preg_match("/^xmlsec1/", shell_exec('xmlsec1 --version'));
		}

		if ($xmlsec1_present === false) {
			throw new SoopfwErrorException(t('Can not find xmlsec1 binaray'));
		}

		$p = popen($this->cmd, 'r');
		$read = fread($p, 4096);
		pclose($p);

		return $read;
	}
}