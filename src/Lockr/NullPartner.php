<?php
// ex: ts=4 sts=4 sw=4 et:

namespace Lockr;

class NullPartner implements PartnerInterface
{
    /**
     * @var string The Lockr region.
     */
    protected $region;

    /**
     * Constucts the partner.
     */
    public function __construct($region = 'us')
    {
        $this->region = $region;
    }

    /**
     * {@inheritdoc}
     */
    public function requestOptions()
    {
        return array();
    }

    /**
     * {@inheritdoc}
     */
    public function getReadUri()
    {
        if ($this->region != 'us') {
            return "https://{$this->region}.custom.api.lockr.io";
        }

        return "https://custom.api.lockr.io";
    }

    /**
     * {@inheritdoc}
     */
    public function getWriteUri()
    {
        if ($this->region != 'us') {
            return "https://{$this->region}.custom.api.lockr.io";
        }

        return "https://custom.api.lockr.io";
    }

    /**
     * {@inheritdoc}
     */
    public function getAccountingUri()
    {
        return "https://custom.api.lockr.io";
    }
}
