<?php
#!/usr/bin/php -q

/* do NOT run this script through a web browser */
if (!isset($_SERVER["argv"][0]) || isset($_SERVER['REQUEST_METHOD'])  || isset($_SERVER['REMOTE_ADDR'])) {
   die("<br><strong>This script is only meant to run at the command line.</strong>");
}

$no_http_headers = true;

/* display No errors */
error_reporting(0);

include_once(dirname(__FILE__) . "/../include/global.php");
include_once(dirname(__FILE__) . "/../lib/snmp.php");
include_once(dirname(__FILE__) . "/../lib/ping.php");

if (!isset($called_by_script_server)) {
	array_shift($_SERVER["argv"]);
	print call_user_func_array("ss_fping", $_SERVER["argv"]);
}
//End header.

function ss_fping($hostname, $ping_sweeps=6, $ping_type="ICMP", $port=80) {
	/* record start time */
	list($micro,$seconds) = split(" ", microtime());
	$ss_fping_start = $seconds + $micro;

	$ping = new Net_Ping;

	$time = array();
	$total_time = 0;
	$failed_results = 0;

	$ping->host["hostname"] = gethostbyname($hostname);
	$ping->retries = 1;
	$ping->port = $port;
	$max = 0.0;
	$min = 9999.99;
	$dev = 0.0;

	$script_timeout = read_config_option("script_timeout");
	$ping_timeout = read_config_option("ping_timeout");

	switch ($ping_type) {
	case "ICMP":
		$method = PING_ICMP;
		break;
	case "TCP":
		$method = PING_TCP;
		break;
	case "UDP":
		$method = PING_UDP;
		break;
	}

	//
	// use ping utility if protocol ICMP and user not root
	//
	if (file_exists("/usr/sbin/fping") && posix_geteuid() && $ping_type == "ICMP" ) {
            $ssping_out = '';
            $ping_init_cmdline = "/usr/sbin/fping -p 250 -q -c 3 -t 1000";
            $ping_cmdline = "/usr/sbin/fping -p 250 -q -c " . ((int) $ping_sweeps);
            if ( isset($called_by_script_server) ) {
                $to = $script_timeout * 1000 / 20;
                $ping_cmdline .= " -t " . ((int)$to);
            } else {
                $ping_cmdline .= " -t " . ((int)$ping_timeout);
            }

            $ping_init_cmdline .= ' ' . escapeshellarg($hostname);
            $ping_cmdline .= ' ' . escapeshellarg($hostname);

            $dummy = exec("$ping_init_cmdline 2>&1");
            $result = exec("$ping_cmdline 2>&1", $out, $ret_val);
            if ($ret_val) {
                return "loss:100.00";
            }

            if (ereg ("xmt/rcv/%loss = [0-9]+/[0-9]+/([0-9]+)%", $result, $regs)) {
                $loss = $regs[1];
            }

            if (ereg ("min/avg/max = ([0-9.]+)/([0-9.]+)/([0-9.]+)", $result, $regs)) {
                $min = $regs[1];
                $avg = $regs[2];
                $max = $regs[3];
                $dev = ($max - $min) / 2;
                return sprintf("min:%0.4f avg:%0.4f max:%0.4f dev:%0.4f loss:%0.4f", $min, $avg, $max, $dev, $loss);
            }
            return "loss:100.00";
    }

	$i = 0;
	while ($i < $ping_sweeps) {
		$result = $ping->ping(AVAIL_PING,
					$method,
					read_config_option("ping_timeout"),
					1);

		if (!$result) {
			$failed_results++;
		}else{
			$time[$i] = $ping->ping_status;
			$total_time += $ping->ping_status;
			if ($ping->ping_status < $min) $min = $ping->ping_status;
			if ($ping->ping_status > $max) $max = $ping->ping_status;
		}

		$i++;

		/* get current time */
		list($micro,$seconds) = split(" ", microtime());
		$ss_fping_current = $seconds + $micro;

		/* if called from script server, end one second before a timeout occurs */
		if ((isset($called_by_script_server)) && (($ss_fping_current - $ss_fping_start + ($ping_timeout/1000) + 1) > $script_timeout)) {
			$ping_sweeps = $i;
			break;
		}
	}

	if ($failed_results == $ping_sweeps) {
		return "loss:100.00";
	}else{
		$loss = ($failed_results/$ping_sweeps) * 100;
		$avg = $total_time/($ping_sweeps-$failed_results);

		/* calculate standard deviation */
		$predev = 0;
		foreach($time as $sample) {
			$predev += pow(($sample-$avg),2);
		}
		$dev = sqrt($predev / count($time));

		return sprintf("min:%0.4f avg:%0.4f max:%0.4f dev:%0.4f loss:%0.4f", $min, $avg, $max, $dev, $loss);
	}
}
?>