<?php

namespace G8Key\Client\Exceptions;

/** Token header `kid` is not present in the configured `public_keys` map. */
class UnknownKidException extends G8KeyClientException {}
