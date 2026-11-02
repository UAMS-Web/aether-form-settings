<?php

declare(strict_types=1);

namespace UAMSWeb\Aether\Forms\Settings\Contracts;

use Statamic\Contracts\Forms\Submission;
use Statamic\Sites\Site;
use UAMSWeb\Aether\Forms\Settings\Support\CurrentSubmissionSite;

/**
 * Names the site a form submission was made from.
 *
 * A form posts to a single endpoint, so the request's own site says nothing
 * about the page the form was on. The submission data feature records the site
 * with each submission and with the captured request context, and asks this
 * contract for it, so an add-on that knows more (the site of the submitting
 * page, recorded when the submission was built) can answer without this add-on
 * depending on it.
 *
 * The add-on binds {@see CurrentSubmissionSite}, which answers with the
 * current site. A host that names the submitting page's site binds its own
 * implementation.
 */
interface SubmissionSite
{
    /**
     * The site a submission was made from.
     *
     * @param  Submission|null  $submission  The submission being saved, or null while the
     *                                       submit request is captured before one exists.
     * @return Site The site the submission belongs to.
     */
    public function forSubmission(?Submission $submission = null): Site;
}
