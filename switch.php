<?php
switch (strtoupper($cmd[0]))
{
case 'ACT':
preg_match('/^ACT (.+)$/', $input, $matches);
broadcast($client[$i]['user']['name'] . ' ACT ' . $matches[1], $i);
break;

case 'MSG':
preg_match('/^MSG (.+)$/', $input, $matches);
broadcast($client[$i]['user']['name'] . ' MSG ' . $matches[1], $i);
break;

case 'NICK':
$taken = false;
foreach ($client as $clien)
{
if ($clien['user']['name'] == $cmd[1])
{
$taken = true;
break;
}
}

if ($taken)
{
socket_write($client[$i]['sock'], "ERROR 002 That name is already taken.\r\n");
}
else
{
broadcast($client[$i]['user']['name'] . ' NICK ' . $cmd[1]);
$client[$i]['user']['name'] = $cmd[1];
}
break;

default:
socket_write($client[$i]['sock'], 'Error 404: Command ' . strtoupper($cmd[0]) . " not found.\r\n");
break;
}
?>