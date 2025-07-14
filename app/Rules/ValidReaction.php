<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidReaction implements ValidationRule
{

    private const VALID_REACTIONS = [
        'like',
        'dislike',
        'love',
        'haha',
        'wow',
        'sad',
        'angry'
    ];
    /**
     * Run the validation rule.
     * 
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!in_array(strtolower($value), self::VALID_REACTIONS, true)) {
            $fail('The :attribute must be one of: ' . implode(', ', self::VALID_REACTIONS));
        }
    }
}
