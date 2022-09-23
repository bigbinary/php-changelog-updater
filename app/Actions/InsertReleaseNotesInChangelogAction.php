<?php

declare(strict_types=1);

namespace App\Actions;

use League\CommonMark\Extension\CommonMark\Node\Block\Heading;
use League\CommonMark\Node\Block\Document;

class InsertReleaseNotesInChangelogAction
{
    public function __construct(private readonly PrepareReleaseNotesAction $prepareReleaseNotes)
    {
    }

    public function execute(Document $changelog, string $releaseNotes, Heading $newReleaseHeading, ?Heading $unreleasedHeading): Document
    {
        // Prepare raw release notes to be inserted into CHANGELOG.
        $parsedReleaseNotes = $this->prepareReleaseNotes->execute($releaseNotes, $newReleaseHeading);
        $unreleasedHeading->insertAfter($parsedReleaseNotes);
        return $changelog;
    }
}
