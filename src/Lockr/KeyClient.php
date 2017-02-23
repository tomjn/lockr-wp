<?php
// ex: ts=4 sts=4 sw=4 et:

namespace Lockr;

use Lockr\KeyWrapper\MultiKeyWrapper;

class KeyClient
{
    /**
     * @var Lockr The external interface.
     */
    protected $client;

    /**
     * @var string|bool Data to decrypt keys.
     */
    protected $encoded = null;

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
     * Gets and sets encrypted keys.
     *
     * @param string $encoded (optional) Data to decrypt keys.
     *
     * @return self The client for method chaining.
     */
    public function encrypted($encoded = true)
    {
        $this->encoded = $encoded;
        return $this;
    }

    /**
     * Gets a key from Lockr.
     *
     * @param string $name The key name.
     *
     * @return string The key.
     */
    public function get($name)
    {
        $body = $this->client->get($this->uri($name));

        if (null !== $this->encoded) {
            return MultiKeyWrapper::decrypt($body['key_value'], $this->encoded);
        }
        return $body['key_value'];
    }

    /**
     * Creates a key in Lockr.
     *
     * @param int $key_size The size of key to create.
     *
     * @return string Returns the created key value.
     */
    public function create($key_size)
    {
        $body = $this->client->get("/v1/generate-key?key_size={$key_size}");
        return base64_decode($body['key_value']);
    }

    /**
     * Sets a key in Lockr.
     *
     * @param string $name The key name.
     * @param string $value The key value.
     * @param string $label The key label.
     *
     * @return string Returns the decrypt data or true.
     */
    public function set($name, $value, $label, $encoded = null)
    {
        if ($this->encoded) {
            if ($encoded === NULL) {
                $ret = MultiKeyWrapper::encrypt($value);
            } else {
                $ret = MultiKeyWrapper::reencrypt($value, $encoded);
            }
            $value = $ret['ciphertext'];
        }
        $data = array(
            'key_value' => $value,
            'key_label' => $label,
        );
        $this->client->patch($this->uri($name), $data);

        if ($this->encoded) {
            return $ret['encoded'];
        }
        return true;
    }

    /**
     * Deletes a key from Lockr.
     *
     * @param string $name The key name.
     */
    public function delete($name)
    {
        $this->client->delete($this->uri($name));
    }

    protected function uri($name)
    {
        return '/v1/key/'.urlencode($name);
    }
}
