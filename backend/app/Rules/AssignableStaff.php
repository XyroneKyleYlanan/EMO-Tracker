<?php

namespace App\Rules;

use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Only active staff can be newly assigned. Users who are already assigned
 * stay valid even if deactivated since, so editing an old record still works.
 */
class AssignableStaff implements ValidationRule
{
    public function __construct(private array $alreadyAssigned = []) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (in_array((int) $value, $this->alreadyAssigned, true)) {
            return;
        }

        $assignable = User::whereKey($value)
            ->where('role', 'staff')
            ->where('is_active', true)
            ->exists();

        if (! $assignable) {
            $fail('Only active staff members can be assigned.');
        }
    }
}
