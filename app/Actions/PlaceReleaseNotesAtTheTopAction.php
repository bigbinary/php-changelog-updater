<?php

declare(strict_types=1);

namespace App\Actions;

use App\CreateNewReleaseHeadingWithCompareUrl;
use App\Exceptions\ReleaseNotesNotProvidedException;
use App\Queries\FindFirstSecondLevelHeading;
use Illuminate\Support\Str;
use League\CommonMark\Extension\CommonMark\Node\Block\Heading;
use League\CommonMark\Extension\CommonMark\Node\Inline\Link;
use League\CommonMark\Node\Block\Document;
use LogicException;
use Throwable;

class PlaceReleaseNotesAtTheTopAction
{
    public function __construct(
        private readonly FindFirstSecondLevelHeading       $findFirstSecondLevelHeading,
        private readonly CreateNewReleaseHeadingWithCompareUrl           $createNewReleaseHeading,
        private readonly InsertReleaseNotesInChangelogAction $insertReleaseNotesInChangelogAction
    ) {
    }

    /**
     * @throws Throwable
     */
    public function execute(Document $changelog, string $headingText, string $latestVersion, string $latestCommitHash, string $releaseDate, ?string $releaseNotes): Document
    {
        throw_if(empty($releaseNotes), ReleaseNotesNotProvidedException::class);

        // Find the Heading of the previous Version
        $previousVersionHeading = $this->findFirstSecondLevelHeading->find($changelog);
        $previousVersion = $this->getPreviousVersionFromPreviousVersionHeading($previousVersionHeading);
        $repositoryUrl = $this->getRepositoryUrlFromPreviousVersionHeading($previousVersionHeading);

        // Create new Heading containing the new version number
        $newReleaseHeading = $this->createNewReleaseHeading->create($repositoryUrl, $previousVersion, $latestVersion, $latestCommitHash, $headingText, $releaseDate);

        return $this->insertReleaseNotesInChangelogAction->execute(
            changelog: $changelog,
            releaseNotes: $releaseNotes,
            newReleaseHeading: $newReleaseHeading,
            previousVersionHeading: $previousVersionHeading
        );
    }

    /**
     * @throws Throwable
     */
    private function getRepositoryUrlFromPreviousVersionHeading(Heading $previousVersionHeading): string
    {
        $linkNode = $this->getLinkNodeFromHeading($previousVersionHeading);

        return Str::of($linkNode->getUrl())
            ->before('/compare')
            ->__toString();
    }

    /**
     * @throws Throwable
     */
    private function getPreviousVersionFromPreviousVersionHeading(Heading $previousVersionHeading): ?string
    {
        $linkNode = $this->getLinkNodeFromHeading($previousVersionHeading);

        return Str::of($linkNode->getUrl())
            ->afterLast('/')
            ->explode('...')[1];
    }

    /**
     * @throws Throwable
     */
    private function getLinkNodeFromHeading(Heading $previousVersionHeading): Link
    {
        /** @var Link $linkNode */
        $linkNode = $previousVersionHeading->firstChild();

        throw_if($linkNode === null, new LogicException("Can not find link node in previous version heading."));

        return $linkNode;
    }
}
