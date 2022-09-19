<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

class UnreleasedHeadingExistsException extends Exception
{
    public function __construct()
    {
        parent::__construct("The unreleased heading should not be present.");
    }
}
