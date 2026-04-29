<?php

namespace G8Key\Client\Exceptions;

/** The /activate endpoint returned a non-2xx response or could not be reached. */
class ActivationFailedException extends G8KeyClientException
{
    public ?int $statusCode = null;

    public static function withStatus(int $status, string $message): self
    {
        $e = new self($message);
        $e->statusCode = $status;

        return $e;
    }
}
