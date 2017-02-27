<?php
// ex: ts=4 sts=4 sw=4 et:

namespace Lockr\KeyWrapper;

class MultiKeyWrapper implements KeyWrapperInterface
{
    protected static $wrappers = array(
        LockrAes128CtrSha256KeyWrapper::PREFIX => LockrAes128CtrSha256KeyWrapper::class,
        '' => LockrAesCbcKeyWrapper::class,
    );

    /**
     * {@inheritdoc}
     */
    public static function enabled()
    {
        foreach (self::$wrappers as $wrapper) {
            if ($wrapper::enabled()) {
                return true;
            }
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public static function encrypt($plaintext)
    {
        foreach (self::$wrappers as $wrapper) {
            if ($wrapper::enabled()) {
                return $wrapper::encrypt($plaintext);
            }
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public static function decrypt($ciphertext, $encoded)
    {
        foreach (self::$wrappers as $prefix => $wrapper) {
            if (substr($encoded, 0, strlen($prefix)) === $prefix) {
                return $wrapper::decrypt($ciphertext, $encoded);
            }
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public static function reencrypt($plaintext, $encoded)
    {
        foreach (self::$wrappers as $prefix => $wrapper) {
            if (substr($encoded, 0, strlen($prefix)) === $prefix) {
                return $wrapper::reencrypt($plaintext, $encoded);
            }
        }

        return false;
    }
}
