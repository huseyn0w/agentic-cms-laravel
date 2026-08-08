<?php

namespace App\Support\Updater;

use RuntimeException;

/**
 * Thrown when an update cannot proceed or fails. Carries a human-readable reason
 * the admin screen and the CLI surface directly.
 */
class UpdateException extends RuntimeException {}
