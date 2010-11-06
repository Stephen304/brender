<?php
date_default_timezone_set('Europe/Zurich');
function test() {
	global $qwer;
	print "qwer=$qwer\n";
}
function output($msg,$type="info") {
	# output can be customized in future for different types 
	# currently type = info, warning, error
	brender_log($msg);
	$when=date('Y/d/m H:i:s');
	$msg= "$when : $type : $msg";
	print "$msg\n";
}
function debug($msg) {
	print "## DEBUG :: $msg\n";
}
function get_client_info($client,$info) {
	send_order("$client","info","$info","80");	
	sleep(2);
	$query="select * from orders where client='server' and orders='info' limit 1";
        $results=mysql_query($query);
        $row=mysql_fetch_object($results);
        $id=$row->id;
        $client=$row->client;
	$rem=$row->rem;
        # print "$id = $client $rem\n";
        remove_order($id);
	return $rem;
	
}
function check_client_exists($client) {
	$query="select count(client) from clients where client='$client'";
	$results=mysql_query($query);
	$qq=mysql_result($results,0);
	return $qq;
}
function get_client_os($client) {
	$query="select machine_os from clients where client='$client'";
	$results=mysql_query($query);
	$qq=mysql_result($results,0);
	return $qq;
}
function get_path($project,$what,$os="NONE") {
	#$path=$what."_".$GLOBALS['os'];
	if ($os=="NONE") {$os=$GLOBALS['os'];};
	$path=$what."_".$os;
	$query="select $path from projects where name='$project'";
	$results=mysql_query($query);
	$qq=mysql_result($results,0);
	print "GETTING PATH $query \n path = $path\n";
	return $qq;
}
function get_blender_path() {
	switch($GLOBALS['os']) {
		case "mac":
			$path="blender/mac/blender.app/Contents/MacOS/blender";
			break;
		case "linux":
			$path="blender/linux/blender";
			break;
		case "windows":
			$path="blender/windows/blender.exe";
			break;

	}
	return $path;
}
function change_order_owner($id,$client) {
	$query="update orders set client=$client where id='$id'";
	# mysql_unbuffered_query($query);
	print "become order query $query\n";
	# print "### $client deleted order $id\n";
}
function new_node($node,$speed=2) {
	$query="insert into clients values ('','$node','$speed','node','0','not running','');";
	mysql_query($query);
}
function delete_node($node) {
	$query="delete from clients where name='$node'";
	mysql_query($query);
}
function remove_order($id) {
	$query="delete from orders where id='$id'";
	mysql_unbuffered_query($query);
	#$os=$GLOBALS['os'];
	#$client=$GLOBALS['computer_name'];
	 #print "### $client of $os deleted order $id\n";
}
function server_stop($pid){
	$query="update status set pid='$pid',status='stopped',started=now()";
	# print "\n query = $query ----\n";
	mysql_unbuffered_query($query);
	print "STOPPED SERVER \n";
	stop();
}
function server_start($pid){
	$query="update status set pid='$pid',status='running',started=now()";
	# 	print "\n query = $query ----\n";
	mysql_query($query);
	print "STARTED SERVER $status $rem\n";
}
function server_status(){
	# print "<br/>get server status<br/>";

	$query="select * from status";
        $results=mysql_query($query);
        while ($row=mysql_fetch_object($results)){
		$status=$row->status;
		$pid=$row->pid;
	}
        if ($status=="running"){
		$color="green";
		send_order("server","ping","","1");
		sleep(3);
        	$query="select * from orders where orders='ping' and client='server'";
        	$results=mysql_query($query);
        	while ($row=mysql_fetch_object($results)){
			set_server_status("status","died");
			set_server_status("pid","0");
			set_server_status("started","now()");
                	$id=$row->id;
			brender_log("server not responding (PING)");
			brender_log("SERVER DIED");
                	remove_order($id);
			print "SERVER DEAD!!!!!!!!<br/>";
			$color="red";
        	}
	}
        else {
		$color="red";
	}
		print "<font color=$color>$status $pid</font>\n";
}
function set_server_status($key,$value){
	$query="update status set $key='$value'";
	mysql_unbuffered_query($query);
	#	print "### $client status : $status $rem\n";
}
function set_status($client,$status,$rem){
	$rem=str_replace("'","\'",$rem);
	$query="update clients set status='$status',rem='$rem' where client='$client'";
	# print "COOOL DEBUG___________ $query\n";
	mysql_unbuffered_query($query);
	#	print "### $client status : $status $rem\n";
}
function send_order($client,$orders,$rem,$priority){
	#print "------send_order var = $client, $orders, $rem, $priority----\n";
	$query="insert into orders values('','$client','$orders','$priority','$rem')";
	# print "order query = $query\n";
	mysql_unbuffered_query($query);
}
function brender_log($log){
	$computer_name=$GLOBALS['computer_name'];
	$heure=date('Y/d/m H:i:s');
	$log_koi = "$heure $computer_name: $log\n";
	#print "\n---------------------- I AM LOGGING THIS ::: $log_koi-----end ----\n";
	$foo=fopen("logs/$computer_name.log",a);
            fwrite($foo,"$log_koi");
        fclose($foo);
	$foo=fopen("logs/brender.log",a);
            fwrite($foo,"$log_koi");
        fclose($foo);
}
function checking_alive_clients() {
	# print "i am checking alive clients ";
	$query="select * from clients where status='idle' or status='disabled'";
        $results=mysql_query($query);
        while ($row=mysql_fetch_object($results)){
                $id=$row->id;
                $client=$row->client;
                $status=$row->status;
                send_order($client,"ping","","15");
                print "pinging $client...\n";
        }
        sleep(2);
        $query="select * from orders where orders='ping'";
        $results=mysql_query($query);
        while ($row=mysql_fetch_object($results)){
                $id=$row->id;
                $client=$row->client;
                print "$id = $client is dead\n";
                set_status("$client","not running",'client not responding (PING)');
		brender_log("$client not responding (PING)");
                remove_order($id);
        }
}
function seconds_to_hms($time_in_secs) {
   $secs = $time_in_secs % 60;
   $time_in_secs -= $secs;
   $time_in_secs /= 60;

   $mins = $time_in_secs % 60;
   $time_in_secs -= $mins;
   $time_in_secs /= 60;

   $hours = $time_in_secs % 24;
   $time_in_secs -= $hours;
   $time_in_secs /= 24;

   $days= $time_in_secs;
	if (!$days) {
		return str_pad($hours,2,'0',STR_PAD_LEFT) . ":" . str_pad($mins,2,'0',STR_PAD_LEFT) . ":" . str_pad($secs,2,'0',STR_PAD_LEFT);
	}
	else {
		return ($days." days ".str_pad($hours,2,'0',STR_PAD_LEFT) . ":" . str_pad($mins,2,'0',STR_PAD_LEFT) . ":" . str_pad($secs,2,'0',STR_PAD_LEFT));
	}
}
function get_rendered_frames($job_id) {
		$query="select scene,shot,project,start,end,filetype from jobs where id='$job_id'";
		#print "query === $query <br/>";
		$results=mysql_query($query);
		$row=mysql_fetch_object($results);
		$output=$row->output;
		$filetype=$row->filetype;
		$project=$row->project;
		$end=$row->end;
		$name=$row->name;
		$a=$row->start-1;
                # print " i check $a to $end $output###.$filetype<br/>";
                while ($a<$end){
                        $a++;
                        $filecheck="/brender/render/$project/$name/".$output.str_pad($a,4,0,STR_PAD_LEFT).".".$filetype;
                        if (file_exists($filecheck)) {
                                $ok="ok";
                                $total++;
                        }
                        else {
                                $ok="";
                        }
               }
		# print "total $total<br/>";
              return $total;

}

?>
