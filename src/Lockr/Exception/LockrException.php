<?php
// ex: ts=4 sts=4 sw=4 et:

namespace Lockr\Exception;

class LockrException extends \Exception
{
    /**
     * @var string
     */
    public $title;

    /**
     * @var string
     */
    public $description;

    /**
     * Constructs a new LockrException.
     */
    public function __construct(array $params = array())
    {
        $this->title = isset($params['title'])
            ? $params['title']
            : '';

        $this->description = isset($params['description'])
            ? $params['description']
            : '';

        parent::__construct(
            isset($params['message']) ? $params['message'] : '',
            isset($params['code']) ? $params['code'] : 0,
            isset($params['previous']) ? $params['previous'] : NULL
        );
    }
}

