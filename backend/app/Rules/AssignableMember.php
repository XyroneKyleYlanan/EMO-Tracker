<?php

namespace App\Rules;

use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Anyone in the office can be given a task, whatever their role: in a small
 * office the head and officers handle events too. Only active accounts can be
 * newly assigned; someone already assigned stays valid even if deactivated
 * since, so editing an old record still works.
 */
class AssignableMember implements ValidationRule
{
    public function __construct(private array $alreadyAssigned = []) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (in_array((int) $value, $this->alreadyAssigned, true)) {
            return;
        }

        if (! User::whereKey($value)->where('is_active', true)->exists()) {
            $fail('Only active members can be assigned.');
        }
    }
}
