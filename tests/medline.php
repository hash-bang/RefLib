<?
$dir = dirname(__FILE__);
require_once("$dir/../reflib.php");
$rl = new RefLib();
$rl->SetContentFile("$dir/data/medline.nbib");
$got = count($rl->refs);
$want = 10;
echo ($got == $want ? 'PASS' : 'FAIL') . " - 10 references read from MedLine nbib file\n";
$got = substr_count($rl->GetContents(), "\n");
$want = 371;
echo ($got == $want ? 'PASS' : 'FAIL') . " - Same file size out output. Got: $got, Want: $want\n";
$rl->SetContentFile("$dir/data/medline.txt");
$got = count($rl->refs);
$want = 10 + 3;
echo ($got == $want ? 'PASS' : 'FAIL') . " - $want references read from Medline txt file. Got: $got, Want: $want\n";
