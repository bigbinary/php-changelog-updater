<?php

declare(strict_types=1);

namespace App\Actions;

use App\Exceptions\ReleaseAlreadyExistsInChangelogException;
use App\Exceptions\UnreleasedHeadingExistsException;
use App\Queries\FindSecondLevelHeadingWithText;
use App\Queries\FindUnreleasedHeading;
use App\Support\Markdown;
use League\CommonMark\Node\Block\Document;
use League\CommonMark\Output\RenderedContentInterface;
use Throwable;

class AddReleaseNotesToChangelogAction
{
    public function __construct(
        private readonly Markdown                                      $markdown,
        private readonly FindUnreleasedHeading                         $findUnreleasedHeading,
        private readonly FindSecondLevelHeadingWithText                $findSecondLevelHeadingWithText,
        private readonly PlaceReleaseNotesAtTheTopAction               $addNewReleaseToChangelog
    ) {
    }

    /**
     * @throws UnrelasedHeadingExistsException|Throwable
     */
    public function execute(string $originalChangelog, string $latestVersion,  string $latestCommitHash, string $headingText, ?string $releaseNotes, string $releaseDate, string $compareUrlTargetRevision): RenderedContentInterface
    {
        $changelog = $this->markdown->parse($originalChangelog);

        $this->checkIfVersionAlreadyExistsInChangelog($changelog, $latestVersion);

        $unreleasedHeading = $this->findUnreleasedHeading->find($changelog);

        throw_if($unreleasedHeading !== null,UnreleasedHeadingExistsException::class );

        $changelog = $this->addNewReleaseToChangelog->execute(
            changelog: $changelog,
            headingText: $headingText,
            latestVersion: $latestVersion,
            latestCommitHash: $latestCommitHash,
            releaseDate: $releaseDate,
            releaseNotes: $releaseNotes
        );

        return $this->markdown->render($changelog);
    }

    /**
     * Check if a second-level heading for the latestVersion already exists in the document.
     * @throws ReleaseAlreadyExistsInChangelogException|Throwable
     */
    private function checkIfVersionAlreadyExistsInChangelog(Document $changelog, string $latestVersion): void
    {
        $result = $this->findSecondLevelHeadingWithText->find($changelog, $latestVersion);

        throw_unless(is_null($result), new ReleaseAlreadyExistsInChangelogException($latestVersion));
    }
}
