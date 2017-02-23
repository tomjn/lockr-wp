<?php
// ex: ts=4 sts=4 sw=4 et:

namespace Lockr;

/**
 * API for site management operations.
 */
class SiteClient
{
    /**
     * @var Lockr The external interface.
     */
    protected $client;

    /**
     * Constructs a SiteClient.
     *
     * @param Lockr $client The external interface.
     */
    public function __construct(Lockr $client)
    {
        $this->client = $client;
    }

    /**
     * Creates a new certificate.
     *
     * @param array $dn The distinguished name to create the CSR.
     *
     * @return string[] The private key and signed certificate.
     */
    public function createCert(array $dn)
    {
        $key = openssl_pkey_new(array('private_key_bits' => 2048));
        if ($key === false) {
            throw new \RuntimeException('Could not create private key.');
        }
        if (!openssl_pkey_export($key, $key_text)) {
            throw new \RuntimeException('Could not export private key.');
        }

        $csr = openssl_csr_new($dn, $key);
        if (!openssl_csr_export($csr, $csr_text)) {
            throw new \RuntimeException('Could not export CSR.');
        }

        $body = $this->client->post('/v1/create-cert', [
            'csr_text' => $csr_text,
        ]);

        return [
            'key_text' => $key_text,
            'cert_text' => $body['cert_text'],
        ];
    }

    /**
     * Checks if the current site/env is registered and/or available.
     *
     * @return bool[]
     *
     * @throws ServerException
     * if the server is unavailable or returns an error.
     * @throws ClientException if there was an unexpected client error.
     */
    public function exists()
    {
        $body = $this->client->get('/v1/site/exists');

        $defaults = array(
            'cert_valid' => false,
            'exists' => false,
            'available' => false,
            'has_cc' => false,
        );

        if (is_array($body)) {
            return $body + $defaults;
        }

        return $defaults;
    }

    /**
     * Registers the site with Lockr.
     *
     * @param string $email The email to register with.
     * @param string $pass  (optional) The password for authentication.
     * @param string $name  (optional) The site name.
     *
     * @throws ServerException
     * if the server is unavailable or returns an error.
     * @throws ClientException if there was an unexpected client error.
     */
    public function register($email, $pass = null, $name = null) {
        $data = array(
            'email' => $email,
            'name' => $name,
        );

        if (null !== $pass) {
            $auth = "$email:$pass";
        } else {
            $auth = null;
        }

        $this->client->post('/v1/site/register', $data, $auth);
    }
}
