<?php

declare(strict_types=1);

namespace UAMSWeb\Aether\Forms\Settings\Support;

use Statamic\Contracts\Forms\Submission;
use Statamic\Facades\Site;
use Statamic\Sites\Site as StatamicSite;
use UAMSWeb\Aether\Forms\Settings\Contracts\SubmissionSite;

/**
 * The default {@see SubmissionSite}: the site Statamic reports as current.
 *
 * A single-site installation has only one answer. A host that serves several
 * sites binds an implementation that reads the submitting page's site.
 */
final class CurrentSubmissionSite implements SubmissionSite
{
    /**
     * The current site, whatever the submission.
     *
     * @param  Submission|null  $submission  Ignored: the current site is not read from the submission.
     * @return StatamicSite The current site.
     */
    #[\Override]
    public function forSubmission(?Submission $submission = null): StatamicSite
    {
        return Site::current();
    }
}
