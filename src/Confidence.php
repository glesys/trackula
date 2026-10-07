<?php

declare(strict_types=1);

namespace Glesys\Trackula;

enum Confidence: string
{
    /** Distinctive format, verified check digit, and no other carrier verifies. */
    case Certain = 'certain';

    /** Verified check digit or distinctive format, but other carriers remain possible. */
    case Likely = 'likely';

    /** The pattern matches, nothing more. */
    case Possible = 'possible';
}
