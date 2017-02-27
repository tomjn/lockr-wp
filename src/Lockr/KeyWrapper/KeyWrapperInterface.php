<?php
// ex: ts=4 sts=4 sw=4 et:

namespace Lockr\KeyWrapper;

interface KeyWrapperInterface
{
    /**
     * @return bool
     */
    public static function enabled();

    /**
     * Encrypt the given plaintext using the same initial state as
     * defined by encoded.
     *
     * @param string $plaintext
     * @param string $encoded
     * @return array
     */
    public static function reencrypt($plaintext, $encoded);

    /**
     * Encrypt the given plaintext.
     *
     * @param string $plaintext
     * @return array
     */
    public static function encrypt($plaintext);

    /**
     * Decrypt the given ciphertext using encoded.
     *
     * @param string $ciphertext
     * @param string $encoded
     * @return string|bool
     */
    public static function decrypt($ciphertext, $encoded);
}
