<?php

declare(strict_types=1);

namespace App\Actions;

use App\Exceptions\ReleaseNotesCanNotBeplacedException;
use League\CommonMark\Extension\CommonMark\Node\Block\Heading;
use League\CommonMark\Node\Block\Document;

class InsertReleaseNotesInChangelogAction
{
    public function __construct(private readonly PrepareReleaseNotesAction $prepareReleaseNotes)
    {
    }

    /**
     * @throws ReleaseNotesCanNotBeplacedException
     */
    public function execute(Document $changelog, string $releaseNotes, Heading $newReleaseHeading, ?Heading $unreleasedHeading): Document
    {
        // Prepare raw release notes to be inserted into CHANGELOG.
        $parsedReleaseNotes = $this->prepareReleaseNotes->execute($releaseNotes, $newReleaseHeading);

        // If unreleased heading exists, insert new release notes block **after** this heading.
        if ($unreleasedHeading !== null) {
            $unreleasedHeading->insertAfter($parsedReleaseNotes);

            return $changelog;
        }

        // If no unreleased heading exists in the document, we consider the CHANGELOG empty.
        // Insert the release notes at the end of the document (after the last element in the existing CHANGELOG).
        if ($changelog->lastChild() !== null) {
            $changelog->lastChild()->insertAfter($parsedReleaseNotes);

            return $changelog;
        }

        // If the CHANGELOG doesn't have any children, we currently don't insert the release notes.
        // An exception is thrown and an error message is displayed to the user.
        throw new ReleaseNotesCanNotBeplacedException();
    }
}
