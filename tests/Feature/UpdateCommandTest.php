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
        '--latest-commit-hash' => 'fdfd8fa3c00b02a1f209be0fb0508f9dd6f173fe',
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
        '--latest-commit-hash' => 'fdfd8fa3c00b02a1f209be0fb0508f9dd6f173fe',
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
        '--latest-commit-hash' => 'fdfd8fa3c00b02a1f209be0fb0508f9dd6f173fe',
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
        '--latest-commit-hash' => 'fdfd8fa3c00b02a1f209be0fb0508f9dd6f173fe',
        '--path-to-changelog' => __DIR__ . '/../Stubs/base-changelog.md',
        '--release-date' => '',
    ])
         ->expectsOutput($expectedOutput)
         ->assertSuccessful();
});

it('places given release notes in correct position in given markdown changelog when no unreleased heading is available', function () {
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
        '--latest-commit-hash' => 'fdfd8fa3c00b02a1f209be0fb0508f9dd6f173fe',
        '--path-to-changelog' => __DIR__ . '/../Stubs/base-changelog-without-unreleased.md',
        '--release-date' => '2021-02-01',
    ])
         ->expectsOutput(file_get_contents(__DIR__ . '/../Stubs/expected-changelog-without-unreleased.md'))
         ->assertSuccessful();
});

it('places given release notes in correct position in given markdown changelog when no heading is available', function () {
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
        '--latest-commit-hash' => 'fdfd8fa3c00b02a1f209be0fb0508f9dd6f173fe',
        '--path-to-changelog' => __DIR__ . '/../Stubs/base-changelog-without-headings.md',
        '--release-date' => '2021-02-01',
    ])
         ->expectsOutput(file_get_contents(__DIR__ . '/../Stubs/expected-changelog-without-headings.md'))
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
        '--latest-commit-hash' => 'fdfd8fa3c00b02a1f209be0fb0508f9dd6f173fe',
        '--path-to-changelog' => __DIR__ . '/../Stubs/base-changelog.md',
        '--release-date' => '2021-02-01',
        '--compare-url-target-revision' => '1.x',
    ])
         ->expectsOutput('CHANGELOG was not updated as release notes for v0.1.0 already exist.')
         ->assertSuccessful();
});

it('nothing happens if no release notes have been given and no unreleased heading can be found', function () {
    $this->artisan('update', [
        '--latest-version' => 'v1.0.0',
        '--latest-commit-hash' => 'fdfd8fa3c00b02a1f209be0fb0508f9dd6f173fe',
        '--path-to-changelog' => __DIR__ . '/../Stubs/base-changelog-without-unreleased.md',
        '--release-date' => '2021-02-01',
        '--compare-url-target-revision' => '1.x',
    ])
         ->expectsOutput('Release Notes were not provided. Pass them through the `--release-notes`-option.')
         ->assertFailed();
});

test('it shows warning if changelog is empty and content can not be placed', function () {
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
        '--latest-commit-hash' => 'fdfd8fa3c00b02a1f209be0fb0508f9dd6f173fe',
        '--path-to-changelog' => __DIR__ . '/../Stubs/empty-changelog.md',
        '--compare-url-target-revision' => 'HEAD',
    ])
         ->expectsOutput('Release notes could not be placed. Is the CHANGELOG empty? Does it contain at least one heading?')
         ->assertFailed();
});

test('it automatically shifts heading levels to be level 3 headings to fit into the existing changelog', function ($releaseNotes) {
    $this->artisan('update', [
        '--release-notes' => $releaseNotes,
        '--latest-version' => 'v1.0.0',
        '--latest-commit-hash' => 'fdfd8fa3c00b02a1f209be0fb0508f9dd6f173fe',
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

it('heading-text option allows user to use different heading text than latest-version when changelog does not contain unreleased heading', function () {
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
        '--latest-commit-hash' => 'fdfd8fa3c00b02a1f209be0fb0508f9dd6f173fe',
        '--path-to-changelog' => __DIR__ . '/../Stubs/base-changelog-without-unreleased.md',
        '--release-date' => '2021-02-01',
        '--heading-text' => '::heading-text::',
    ])
         ->expectsOutput(file_get_contents(__DIR__ . '/../Stubs/expected-changelog-without-unreleased-with-heading-text.md'))
         ->assertSuccessful();
});
