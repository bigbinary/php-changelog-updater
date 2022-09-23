<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

class UnreleasedHeadingDoesNotExistException extends Exception
{
    public function __construct()
    {
        parent::__construct("The 'Unreleased' heading was not found in the CHANGELOG. Please refer to the correct CHANGELOG format at: https://github.com/bigbinary/neeto-editor/blob/master/CHANGELOG.md.");
    }
}
