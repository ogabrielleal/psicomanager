<?php
declare(strict_types=1);

namespace App\Core;

final class PiiSanitizer
{
    public static function sanitize(string $text,array $knownNames=[]): string
    {
        foreach(array_filter($knownNames) as $i=>$name){
            $text=preg_replace('/\b'.preg_quote((string)$name,'/').'\b/iu','$$PACIENTE_'.chr(65+($i%26)).'$$',$text)??$text;
        }
        $patterns=[
            '/\b\d{3}\.?\d{3}\.?\d{3}-?\d{2}\b/'=>'$$CPF_MASCARADO$$',
            '/\b[\w.%+\-]+@[\w.\-]+\.[A-Z]{2,}\b/iu'=>'$$EMAIL_MASCARADO$$',
            '/\b(?:\+?55\s?)?(?:\(?\d{2}\)?\s?)?9?\d{4}[-\s]?\d{4}\b/'=>'$$TELEFONE_MASCARADO$$',
            '/\b\d{2}\/\d{2}\/\d{4}\b/'=>'$$DATA_MASCARADA$$',
            '/\b\d{5}-?\d{3}\b/'=>'$$CEP_MASCARADO$$'
        ];
        foreach($patterns as $pattern=>$replacement)$text=preg_replace($pattern,$replacement,$text)??$text;
        return $text;
    }
}
