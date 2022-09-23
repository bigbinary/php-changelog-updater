<?php

declare(strict_types=1);

it('places given release notes in correct position in given markdown changelog', function () {
    $this->artisan('update', [
        '--release-notes' => <<<MD
        ### Added
        - New Feature A
        - New Feature B

        ### Changed
        - Update Feature C

        ### Removes
        - Remove Feature D
        MD,
        '--latest-version' => 'v1.0.0',
        '--latest-commit' => '1eefb4b7adef74e1b21c336063fbb8071f0c6e6f',
        '--path-to-changelog' => __DIR__ . '/../Stubs/base-changelog.md',
        '--release-date' => '2021-02-01',
    ])
         ->expectsOutput(file_get_contents(__DIR__ . '/../Stubs/expected-changelog.md'))
         ->assertSuccessful();
});

it('outputs RELEASE_COMPARE_URL and UNRELEASED_COMPARE_URL for GitHub Actions in CI environment', function () {
    $this->artisan('update', [
        '--release-notes' => <<<MD
        ### Added
        - New Feature A
        - New Feature B

        ### Changed
        - Update Feature C

        ### Removes
        - Remove Feature D
        MD,
        '--latest-version' => 'v1.0.0',
        '--path-to-changelog' => __DIR__ . '/../Stubs/base-changelog.md',
        '--release-date' => '2021-02-01',
        '--github-actions-output' => true,
    ])
         ->expectsOutputToContain(sprintf("::set-output name=%s::%s", 'RELEASE_COMPARE_URL', 'https://github.com/org/repo/compare/v0.1.0...v1.0.0'))
         ->expectsOutputToContain(sprintf("::set-output name=%s::%s", 'RELEASE_URL_FRAGMENT', '#v100---2021-02-01'))
         ->expectsOutputToContain(sprintf("::set-output name=%s::%s", 'UNRELEASED_COMPARE_URL', 'https://github.com/org/repo/compare/v1.0.0...HEAD'))
         ->assertSuccessful();
});

it('throws error if latest-version is missing', function () {
    $this->artisan('update', [
        '--release-notes' => '::release-notes::',
        ])
       ->assertFailed();
})->throws(InvalidArgumentException::class, 'No latest-version option provided. Abort.');

it('uses current date for release date if no option is provieded', function () {
    $expectedChangelog = file_get_contents(__DIR__ . '/../Stubs/expected-changelog.md');
    $expectedOutput = str_replace('2021-02-01', now()->format('Y-m-d'), $expectedChangelog);

    $this->artisan('update', [
        '--release-notes' => <<<MD
        ### Added
        - New Feature A
        - New Feature B

        ### Changed
        - Update Feature C

        ### Removes
        - Remove Feature D
        MD,
        '--latest-version' => 'v1.0.0',
        '--latest-commit' => '1eefb4b7adef74e1b21c336063fbb8071f0c6e6f',
        '--path-to-changelog' => __DIR__ . '/../Stubs/base-changelog.md',
    ])
         ->expectsOutput($expectedOutput)
         ->assertSuccessful();
});

it('uses current date for release date if option is empty', function () {
    $expectedChangelog = file_get_contents(__DIR__ . '/../Stubs/expected-changelog.md');
    $expectedOutput = str_replace('2021-02-01', now()->format('Y-m-d'), $expectedChangelog);

    $this->artisan('update', [
        '--release-notes' => <<<MD
        ### Added
        - New Feature A
        - New Feature B

        ### Changed
        - Update Feature C

        ### Removes
        - Remove Feature D
        MD,
        '--latest-version' => 'v1.0.0',
        '--latest-commit' => '1eefb4b7adef74e1b21c336063fbb8071f0c6e6f',
        '--path-to-changelog' => __DIR__ . '/../Stubs/base-changelog.md',
        '--release-date' => '',
    ])
         ->expectsOutput($expectedOutput)
         ->assertSuccessful();
});

it('places given release notes in correct position even if changelog is empty besides an unreleased heading', function () {
    $this->artisan('update', [
        '--release-notes' => <<<MD
        ### Added
        - New Feature A
        - New Feature B

        ### Changed
        - Update Feature C

        ### Removes
        - Remove Feature D
        MD,
        '--latest-version' => 'v1.0.0',
        '--latest-commit' => '1eefb4b7adef74e1b21c336063fbb8071f0c6e6f',
        '--path-to-changelog' => __DIR__ . '/../Stubs/base-changelog-empty-with-unreleased.md',
        '--release-date' => '2021-02-01',
    ])
         ->expectsOutput(file_get_contents(__DIR__ . '/../Stubs/expected-changelog-empty-with-unreleased.md'))
         ->assertSuccessful();
});

it('uses compare-url-target option in unreleased heading url', function () {
    $this->artisan('update', [
        '--release-notes' => <<<MD
        ### Added
        - New Feature A
        - New Feature B

        ### Changed
        - Update Feature C

        ### Removes
        - Remove Feature D
        MD,
        '--latest-version' => 'v1.0.0',
        '--latest-commit' => '1eefb4b7adef74e1b21c336063fbb8071f0c6e6f',
        '--path-to-changelog' => __DIR__ . '/../Stubs/base-changelog-with-custom-compare-url-target.md',
        '--release-date' => '2021-02-01',
        '--compare-url-target-revision' => '1.x',
    ])
         ->expectsOutput(file_get_contents(__DIR__ . '/../Stubs/expected-changelog-with-custom-compare-url-target.md'))
         ->assertSuccessful();
});

it('shows warning if version already exists in the changelog', function () {
    $this->artisan('update', [
        '--release-notes' => <<<MD
        ### Added
        - New Feature A
        - New Feature B

        ### Changed
        - Update Feature C

        ### Removes
        - Remove Feature D
        MD,
        '--latest-version' => 'v0.1.0',
        '--path-to-changelog' => __DIR__ . '/../Stubs/base-changelog.md',
        '--release-date' => '2021-02-01',
        '--compare-url-target-revision' => '1.x',
    ])
         ->expectsOutput('CHANGELOG was not updated as release notes for v0.1.0 already exist.')
         ->assertSuccessful();
});

it('uses existing content between unreleased and previous version heading as release notes if release notes are empty', function () {
    $this->artisan('update', [
        '--release-notes' => '',
        '--latest-version' => 'v1.0.0',
        '--latest-commit' => '22981a6eeee7fd5fbf8a197b2195b2dec8f159e0',
        '--path-to-changelog' => __DIR__ . '/../Stubs/base-changelog-with-unreleased-notes.md',
        '--release-date' => '2021-02-01',
        '--compare-url-target-revision' => '1.x',
    ])
         ->expectsOutput(file_get_contents(__DIR__ . '/../Stubs/expected-changelog-with-unreleased-notes.md'))
         ->assertSuccessful();
});

it('uses existing content between unreleased and previous version heading as release notes if release notes option is not provided', function () {
    $this->artisan('update', [
        '--latest-version' => 'v1.0.0',
        '--latest-commit' => '22981a6eeee7fd5fbf8a197b2195b2dec8f159e0',
        '--path-to-changelog' => __DIR__ . '/../Stubs/base-changelog-with-unreleased-notes.md',
        '--release-date' => '2021-02-01',
        '--compare-url-target-revision' => '1.x',
    ])
         ->expectsOutput(file_get_contents(__DIR__ . '/../Stubs/expected-changelog-with-unreleased-notes.md'))
         ->assertSuccessful();
});

test('it automatically shifts heading levels to be level 3 headings to fit into the existing changelog', function ($releaseNotes) {
    $this->artisan('update', [
        '--release-notes' => $releaseNotes,
        '--latest-version' => 'v1.0.0',
        '--path-to-changelog' => __DIR__ . '/../Stubs/base-changelog.md',
        '--release-date' => '2021-02-01',
    ])
         ->expectsOutput(file_get_contents(__DIR__ . '/../Stubs/expected-changelog-with-shifted-headings.md'))
         ->assertSuccessful();
})->with([
    'starts with h1' => <<<MD
        # Added
        - New Feature A
        - New Feature B

        # Changed
        - Update Feature C

        ## Removes
        - Remove Feature D
        MD,
    'starts with h2' => <<<MD
        ## Added
        - New Feature A
        - New Feature B

        ## Changed
        - Update Feature C

        ### Removes
        - Remove Feature D
        MD,
]);

it('heading-text option allows user to use different heading text than latest-version when changelog contains unreleased heading', function () {
    $this->artisan('update', [
        '--release-notes' => <<<MD
        ### Added
        - New Feature A
        - New Feature B

        ### Changed
        - Update Feature C

        ### Removes
        - Remove Feature D
        MD,
        '--latest-version' => 'v1.0.0',
        '--path-to-changelog' => __DIR__ . '/../Stubs/base-changelog.md',
        '--release-date' => '2021-02-01',
        '--heading-text' => '::heading-text::',
    ])
         ->expectsOutput(file_get_contents(__DIR__ . '/../Stubs/expected-changelog-with-heading-text.md'))
         ->assertSuccessful();
});
