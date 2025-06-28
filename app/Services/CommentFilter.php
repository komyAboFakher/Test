<?php
// app/Services/CommentFilter.php
namespace App\Services;

class CommentFilter
{
    private $bannedWords = [
        'idiot',
        'stupid',
        'hate',
        'loser',
        // Add 50-100 common insults (find lists online)
    ];
    private $substitutionPatterns = [
        '/\*/' => 'u', // replaces f**k → fuck
        '/1/' => 'i',
        '/0/' => 'o',
        '/@/' => 'a',
        '/\$/' => 's',

    ];

    private $bannedPatterns = [
        '/f[\W_]*[u\*]+[\W_]*[c]+[\W_]*[k]+/i',
        '/i[\W_]*[d1\|]+[\W_]*[i]+[\W_]*[o0]+[\W_]*[t]+/i',
        '/@[\W_]*[s\$]+[\W_]*[s\$]+/i',
        // Add more patterns as needed
    ];



    private function normalize(string $text): array
    {
        $text = strtolower($text);
        foreach ($this->substitutionPatterns as $pattern => $replacement) {
            $text = preg_replace($pattern, $replacement, $text);
        }

        $noSymbols = preg_replace('/[^a-z]/', '', $text);
        $noSpaces = str_replace(' ', '', $text);

        return [$noSymbols, $noSpaces];
    }




    public function isClean(string $comment): bool
    {
        [$noSymbols, $noSpaces] = $this->normalize($comment);

        foreach ($this->bannedWords as $word) {
            if (str_contains($noSymbols, $word) || str_contains($noSpaces, $word)) {
                return false;
            }
        }

        return true;
    }
}
