<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class PermissionRule implements ValidationRule
{


    private const VALID_PERMISSIONS = [
        'Library',
        'Nurse',
        'Oversee',
    ];
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
         if (!in_array(strtolower($value), self::VALID_PERMISSIONS, true)) {
            $fail('The :Permission must be one of: ' . implode(', ', self::VALID_PERMISSIONS));
        }
    }
}
