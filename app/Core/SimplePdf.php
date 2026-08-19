<?php
declare(strict_types=1);
namespace App\Core;
final class SimplePdf{
 public static function output(array $lines,string $filename='recibo.pdf'): never{
  $y=790;$content="BT\n/F1 12 Tf\n";foreach($lines as $line){$line=self::safe((string)$line);$content.="1 0 0 1 55 {$y} Tm (".self::esc($line).") Tj\n";$y-=22;} $content.="ET";
  $objects=[];$objects[]="1 0 obj<< /Type /Catalog /Pages 2 0 R >>endobj";$objects[]="2 0 obj<< /Type /Pages /Kids [3 0 R] /Count 1 >>endobj";$objects[]="3 0 obj<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 5 0 R >> >> /Contents 4 0 R >>endobj";$objects[]="4 0 obj<< /Length ".strlen($content)." >>stream\n{$content}\nendstream\nendobj";$objects[]="5 0 obj<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>endobj";
  $pdf="%PDF-1.4\n";$offsets=[0];foreach($objects as $o){$offsets[]=strlen($pdf);$pdf.=$o."\n";}$xref=strlen($pdf);$pdf.="xref\n0 ".(count($objects)+1)."\n0000000000 65535 f \n";for($i=1;$i<=count($objects);$i++)$pdf.=sprintf("%010d 00000 n \n",$offsets[$i]);$pdf.="trailer<< /Size ".(count($objects)+1)." /Root 1 0 R >>\nstartxref\n{$xref}\n%%EOF";header('Content-Type: application/pdf');header('Content-Disposition: attachment; filename="'.preg_replace('/[^a-zA-Z0-9._-]/','_',$filename).'"');header('Content-Length: '.strlen($pdf));echo $pdf;exit;
 }
 private static function safe(string $s): string{if(!function_exists('iconv'))return $s;$x=iconv('UTF-8','Windows-1252//TRANSLIT//IGNORE',$s);return $x===false?$s:$x;}
 private static function esc(string $s): string{return str_replace(['\\','(',')'],['\\\\','\\(','\\)'],$s);}
}
