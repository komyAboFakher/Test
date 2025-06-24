<?php

namespace App\Rules;


use Illuminate\Contracts\Validation\Rule;

class validateDate implements Rule
{
    public function passes($attribute, $value)
    {
        $formats = ['m/d/Y', 'n/j/Y', 'Y-m-d']; // m=zero-padded, n=non-zero-padded
        foreach ($formats as $format) {
            $date = \DateTime::createFromFormat($format, $value);
            if ($date && $date->format($format) == $value) {
                return true;
            }
        }

        return false;
    }

    public function message()
    {
        return 'The :attribute must be a valid date in M/D/YYYY or MM/DD/YYYY format.';
    }
}
