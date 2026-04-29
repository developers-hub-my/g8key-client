<?php

namespace G8Key\Client\Exceptions;

/** The /heartbeat endpoint returned a non-2xx response (other than 410/423) or could not be reached. */
class HeartbeatFailedException extends G8KeyClientException
{
    public ?int $statusCode = null;

    public static function withStatus(int $status, string $message): self
    {
        $e = new self($message);
        $e->statusCode = $status;

        return $e;
    }
}
