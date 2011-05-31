<?php
// Set time limit to indefinite execution
set_time_limit (0);

// Set the ip and port we will listen on
$address = '127.0.0.1';
$port = 9000;
$max_clients = 10;

$clients = array();

$sock = socket_create(AF_INET, SOCK_STREAM, 0);
socket_bind($sock, $address, $port);
socket_listen($sock);

function broadcast($string, $except = -1)
{
	global $max_clients, $client;
	echo $string . PHP_EOL;
	for ($i = 0; $i < $max_clients; $i++)
	{
		if ($client[$i]['sock'] && $client[$i]['user']['name'] && $i !== $except)
		{
			socket_write($client[$i]['sock'], $string . "\r\n");
		}
	}
}

while (true)
{
	$read = array();
	$read[0] = $sock;
	for ($i = 0; $i < $max_clients; $i++)
	{
		if ($client[$i]['sock'] != null)
		{
			$read[$i + 1] = $client[$i]['sock'] ;
		}
	}

	$ready = socket_select($read, $write = null, $except = null, $tv_sec = null);
	if (in_array($sock, $read))
	{
		for ($i = 0; $i < $max_clients; $i++)
		{
			if ($client[$i]['sock'] == null)
			{
				$client[$i]['sock'] = socket_accept($sock);
				socket_write($client[$i]['sock'], "Welcome. Please use \"NICK yournick\" to get started.\r\n");
				echo 'Accepted socket.' . PHP_EOL;
				break;
			}
			else if ($i == $max_clients - 1)
			{
				echo 'Too many clients.' . PHP_EOL;
			}
		}
		if (--$ready <= 0)
		{
			continue;
		}
	}

	for ($i = 0; $i < $max_clients; $i++)
	{
		if (in_array($client[$i]['sock'] , $read))
		{
			$input = socket_read($client[$i]['sock'] , 1024, PHP_NORMAL_READ);
			if ($input == null)
			{
				broadcast('QUIT ' . $client[$i]['user']['name']);
				$fp = fopen("./server.users", "r");
				$users = fgets($fp);
				fclose($fp);
				$fp2 = fopen("./server.users", "w");
				$users = explode(" ", $users);
				foreach($users as $user){
					if($user != $client[$i]['user']['name']){
						fwrite($user, $fp2." ");
					}
				}
				fclose($fp2);
				unset($client[$i]);
				echo 'Unset socket.' . PHP_EOL;
			}
			$input = trim($input);
			if ($input == 'exit')
			{
				broadcast('QUIT ' . $client[$i]['quit']['name']);
								$fp = fopen("./server.users", "r");
				$users = fgets($fp);
				fclose($fp);
				$fp2 = fopen("./server.users", "w");
				$users = explode(" ", $users);
				foreach($users as $user){
					if($user != $client[$i]['user']['name']){
						fwrite($user, $fp2." ");
					}
				}
				socket_close($client[$i]['sock']);
				unset($client[$i]);
				echo 'Closed socket.' . PHP_EOL;
			}
			else if ($input !== '')
			{
				$cmd = explode(' ', $input);
				if (!isset($client[$i]['user']['name']))
				{
					if (strtoupper($cmd[0]) == 'NICK')
					{
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
							socket_write($client[$i]['sock'], "ERROR 002 That name is already taken. Try again.\r\n");
						}
						else
						{
							$client[$i]['user']['name'] = $cmd[1];
							broadcast('JOIN ' . $cmd[1]);
							$fp = fopen("./server.users", "a+");
							fwrite($client[$i]['user']['name'], $fp);
							fclose($fp);
						}
					}
					else
					{
						socket_write($client[$i]['sock'], "ERROR 001 No NICK specified. Please specify your NICK using \"NICK yournick\".\r\n");
					}
					continue;
				}

			include("./switch.php");
			}
		}
	}
}

socket_close($sock);
?>