<?php
// ex: ts=4 sts=4 sw=4 et:

namespace Lockr\KeyWrapper;

class LockrAesCbcKeyWrapper implements KeyWrapperInterface
{
    /**
     * {@inheritdoc}
     */
    public static function enabled()
    {
        return function_exists('mcrypt_encrypt') && function_exists('openssl_encrypt');
    }

    /**
     * {@inheritdoc}
     */
    public static function encrypt($plaintext)
    {
        $cipher = MCRYPT_RIJNDAEL_256;
        $mode = MCRYPT_MODE_CBC;

        $key = openssl_random_pseudo_bytes(32);
        $iv_len = mcrypt_get_iv_size($cipher, $mode);
        $iv = mcrypt_create_iv($iv_len);

        $ciphertext = mcrypt_encrypt($cipher, $key, $plaintext, $mode, $iv);
        $ciphertext = base64_encode($ciphertext);
        $encoded = self::encode($cipher, $mode, $iv, $key);

        return array(
            'ciphertext' => $ciphertext,
            'encoded' => $encoded,
        );
    }

    /**
     * {@inheritdoc}
     */
    public static function decrypt($ciphertext, $encoded)
    {
        $parts = self::decode($encoded);
        if (!$parts) {
            return false;
        }
        list($cipher, $mode, $iv, $key) = $parts;
        $ciphertext = base64_decode($ciphertext);

        $key = mcrypt_decrypt($cipher, $key, $ciphertext, $mode, $iv);
        if ($key === false) {
            return false;
        }

        return trim($key);
    }

    /**
     * {@inheritdoc}
     */
    public static function reencrypt($plaintext, $encoded)
    {
        $parts = self::decode($encoded);
        if (!$parts) {
            return false;
        }
        list($cipher, $mode, $iv, $key) = $parts;
        $ciphertext = mcrypt_encrypt($cipher, $key, $plaintext, $mode, $iv);
        $ciphertext = base64_encode($ciphertext);
        return array(
            'ciphertext' => $ciphertext,
            'encoded' => $encoded,
        );
    }

    protected function encode($cipher, $mode, $iv, $key)
    {
        $parts = array(
            $cipher,
            $mode,
            base64_encode($iv),
            base64_encode($key),
        );

        return implode('$', $parts);
    }

    protected function decode($encoded)
    {
        $parts = explode('$', $encoded, 4);
        if (!$parts || count($parts) != 4) {
            return false;
        }
        list($cipher, $mode, $iv, $key) = $parts;
        $iv = base64_decode($iv);
        $key = base64_decode($key);
        return array($cipher, $mode, $iv, $key);
    }
}
