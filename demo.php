<?php
include_once("Pcc.php");
$p = new Pcc('demoProject');
$p->run();

//..... you want Coverage Code,start
function testInterface($testCase)
{
    switch($testCase)
    {
        case '1':
            $out = '$testCase = 1';
            break;
        case '2':
            $out = '$testCase = 2';
            break;
        default:
            $out = '$testCase <> 1 && $testCase <> 2';
            break;

    }
}
testInterface(1);
//....you want Coverage Code,end
//....

?>
