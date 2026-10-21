<?php

namespace App\Policies;

/**
 * Intentionally has no methods of its own: every rule is inherited from AuthoredRecordPolicy.
 * (An empty policy class returns no abilities; this one works because the parent defines them.)
 */
class StudentNotePolicy extends AuthoredRecordPolicy
{
}
