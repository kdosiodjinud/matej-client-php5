<?php

namespace Lmc\Matej\Model;

use Lmc\Matej\Exception\ResponseDecodingException;

/**
 * Response to one single command which was part of request batch.
 */
class CommandResponse
{
    const STATUS_OK = 'OK';
    const STATUS_ERROR = 'ERROR';
    const STATUS_SKIPPED = 'SKIPPED';
    const STATUS_INVALID = 'INVALID';
    /** @var string */
    private $status;
    /** @var string */
    private $message;
    /** @var array */
    private $data = [];

    private function __construct()
    {
    }

    /** @return static */
    public static function createFromRawCommandResponseObject(\stdClass $rawCommandResponseObject)
    {
        if (!isset($rawCommandResponseObject->status)) {
            throw new ResponseDecodingException('Status field is missing in command response object');
        }
        $commandResponse = new static();
        $commandResponse->status = $rawCommandResponseObject->status;
        $commandResponse->message = isset($rawCommandResponseObject->message) ? $rawCommandResponseObject->message : '';
        $commandResponse->data = isset($rawCommandResponseObject->data) ? $rawCommandResponseObject->data : [];

        return $commandResponse;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function getMessage()
    {
        return $this->message;
    }

    public function getData()
    {
        return $this->data;
    }

    public function isSuccessful()
    {
        return $this->getStatus() === static::STATUS_OK;
    }
}
