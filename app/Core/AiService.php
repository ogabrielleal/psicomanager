<?php
declare(strict_types=1);

namespace App\Core;

final class AiService
{
    public function enabled(): bool
    {
        return filter_var(env('AI_ENABLED','false'),FILTER_VALIDATE_BOOL)&&trim((string)env('AI_API_KEY',''))!=='';
    }

    public function ask(string $prompt,string $mode='soap',array $knownNames=[],int $tenantId=0): array
    {
        if(!$this->enabled())return ['ok'=>false,'error'=>'IA ainda não configurada. Defina AI_ENABLED=true e a chave da API no .env.'];
        $safePrompt=PiiSanitizer::sanitize($prompt,$knownNames);
        $sources=$this->retrieve($safePrompt,$tenantId);
        $context='';
        foreach($sources as $i=>$src){$safeTitle=PiiSanitizer::sanitize((string)$src['title'],$knownNames);$safeContent=PiiSanitizer::sanitize((string)$src['content'],$knownNames);$context.="\n[FONTE ".($i+1)."] {$safeTitle}\n{$safeContent}\n";}

        $system=match($mode){
            'rag'=>'Você é um copiloto científico para psicólogos. Responda somente com base nas fontes fornecidas. Se as fontes não sustentarem algo, diga explicitamente. Não invente referências.',
            'audit'=>'Você audita a estrutura formal de documentos psicológicos. Não substitua julgamento profissional, não faça diagnóstico autônomo e sinalize incertezas.',
            'longitudinal'=>'Você sintetiza padrões ao longo do tempo sem inferir causalidade não sustentada. Preserve linguagem clínica cautelosa.',
            default=>'Você organiza notas clínicas em estrutura SOAP, preservando fatos e marcando como hipótese tudo que não estiver explicitamente sustentado.'
        };
        $finalPrompt=$system."\n\nCONTEXTO CIENTÍFICO/REGULATÓRIO:\n".($context?:'[nenhuma fonte interna recuperada]')
            ."\n\nSOLICITAÇÃO ANONIMIZADA:\n".$safePrompt;

        if((string)env('AI_PROVIDER','gemini')!=='gemini')return ['ok'=>false,'error'=>'Nesta versão Hostoo, o conector implementado é Gemini.'];
        $model=rawurlencode((string)env('AI_MODEL','gemini-3.6-flash'));
        $key=rawurlencode((string)env('AI_API_KEY'));
        $endpoint="https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$key}";
        $payload=json_encode(['contents'=>[['role'=>'user','parts'=>[['text'=>$finalPrompt]]]],'generationConfig'=>['temperature'=>0.2,'maxOutputTokens'=>1800]],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);

        $ch=curl_init($endpoint);
        curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_POST=>true,CURLOPT_HTTPHEADER=>['Content-Type: application/json'],CURLOPT_POSTFIELDS=>$payload,CURLOPT_TIMEOUT=>(int)env('AI_TIMEOUT',45)]);
        $raw=curl_exec($ch);$status=(int)curl_getinfo($ch,CURLINFO_HTTP_CODE);$error=curl_error($ch);curl_close($ch);
        if($raw===false||$status>=400)return ['ok'=>false,'error'=>'Falha no provedor de IA. '.($error?:'HTTP '.$status)];
        $data=json_decode($raw,true);$text=$data['candidates'][0]['content']['parts'][0]['text']??null;
        if(!is_string($text)||trim($text)==='')return ['ok'=>false,'error'=>'O provedor não retornou conteúdo utilizável.'];
        return ['ok'=>true,'text'=>$text,'sources'=>$sources,'sanitized_prompt'=>$safePrompt];
    }

    private function retrieve(string $query,int $tenantId): array
    {
        if($tenantId<1)return [];
        $tokens=preg_split('/\s+/u',mb_strtolower($query))?:[];
        $tokens=array_values(array_filter($tokens,fn($t)=>mb_strlen($t)>=4));
        $tokens=array_slice(array_unique($tokens),0,8);
        if(!$tokens)return [];
        $where=[];$params=['tenant'=>$tenantId];
        foreach($tokens as $i=>$token){
            $titleKey="q{$i}_title";
            $contentKey="q{$i}_content";
            $where[]="(LOWER(title) LIKE :{$titleKey} OR LOWER(content) LIKE :{$contentKey})";
            $params[$titleKey]='%'.$token.'%';
            $params[$contentKey]='%'.$token.'%';
        }
        $sql="SELECT id,title,source_url,content FROM knowledge_chunks
            WHERE (tenant_id=:tenant OR tenant_id IS NULL) AND active=1 AND (".implode(' OR ',$where).")
            ORDER BY is_official DESC,updated_at DESC LIMIT 5";
        $st=db()->prepare($sql);$st->execute($params);return $st->fetchAll();
    }
}
