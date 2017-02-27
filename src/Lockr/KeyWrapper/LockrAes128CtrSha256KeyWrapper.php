<?php
// ex: ts=4 sts=4 sw=4 et:

namespace Lockr\KeyWrapper;

class LockrAes128CtrSha256KeyWrapper implements KeyWrapperInterface
{
    const PREFIX = 'aes-128-ctr-sha256';

    const METHOD = 'aes-128-ctr';

    const HASH_BYTES = 44;

    /**
     * {@inheritdoc}
     */
    public static function enabled()
    {
        return function_exists('openssl_encrypt');
    }

    /**
     * {@inheritdoc}
     */
    public static function encrypt($plaintext)
    {
        $key = openssl_random_pseudo_bytes(32);
        $iv_len = openssl_cipher_iv_length(self::METHOD);
        $iv = openssl_random_pseudo_bytes($iv_len);

        $ciphertext = openssl_encrypt(
            $plaintext,
            self::METHOD,
            $key,
            OPENSSL_RAW_DATA,
            $iv
        );

        $hmac_key = openssl_random_pseudo_bytes(32);
        $hmac = self::hmac($ciphertext, $hmac_key);

        return array(
            'ciphertext' => base64_encode($hmac) . base64_encode($ciphertext),
            'encoded' => self::encode(
                $key,
                $iv,
                $hmac_key
            ),
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
        list($key, $iv, $hmac_key) = $parts;

        $hmac = base64_decode(substr($ciphertext, 0, self::HASH_BYTES));
        $ciphertext = base64_decode(substr($ciphertext, self::HASH_BYTES));

        if (!self::hashEquals($hmac, self::hmac($ciphertext, $hmac_key))) {
            return false;
        }

        $plaintext = openssl_decrypt(
            $ciphertext,
            self::METHOD,
            $key,
            OPENSSL_RAW_DATA,
            $iv
        );

        if ($plaintext === false) {
            return false;
        }

        return $plaintext;
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
        list($key, $iv, $hmac_key) = $parts;

        $ciphertext = openssl_encrypt(
            $plaintext,
            self::METHOD,
            $key,
            OPENSSL_RAW_DATA,
            $iv
        );

        $hmac = self::hmac($ciphertext, $hmac_key);

        return array(
            'ciphertext' => base64_encode($hmac) . base64_encode($ciphertext),
            'encoded' => $encoded,
        );
    }

    protected static function hmac($data, $key)
    {
        return hash_hmac('sha256', $data, $key, true);
    }

    protected static function hashEquals($left, $right)
    {
        if (function_exists('hash_equals')) {
            return hash_equals($left, $right);
        }

        $ret = 0;

        if (strlen($left) !== strlen($right)) {
            $right = $left;
            $ret = 1;
        }

        $res = $left ^ $right;

        for ($i = strlen($res) - 1; $i >= 0; --$i) {
            $ret |= ord($res[$i]);
        }

        return !$ret;
    }

    protected static function encode($key, $iv, $hmac_key)
    {
        $parts = array(
            self::PREFIX,
            base64_encode($key),
            base64_encode($iv),
            base64_encode($hmac_key),
        );

        return implode('$', $parts);
    }

    protected static function decode($encoded)
    {
        $parts = explode('$', $encoded, 4);
        if (!$parts || count($parts) != 4) {
            return false;
        }
        list($prefix, $key, $iv, $hmac_key) = $parts;
        if ($prefix !== self::PREFIX) {
            return false;
        }

        return array(
            base64_decode($key),
            base64_decode($iv),
            base64_decode($hmac_key),
        );
    }
}

