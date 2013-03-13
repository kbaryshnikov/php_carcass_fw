<?php
/**
 * Carcass Framework
 *
 * @author    Konstantin Baryshnikov <me@fixxxer.me>
 * @license   http://www.gnu.org/licenses/gpl.html GPL
 */

namespace Carcass\Corelib;

/**
 * Encryption library
 *
 * @package Carcass\Corelib
 */
class Crypter {

    protected
        $secret,
        $xor_mask,
        $iv,
        $crypt_algo = MCRYPT_RIJNDAEL_256,
        $crypt_mode = MCRYPT_MODE_ECB;

    /**
     * @param string|array|null settings array:[secret=>string, algo=>algo|[algo,mode]], or string $secret. 
     *                    If null, secret is not defined: it must be set with setSecret() later, or
     *                    the dependant parameters (salt, xor_with etc) must be passed as arguments to the methods.
     */
    public function __construct($settings = null) {
        if (null === $settings) {
            return;
        }

        if (!is_array($settings)) {
            $settings = [ 'secret' => (string)$settings ];
        }

        $this->configure($settings);
    }

    /**
     * @param array $settings
     * @return $this
     */
    public function configure(array $settings) {
        if (isset($settings['secret'])) {
            $this->setSecret($settings['secret'] ?: null);
        }
        if (isset($settings['algo'])) {
            if (is_array($settings['algo'])) {
                $algo = reset($settings['algo']);
                $mode = next($settings['algo']) ?: null;
            } else {
                $algo = $settings['algo'];
                $mode = null;
            }
            $this->setCryptAlgo($algo, $mode);
        }
        return $this;
    }

    /**
     * @param $algo
     * @param null $mode
     * @return $this
     */
    public function setCryptAlgo($algo, $mode = null) {
        $this->crypt_algo = is_string($algo) ? constant('MCRYPT_' . strtoupper($algo)) : $algo;
        $this->crypt_mode = $mode === null ? MCRYPT_MODE_ECB : ( is_string($mode) ? constant('MCRYPT_MODE_' . strtoupper($mode)) : $mode );
        $this->iv = null;
        return $this;
    }

    /**
     * @param $secret
     * @return $this
     */
    public function setSecret($secret) {
        $this->secret = $secret;
        $this->xor_mask = $secret === null ? null : (crc32($secret) % 256);
        return $this;
    }

    /**
     * Calculates a salted sha1 hash
     *
     * @param string $s source string
     * @param string|null $salt salt; defaults to secret
     * @return string
     */
    public function hash($s, $salt = null) {
        return sha1(($salt ? $salt : $this->getSecret()) . $s);
    }

    /**
     * Encodes/decodes the string with offset-modified XOR. The implementation is symmetric.
     *
     * @param string $s source string
     * @param int $xor_with - xor byte value (0..255), defaults to crc32(secret) % 256
     * @return string
     */
    public function xorString($s, $xor_with = null) {
        if (!strlen($s)) return '';
        if (null === $xor_with) {
            $xor_with = $this->getXorMask();
        } else {
            settype($xor_with, 'integer');
        }
        $len = strlen($s);
        for ($i=0; $i<$len; ++$i) {
            $s{$i} = chr(ord($s{$i}) ^ $xor_with ^ (~$i));
        }
        return $s;
    }

    /**
     * Obfuscates a string, array, or an object. Returns a web safe string.
     *
     * @param mixed $s
     * @return string
     */
    public function obfuscate($s) {
        return StringTools::webSafeBase64Encode($this->xorString(JsonTools::encode($s)));
    }

    /**
     * Deobfuscates data obfuscated by obfuscate()
     *
     * @param string $s
     * @return mixed
     */
    public function deobfuscate($s) {
        try {
            return JsonTools::decode($this->xorString(StringTools::webSafeBase64Decode($s)), true);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Encodes a string, array, or an object. Returns a web safe string.
     *
     * @param mixed $s
     * @param string $secret default self.secret
     * @return string
     */
    public function encrypt($s, $secret = null) {
        return StringTools::webSafeBase64Encode(mcrypt_encrypt(
            $this->crypt_algo,
            $this->buildCryptKey($secret ? $secret : $this->getSecret()),
            serialize($s),
            $this->crypt_mode,
            $this->getIv()
        ));
    }

    /**
     * Decodes data encoded with encrypt()
     *
     * @param string $s
     * @param string $secret default self.secret
     * @return mixed
     */
    public function decrypt($s, $secret = null) {
        try {
            return unserialize(mcrypt_decrypt(
                $this->crypt_algo,
                $this->buildCryptKey($secret ? $secret : $this->getSecret()),
                StringTools::webSafeBase64Decode($s),
                $this->crypt_mode,
                $this->getIv()
            ));
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Encodes a string, array, or an object, with salt. Returns a salted web safe string.
     *
     * @param mixed $s
     * @param string $salt   default to generate random 6-8 chars salt
     * @param string $secret default self.secret
     * @return string
     */
    public function encryptSalted($s, $salt = null, $secret = null) {
        if (empty($salt)) {
            $salt = static::getRandomString(6, 8, join('', array_merge(range('A','Z'),range('a','z'),range(0,9))));
        }
        return $salt . '$' . StringTools::webSafeBase64Encode(mcrypt_encrypt(
            $this->crypt_algo,
            $this->buildCryptKey($secret ? $secret : $this->getSecret(), $salt),
            serialize($s),
            $this->crypt_mode,
            $this->getIv()
        ));
    }

    /**
     * Decodes data encoded with encrypt()
     *
     * @param string $salted_str salted encoded string, formed by encryptSalted()
     * @param string $secret default self.secret
     * @return mixed
     */
    public function decryptSalted($salted_str, $secret = null) {
        try {
            list($salt, $s) = explode('$', $salted_str, 2);
            return unserialize(mcrypt_decrypt(
                $this->crypt_algo,
                $this->buildCryptKey($secret ? $secret : $this->getSecret(), $salt),
                StringTools::webSafeBase64Decode($s),
                $this->crypt_mode,
                $this->getIv()
            ));
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Generates a random number in the given range
     *
     * @param int $min
     * @param int $max
     * @return int
     */
    public static function getRandomNumber($min = 0, $max = 65535) {
        $min = (int)$min;
        $max = (int)$max;
        if ($min > $max) {
            list($min, $max) = array($max, $min);
        }
        $random_ints = unpack("V*", static::getRandomBytes(8));
        $random_int = ($random_ints[2] << 31) | $random_ints[1];
        return intval($random_int * ($max - $min + 1) / ~(1<<63)) + $min;
    }

    /**
     * Generates random bytes
     * 
     * @param int $count of bytes
     * @return string
     */
    public static function getRandomBytes($count) {
        return file_get_contents("/dev/urandom", false, null, null, $count);
    }

    /**
     * getRandomString
     *
     * @param int $min_len
     * @param int|null $max_len
     * @param string $chars
     * @throws \InvalidArgumentException
     * @return string
     */
    public static function getRandomString($min_len = 8, $max_len = null, $chars = 'abcdefghijklmnopqrstuvwxyz') {
        if ($max_len === null) $max_len = $min_len;
        if ($min_len < 1 || $max_len < 1 || $max_len < $min_len) {
            throw new \InvalidArgumentException('Invalid length value(s)');
        }
        if (!is_string($chars) || strlen($chars) < 1) {
            throw new \InvalidArgumentException('Invalid chars argument value');
        }
        $result = '';
        $result_length = static::getRandomNumber($min_len, $max_len);
        $chars_length  = mb_strlen($chars);
        for ($i = 0; $i < $result_length; $i++) {
            $result .= mb_substr($chars, static::getRandomNumber(0, $chars_length-1), 1);
        }
        return $result;
    }

    /**
     * @throws \RuntimeException
     */
    protected function ensureSecretIsDefined() {
        if (null === $this->secret) {
            throw new \RuntimeException('Secret is undefined');
        }
    }

    /**
     * @return string
     */
    protected function getSecret() {
        $this->ensureSecretIsDefined();
        return $this->secret;
    }

    /**
     * @return string
     */
    protected function getXorMask() {
        $this->ensureSecretIsDefined();
        return $this->xor_mask;
    }

    /**
     * @return string
     */
    protected function getIv() {
        if (!isset($this->iv)) {
            $this->iv = mcrypt_create_iv(mcrypt_get_iv_size($this->crypt_algo, $this->crypt_mode), MCRYPT_RAND);
        }
        return $this->iv;
    }

    /**
     * @param $secret
     * @param null $salt
     * @return string
     */
    protected function buildCryptKey($secret, $salt = null) {
        if (empty($salt)) {
            $salt = "\x0";
        }
        return mhash_keygen_s2k(MHASH_SHA256, $secret, $salt, mcrypt_get_key_size($this->crypt_algo, $this->crypt_mode));
    }

}
