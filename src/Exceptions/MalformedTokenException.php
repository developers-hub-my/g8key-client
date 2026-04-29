<?php

namespace G8Key\Client\Exceptions;

/** Token segments missing, JSON decode failed, or `nbf`/`typ` is wrong. */
class MalformedTokenException extends G8KeyClientException {}
