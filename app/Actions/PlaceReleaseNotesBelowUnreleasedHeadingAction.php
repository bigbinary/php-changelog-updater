<?php

declare(strict_types=1);

namespace App\Actions;

use App\CreateNewReleaseHeadingWithCompareUrl;
use App\GenerateCompareUrl;
use App\Queries\FindSecondLevelHeadingWithText;
use App\Support\GitHubActionsOutput;
use Illuminate\Support\Str;
use League\CommonMark\Extension\CommonMark\Node\Block\Heading;
use League\CommonMark\Extension\CommonMark\Node\Inline\Link;
use League\CommonMark\Node\Block\Document;
use LogicException;
use Throwable;

class PlaceReleaseNotesBelowUnreleasedHeadingAction
{
    public function __construct(
        private readonly GenerateCompareUrl                    $generateCompareUrl,
        private readonly FindSecondLevelHeadingWithText        $findPreviousVersionHeading,
        private readonly CreateNewReleaseHeadingWithCompareUrl $createNewReleaseHeading,
        private readonly GitHubActionsOutput                   $gitHubActionsOutput,
        private readonly InsertReleaseNotesInChangelogAction $insertReleaseNotesInChangelogAction
    ) {
    }

    /**
     * @throws Throwable
     */
    public function execute(Heading $unreleasedHeading, string $latestCommit, string $headingText, string $releaseDate, ?string $releaseNotes, Document $changelog, string $compareUrlTargetRevision): Document
    {
        $previousCommit = $this->getPreviousCommitFromUnreleasedHeading($unreleasedHeading);
        $repositoryUrl = $this->getRepositoryUrlFromUnreleasedHeading($unreleasedHeading);
        $updatedUrl = $this->generateCompareUrl->generate($repositoryUrl, $latestCommit, $compareUrlTargetRevision);

        $link = $this->getLinkNodeFromHeading($unreleasedHeading);
        $link->setUrl($updatedUrl);
        $this->gitHubActionsOutput->add('UNRELEASED_COMPARE_URL', $updatedUrl);

        // Create new Heading containing the new version number
        $newReleaseHeading = $this->createNewReleaseHeading->create($repositoryUrl, $previousCommit, $latestCommit, $headingText, $releaseDate);

        if (empty($releaseNotes)) {
            // If no Release Notes have been passed, add the new Release Heading below the updated Unreleased Heading.
            // We assume that the user already added their release notes under the Unreleased Heading.
            $unreleasedHeading->insertAfter($newReleaseHeading);
        } else {
            return $this->insertReleaseNotesInChangelogAction->execute(
                changelog: $changelog,
                releaseNotes: $releaseNotes,
                newReleaseHeading: $newReleaseHeading,
                unreleasedHeading: $unreleasedHeading
            );
        }

        return $changelog;
    }

    /**
     * @throws Throwable
     */
    private function getPreviousCommitFromUnreleasedHeading(Heading $unreleasedHeading): ?string
    {
        $linkNode = $this->getLinkNodeFromHeading($unreleasedHeading);

        return Str::of($linkNode->getUrl())
            ->afterLast('/')
            ->explode('...')
            ->first();
    }

    /**
     * @throws Throwable
     */
    private function getRepositoryUrlFromUnreleasedHeading(Heading $unreleasedHeading): string
    {
        $linkNode = $this->getLinkNodeFromHeading($unreleasedHeading);

        return Str::of($linkNode->getUrl())
            ->before('/compare')
            ->__toString();
    }

    /**
     * @throws Throwable
     */
    private function getLinkNodeFromHeading(Heading $unreleasedHeading): Link
    {
        /** @var Link $linkNode */
        $linkNode = $unreleasedHeading->firstChild();

        throw_if($linkNode === null, new LogicException("Can not find link node in unreleased heading."));

        return $linkNode;
    }
}
