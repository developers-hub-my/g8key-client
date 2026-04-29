<?php

namespace G8Key\Client\Exceptions;

/** Token header `alg` is not the expected `EdDSA`. */
class AlgConfusionException extends G8KeyClientException {}
