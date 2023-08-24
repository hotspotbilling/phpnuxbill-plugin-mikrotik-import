<?php

use PEAR2\Net\RouterOS;

register_menu("Mikrotik Import", true, "mikrotik_import_ui", 'SETTINGS', '');

function mikrotik_import_ui()
{
    global $ui;
    _admin();
    $ui->assign('_title', 'Mikrotik Import');
    $ui->assign('_system_menu', 'settings');
    $admin = Admin::_info();
    $ui->assign('_admin', $admin);
    $ui->display('mikrotik_import.tpl');
}

function mikrotik_import_start_ui()
{
    global $ui;
    ini_set('max_execution_time', 0);
    set_time_limit(0);
    _admin();
    $ui->assign('_title', 'Mikrotik Start Import');
    $ui->assign('_system_menu', 'settings');
    $admin = Admin::_info();
    $ui->assign('_admin', $admin);

    $type = $_POST['type'];

    // get mikrotik info
    $mikrotik = ORM::for_table('tbl_routers')->where('name', $_POST['server'])->find_one();
    if($type=='Hotspot'){
        $results = mikrotik_import_mikrotik_hotspot_package($_POST['server'], $mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);
    }else if($type=='PPPOE'){
        $results = mikrotik_import_mikrotik_ppoe_package($_POST['server'], $mikrotik['ip_address'], $mikrotik['username'], $mikrotik['password']);
    }
    $ui->assign('results', $results);
    $ui->display('mikrotik_import_start.tpl');
}

function mikrotik_import_mikrotik_hotspot_package($router, $ip, $user, $pass)
{
    $client = Mikrotik::getClient($ip, $user, $pass);
    // import Hotspot Profile to package
    $printRequest = new RouterOS\Request(
        '/ip hotspot user profile print'
    );
    $results = [];
    $profiles = $client->sendSync($printRequest)->toArray();
    foreach ($profiles as $p) {
        $name = $p->getProperty('name');
        $rateLimit = $p->getProperty('rate-limit');
        $sharedUser = $p->getProperty('shared-user');

        // 10M/10M
        $rateLimit = explode(" ", $rateLimit)[0];
        if (strlen($rateLimit) > 1) {
            // Create Bandwidth profile
            $rate = explode("/", $rateLimit);
            $unit_up = preg_replace("/[^a-zA-Z]+/", "", $rate[0]) . "bps";
            $unit_down = preg_replace("/[^a-zA-Z]+/", "", $rate[1]) . "bps";
            $rate_up = preg_replace("/[^0-9]+/", "", $rate[0]);
            $rate_down = preg_replace("/[^0-9]+/", "", $rate[1]);
            $bw_name = str_replace("/", "_", $rateLimit);
            $bw = ORM::for_table('tbl_bandwidth')->where('name_bw', $bw_name)->find_one();
            if (!$bw) {
                $results[] = "Bandwith Created: $bw_name";
                $d = ORM::for_table('tbl_bandwidth')->create();
                $d->name_bw = $bw_name;
                $d->rate_down = $rate_down;
                $d->rate_down_unit = $unit_down;
                $d->rate_up = $rate_up;
                $d->rate_up_unit = $unit_up;
                $d->save();
                $bw_id = $d->id();
            }else{
                $results[] = "Bandwith Exists: $bw_name";
                $bw_id = $bw->id;
            }

            // Create Packages
            $pack = ORM::for_table('tbl_plans')->where('name_plan', $name)->find_one();
            if(!$pack){
                $results[] = "Packages Created: $name";
                $d = ORM::for_table('tbl_plans')->create();
                $d->name_plan = $name;
                $d->id_bw = $bw_id;
                $d->price = '10000';
                $d->type = 'Hotspot';
                $d->typebp = 'Unlimited';
                $d->limit_type = 'Time_Limit';
                $d->time_limit = 0;
                $d->time_unit = 'Hrs';
                $d->data_limit = 0;
                $d->data_unit = 'MB';
                $d->validity = '30';
                $d->validity_unit = 'Days';
                $d->shared_users = $sharedUser;
                $d->routers = $router;
                $d->enabled = 1;
                $d->save();
            }else{
                $results[] = "Packages Exists: $name";
            }
        }
    }
    // Import user
    $userRequest = new RouterOS\Request(
        '/ip hotspot user print'
    );
    $users = $client->sendSync($userRequest)->toArray();
    foreach ($users as $u) {
        $username = $u->getProperty('name');
        if(!empty($username) && !empty($u->getProperty('password'))){
            $d = ORM::for_table('tbl_customers')->where('username', $username)->find_one();
            if($d){
                $results[] = "Username Exists: $username";
            }else{
                $d = ORM::for_table('tbl_customers')->create();
                $d->username = $username;
                $d->password = $u->getProperty('password');
                $d->fullname = $username;
                $d->address = '';
                $d->email = (empty($u->getProperty('email')))?'':$u->getProperty('email');
                $d->phonenumber = '';
                if ($d->save()) {
                    $results[] = "$username added successfully";
                }else{
                    $results[] = "$username Failed to be added";
                }
            }
        }
    }
    return $results;
}


function mikrotik_import_mikrotik_ppoe_package($router, $ip, $user, $pass)
{
    $client = Mikrotik::getClient($ip, $user, $pass);
    // import Hotspot Profile to package
    $printRequest = new RouterOS\Request(
        '/ppp profile print'
    );
    $results = [];
    $profiles = $client->sendSync($printRequest)->toArray();
    foreach ($profiles as $p) {
        $name = $p->getProperty('name');
        $rateLimit = $p->getProperty('rate-limit');

        // 10M/10M
        $rateLimit = explode(" ", $rateLimit)[0];
        if (strlen($rateLimit) > 1) {
            // Create Bandwidth profile
            $rate = explode("/", $rateLimit);
            $unit_up = preg_replace("/[^a-zA-Z]+/", "", $rate[0]) . "bps";
            $unit_down = preg_replace("/[^a-zA-Z]+/", "", $rate[1]) . "bps";
            $rate_up = preg_replace("/[^0-9]+/", "", $rate[0]);
            $rate_down = preg_replace("/[^0-9]+/", "", $rate[1]);
            $bw_name = str_replace("/", "_", $rateLimit);
            $bw = ORM::for_table('tbl_bandwidth')->where('name_bw', $bw_name)->find_one();
            if (!$bw) {
                $results[] = "Bandwith Created: $bw_name";
                $d = ORM::for_table('tbl_bandwidth')->create();
                $d->name_bw = $bw_name;
                $d->rate_down = $rate_down;
                $d->rate_down_unit = $unit_down;
                $d->rate_up = $rate_up;
                $d->rate_up_unit = $unit_up;
                $d->save();
                $bw_id = $d->id();
            }else{
                $results[] = "Bandwith Exists: $bw_name";
                $bw_id = $bw->id;
            }

            // Create Packages
            $pack = ORM::for_table('tbl_plans')->where('name_plan', $name)->find_one();
            if(!$pack){
                $results[] = "Packages Created: $name";
                $d = ORM::for_table('tbl_plans')->create();
                $d->name_plan = $name;
                $d->id_bw = $bw_id;
                $d->price = '10000';
                $d->type = 'PPPOE';
                $d->typebp = 'Unlimited';
                $d->limit_type = 'Time_Limit';
                $d->time_limit = 0;
                $d->time_unit = 'Hrs';
                $d->data_limit = 0;
                $d->data_unit = 'MB';
                $d->validity = '30';
                $d->validity_unit = 'Days';
                $d->routers = $router;
                $d->enabled = 1;
                $d->save();
            }else{
                $results[] = "Packages Exists: $name";
            }
        }
    }
    // Import user
    $userRequest = new RouterOS\Request(
        '/ppp secret print'
    );
    $users = $client->sendSync($userRequest)->toArray();
    foreach ($users as $u) {
        $username = $u->getProperty('name');
        if(!empty($username) && !empty($u->getProperty('password'))){
            $d = ORM::for_table('tbl_customers')->where('username', $username)->find_one();
            if($d){
                $results[] = "Username Exists: $username";
            }else{
                $d = ORM::for_table('tbl_customers')->create();
                $d->username = $username;
                $d->password = $u->getProperty('password');
                $d->fullname = $username;
                $d->address = '';
                $d->email = '';
                $d->phonenumber = '';
                if ($d->save()) {
                    $results[] = "$username added successfully";
                }else{
                    $results[] = "$username Failed to be added";
                }
            }
        }
    }
    return $results;
}