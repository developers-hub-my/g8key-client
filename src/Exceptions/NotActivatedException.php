<?php

namespace G8Key\Client\Exceptions;

/** No activation present in the local store; run `php artisan license:activate` first. */
class NotActivatedException extends G8KeyClientException {}
