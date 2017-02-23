<?php
// ex: ts=4 sts=4 sw=4 et:

namespace Lockr;

class Partner implements PartnerInterface
{
    /**
     * @var string The SSL cert path.
     */
    protected $cert;

    /**
     * @var string The Lockr partner.
     */
    protected $partner;

    /**
     * @var string The Lockr region.
     */
    protected $region;

    /**
     * Constucts the partner.
     */
    public function __construct($cert, $partner, $region = 'us')
    {
        $this->cert = $cert;
        $this->partner = $partner;
        $this->region = $region;
    }

    /**
     * {@inheritdoc}
     */
    public function requestOptions()
    {
        return array(
            'cert' => $this->cert,
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getReadUri()
    {
        if ($this->region != 'us') {
            return "https://{$this->region}.{$this->partner}.api.lockr.io";
        }

        return "https://{$this->partner}.api.lockr.io";
    }

    /**
     * {@inheritdoc}
     */
    public function getWriteUri()
    {
        if ($this->region != 'us') {
            return "https://{$this->region}.{$this->partner}.api.lockr.io";
        }

        return "https://{$this->partner}.api.lockr.io";
    }

    /**
     * {@inheritdoc}
     */
    public function getAccountingUri()
    {
        return "https://{$this->partner}.api.lockr.io";
    }
}
