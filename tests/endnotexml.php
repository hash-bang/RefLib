<?
$dir = dirname(__FILE__);
require("$dir/../reflib.php");

$rl = new RefLib();
$rl->SetContentFile("$dir/data/endnote.xml");
echo "References: " . count($rl->refs);
