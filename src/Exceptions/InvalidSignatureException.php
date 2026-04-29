<?php

namespace G8Key\Client\Exceptions;

/** Signature length is wrong, or `sodium_crypto_sign_verify_detached` rejected it. */
class InvalidSignatureException extends G8KeyClientException {}
