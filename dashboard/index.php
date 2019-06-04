<?php

//Database initialization and autologin initialization
require_once '../config.php';

//Load twig templating system
require_once '/home/pi/vendor/autoload.php';
$loader = new Twig_Loader_Filesystem('templates');
$twig = new Twig_Environment($loader);
if (!isset($_GET['p']))
    $_GET['p'] = 'home';
$twig->addGlobal('_get', $_GET);

global $python_status;
$python_cmd = '';
//If autologin is not successful redirect to the login page
if(!isset($_SESSION['username']))
    {
    header("Location: ../index.php");
    exit;
    }
//Check post variables for and form submits and run functions
else
    {
    if(isset($_POST['save_device']))
       apply_setting($db);
    else if(isset($_POST['applySchedule']))
       applySchedule($db);
    else if(isset($_POST['create']))
       createDevice($db);
    else if(isset($_POST['delete_device']))
       deleteDevice($db);
    else if(isset($_POST['clr_log']))
       clearLog($db);
    else if(isset($_POST['save_settings']))
       save_settings($db);
    else if(isset($_POST['delete_user']))
       delete_user($db);
    else if(isset($_POST['reset_password']))
       reset_password($db);
    else if(isset($_POST['new_journal']))
       new_journal($db);
    else if(isset($_POST['create_user']))
       create_user($db);
    else if(isset($_POST['delete_logbook']))
       delete_logbook($db);
    else if(isset($_POST['restore_archive']))
       restore_archive($db);
    load_pages($db,$twig);
    }
//Load whichever page user has requested $_GET['p'] is page
function load_pages($db, $twig){
    global $python_cmd;
    //Load all MYSQL Devices
    $sql = "SELECT * from devices";
    $result = mysqli_query($db,$sql);
    $devices = mysqli_fetch_all($result,MYSQLI_ASSOC);
    
    //Load all MYSQL GPIO pins
    $sql = "SELECT gpio from gpio";
    $result = mysqli_query($db,$sql);
    $gpio_pins = mysqli_fetch_all($result,MYSQLI_ASSOC);
    
    //Load all MYSQL menu pages
    $sql = "SELECT * from menu ORDER BY m_order";
    $result = mysqli_query($db,$sql);
    $menu = mysqli_fetch_all($result,MYSQLI_ASSOC);
    
    //Load all MYSQL device protocols and sort the into protocol id array
    $sql = "SELECT * from device_protocol ORDER BY d_protocol";
    $result = mysqli_query($db,$sql);
    $protocols = mysqli_fetch_all($result,MYSQLI_ASSOC);
    $protocol_types = array();
    foreach($protocols as $p)
    {
    $protocol_types[$p['d_protocol']] = $p;
    }
    
    //Load all MYSQL triggers and sort them into trigger id array
    $sql = "SELECT * from triggers ORDER BY t_id";
    $result = mysqli_query($db,$sql);
    $triggers = mysqli_fetch_all($result,MYSQLI_ASSOC);
    $trigger_types = array();
    foreach($triggers as $t)
    {
    $trigger_types[$t['t_id']] = $t;
    }

    //Load the latest settings from MYSQL
    $sql = "SELECT * FROM settings WHERE id = (SELECT MAX(id) FROM settings)";
    $result = mysqli_query($db,$sql);
    $settings = mysqli_fetch_all($result,MYSQLI_ASSOC);
    if($settings[0]['e_cels'])
        $scaleString = 'C';
    else
        $scaleString = 'F';
    
    //Load all the MYSQL device types and sort them into a types array
    $sql = "SELECT * from device_types";
    $result = mysqli_query($db,$sql);
    $types = mysqli_fetch_all($result,MYSQLI_ASSOC);
    $device_types = array();
    foreach($types as $type)
    {
    $device_types[$type['d_type']] = $type;
    }
    
    //Get the current state of the Python Service
    //$py_status = get_python_status();
    $sql = "select temperature,humidity from sensor_log WHERE id = (SELECT MAX(id) FROM sensor_log)";
    $result = mysqli_query($db,$sql);
    $last_sensor = mysqli_fetch_all($result,MYSQLI_ASSOC);
    
    //Create a new Device
    
    if($_GET['p'] == 'newbook')
      {
      echo $twig->render('newbook.html', ['nav_menu' => $menu, 'scaleString' => $scaleString,'devices' => $devices, 'triggers' => $triggers, 'gpio_pins' => $gpio_pins, 'settings' => $settings[0], 'py_cmd'=> $python_cmd, 'temperature' => $last_sensor[0]['temperature'], 'humidity' => $last_sensor[0]['humidity']]);
      }
    
    //Scheduling a Device
    elseif($_GET['p'] == 'schedule')
      {
      $sql = "SELECT * from schedule WHERE schedule.d_id=".$_GET['d'];
      $result = mysqli_query($db,$sql);
      $deviceSchedule = mysqli_fetch_all($result,MYSQLI_ASSOC);
      echo $twig->render('schedule.html', [ 'nav_menu' => $menu, 'scaleString' => $scaleString,'devices' => $devices, 'triggers' => $triggers, 'gpio_pins' => $gpio_pins, 'ds_id' => $_GET['d'], 'deviceSchedule' => $deviceSchedule, 'settings' => $settings[0],'py_cmd'=> $python_cmd,'temperature' => $last_sensor[0]['temperature'], 'humidity' => $last_sensor[0]['humidity']]);
      }
    //Wesite Logout
    elseif($_GET['p'] == 'logout')
      {
      include_once '../logout.php';
      }
      
    //Webcam Page
    elseif($_GET['p'] == 'webcam')
      {
      echo $twig->render('webcam.html', ['nav_menu' => $menu, 'scaleString' => $scaleString,'settings' => $settings[0], 'webcam_ip' => $_SERVER['SERVER_ADDR'],'temperature' => $last_sensor[0]['temperature'], 'humidity' => $last_sensor[0]['humidity']]);
      }
      
    //Calendar Page for Journal
    elseif($_GET['p'] == 'calendar')
      {
      $sql = "SELECT * from journal_event WHERE journal_id=".$_GET['jid']." ORDER BY event_date DESC";
      $result = mysqli_query($db,$sql);
      $journal_events = mysqli_fetch_all($result,MYSQLI_ASSOC);
      
      $sql = "SELECT * from event_type";
      $result = mysqli_query($db,$sql);
      $etype = mysqli_fetch_all($result,MYSQLI_ASSOC);
      $managed_types = array();
      foreach ($etype as $type)
      {
        $managed_types[$type['type_id']]  = $type;
      }
      $journal_dates = array();
      $event_string = '';
      foreach ($journal_events as $event)
        {
         $date = new DateTime($event['event_date']);
         $date_array = $journal_dates[$date->format('Y-m-d')];
         $date_array[] = $event;
         if($event['event_type'] == 0)
            $event_string .= "{title  : '<div class=\"d-flex\"><button class=\"btn btn-primary btn-sm magnify\" style=\"border-radius:50%;\"><i class=\"p-0 fas fa-tint\"></i></button><div class=\"d-none d-sm-block align-middle pt-2 pl-1 font-weight-bold d-sm\">".$date->format('g:iA')."</div></div>', start  : '".$date->format('Y-m-d')."', description: '".$date->format('g:iA').' : '.$managed_types[$event['event_type']]['full_name']."'},";
         if($event['event_type'] == 5)
            $event_string .= "{title  : '<div class=\"d-flex\"><button class=\"btn btn-success btn-sm magnify\" style=\"border-radius:50%;\"><i class=\"p-0 fas fa-tint\"></i></button><div class=\"d-none d-sm-block align-middle pt-2 pl-1 font-weight-bold d-sm\">".$date->format('g:iA')."</div></div>', start  : '".$date->format('Y-m-d')."', description: '".$date->format('g:iA').' : '.$managed_types[$event['event_type']]['full_name']."'},";
         else if ($event['event_type'] == 10)
            $event_string .= "{title  : '<div class=\"d-flex\"><button class=\"btn btn-success btn-sm magnify\" style=\"border-radius:50%;\"><small class=\"p-0 fas fa-leaf\"></small></button><div class=\"d-none d-sm-block align-middle pt-2 pl-1 font-weight-bold d-sm\">".$date->format('g:iA')."</div></div>', start  : '".$date->format('Y-m-d')."', description: '".$date->format('g:iA').' : '.$managed_types[$event['event_type']]['full_name']."'},";
         else if ($event['event_type'] == 20)
            $event_string .= "{title  : '<div class=\"d-flex\"><button type=\"button\" class=\"magnify btn btn-warning btn-sm\" style=\"border-radius:50%;\"><i class=\"p-0 fas fa-sticky-note\"></i></button><div class=\"d-none d-sm-block align-middle pt-2 pl-1 font-weight-bold\">".$date->format('g:iA')."</div></div>', start  : '".$date->format('Y-m-d')."', description: '".$date->format('g:iA').' : '.addslashes($event['event_note'])."'},";
         $journal_dates[$date->format('Y-m-d')] = $date_array;
        }
      echo $twig->render('calendar.html', ['nav_menu' => $menu, 'scaleString' => $scaleString,'settings' => $settings[0], 'journal_dates' => $journal_dates, 'event_string' => $event_string, 'py_cmd'=> $python_cmd,'temperature' => $last_sensor[0]['temperature'], 'humidity' => $last_sensor[0]['humidity']]);
    }
    
    //Archive Page for Journal
    elseif($_GET['p'] == 'archive')
      {
         $sql = "SELECT * from journal WHERE archived=1 ORDER BY id DESC";
         $result = mysqli_query($db,$sql);
         $journals = mysqli_fetch_all($result,MYSQLI_ASSOC);
         
         if(!isset($_GET['jid']))
         {
         $_GET['jid'] = $journals[0]['id'];
         $twig->addGlobal('_get', $_GET);
         }
         
         $sql = "SELECT COUNT(id) FROM journal_event WHERE journal_id=".$_GET['jid'];
         $result = mysqli_query($db,$sql);
         $event_count = mysqli_fetch_all($result,MYSQLI_ASSOC)[0]['COUNT(id)'];

         $journal_id = array();
         foreach ($journals as $j)
           {
            $journal_id[$j['id']] = $j;
           }

         echo $twig->render('archive.html', ['nav_menu' => $menu, 'scaleString' => $scaleString,'settings' => $settings[0], 'journals' => $journal_id, 'event_count' => $event_count, 'py_cmd'=> $python_cmd,'temperature' => $last_sensor[0]['temperature'], 'humidity' => $last_sensor[0]['humidity']]);
      }
      
    //Journal Page
    elseif($_GET['p'] == 'journal')
      {
      if(isset($_POST['archive_logbook']))
         {
              if(!isset($_GET['jid']))
              {
              $sql = "SELECT * from journal WHERE archived=false";
              $result = mysqli_query($db,$sql);
              $journal = mysqli_fetch_all($result,MYSQLI_ASSOC);
              $id = $journal[0]['id'];
              }
              else
              {
              $id = $_GET['jid'];
              }

              $sql = "UPDATE journal SET archived=true WHERE id=".$id;
              $result = mysqli_query($db,$sql);
              unset($_GET['jid']);
         }
      
      $sql = "SELECT * from journal WHERE archived=false";
      $result = mysqli_query($db,$sql);
      $journal = mysqli_fetch_all($result,MYSQLI_ASSOC);
      $sql = "SELECT * from event_type";
      $result = mysqli_query($db,$sql);
      $etype = mysqli_fetch_all($result,MYSQLI_ASSOC);
      $managed_types = array();
      foreach ($etype as $type)
      {
        $managed_types[$type['type_id']]  = $type;
      }      
      if(!isset($_GET['jid']))
         {
         $_GET['jid'] = $journal[0]['id'];
         $twig->addGlobal('_get', $_GET);
         }

      if(isset($_POST['new_event_note']))
         {
              $sql = "INSERT INTO journal_event(journal_id, event_date, event_type, event_note, event_user) VALUES ('".$_GET['jid']."', '".date('Y-m-d H:i:s')."', '20', '".$_POST['event_note']."','".$_SESSION["username"]."')";
              $result = mysqli_query($db,$sql);
         }
      
      if(isset($_POST['event_plain']))
         {
              $sql = "INSERT INTO journal_event(journal_id, event_date, event_type, event_user) VALUES ('".$_GET['jid']."', '".date('Y-m-d H:i:s')."', '0','".$_SESSION["username"]."')";
              $result = mysqli_query($db,$sql);
         }
      if(isset($_POST['event_nutes']))
         {
              $sql = "INSERT INTO journal_event(journal_id, event_date, event_type, event_user) VALUES ('".$_GET['jid']."', '".date('Y-m-d H:i:s')."', '5','".$_SESSION["username"]."')";
              $result = mysqli_query($db,$sql);
         }
      if(isset($_POST['event_defol']))
         {
              $sql = "INSERT INTO journal_event(journal_id, event_date, event_type, event_user) VALUES ('".$_GET['jid']."', '".date('Y-m-d H:i:s')."', '10','".$_SESSION["username"]."')";
              $result = mysqli_query($db,$sql);
         }
         
         
      if($_POST['clr_date'] != '')
         {
              $date = \DateTime::createFromFormat('m-d-Y',  $_POST['clr_date'])->format("Y-m-d");
              $sql = "DELETE FROM journal_event WHERE event_date >= '".$date." 00:00:00' AND event_date <= '".$date." 23:59:59' AND journal_id=".$_GET['jid'];
              $result = mysqli_query($db,$sql);
         }

      
      $sql = "SELECT * from journal_event WHERE journal_id=".$_GET['jid']." ORDER BY event_date DESC";
      $result = mysqli_query($db,$sql);
      $journal_events = mysqli_fetch_all($result,MYSQLI_ASSOC);
      
      $journal_dates = array();
      foreach ($journal_events as $event)
        {
         $date = new DateTime($event['event_date']);
         $date_array = $journal_dates[$date->format('Y-m-d')];
         $date_array[] = $event;
         $journal_dates[$date->format('Y-m-d')] = $date_array;
        }
        
      $sql = "SELECT breeder,genetics,nutrients,method,media,lighting,note from journal WHERE id=".$_GET['jid'];
      $result = mysqli_query($db,$sql);
      $journal_desc = mysqli_fetch_all($result,MYSQLI_ASSOC);
      
      $sql = "select DATE_FORMAT(log_date, '%Y-%m-%d') as date, min(temperature) as min_temperature, max(temperature) as max_temperature from sensor_log group by DATE_FORMAT(log_date, '%d-%m-%Y') order by log_date";
      $result = mysqli_query($db,$sql);
      $max_temps = mysqli_fetch_all($result,MYSQLI_ASSOC);
      $sql = "select DATE_FORMAT(log_date, '%Y-%m-%d') as date, min(humidity) as min_humidity, max(humidity) as max_humidity from sensor_log group by DATE_FORMAT(log_date, '%d-%m-%Y') order by log_date";
      $result = mysqli_query($db,$sql);
      $max_humiditys = mysqli_fetch_all($result,MYSQLI_ASSOC);
      
      
      $mt = array();
      foreach ($max_temps as $temp)
        {
         $date = new DateTime($temp['date']);
         $mt[$date->format('Y-m-d')] = $temp;
        }
       $max_temps = $mt;
       
      $mh = array();
      foreach ($max_humiditys as $hum)
        {
         $date = new DateTime($hum['date']);
         $mh[$date->format('Y-m-d')] = $hum;
        }
       $max_humiditys = $mh;

            
      echo $twig->render('journal.html', ['nav_menu' => $menu, 'scaleString' => $scaleString,'todays_date' => date('Y-m-d'), 'settings' => $settings[0], 'journal' => $journal, 'etype' => $managed_types, 'journal_events' => $journal_dates, 'journal_desc' => $journal_desc[0], 'py_cmd'=> $python_cmd,'temperature' => $last_sensor[0]['temperature'], 'humidity' => $last_sensor[0]['humidity'], 'max_temps' => $max_temps, 'max_humiditys' => $max_humiditys]);
      }


    //Website Settings Page
    elseif($_GET['p'] == 'settings')
      {
      echo $twig->render('settings.html', ['nav_menu' => $menu, 'scaleString' => $scaleString,'gpio_pins' => $gpio_pins, 'settings' => $settings[0], 'py_cmd'=> $python_cmd,'temperature' => $last_sensor[0]['temperature'], 'humidity' => $last_sensor[0]['humidity']]);
      }
      
    //Website History Page
    elseif($_GET['p'] == 'log')
      {
      if(!isset($_GET['device']))
        {
        $_GET['device'] = $devices[0]['id'];
        $twig->addGlobal('_get', $_GET);
        }
      $sql = "SELECT * from log WHERE log.d_id=".$_GET['device']." ORDER BY runtime DESC ";
      $result = mysqli_query($db,$sql);
      $deviceLog = mysqli_fetch_all($result,MYSQLI_ASSOC);
      $sql = "SELECT d_name from devices WHERE id=".$_GET['device'];
      $result = mysqli_query($db,$sql);
      $deviceName = mysqli_fetch_all($result,MYSQLI_ASSOC);
      echo $twig->render('log.html', ['nav_menu' => $menu, 'scaleString' => $scaleString,'deviceLog' => $deviceLog, 'settings' => $settings[0], 'devices' => $devices, 'deviceName' => $deviceName[0]['d_name'], 'protocols' => $protocol_types, 'py_cmd'=> $python_cmd,'temperature' => $last_sensor[0]['temperature'], 'humidity' => $last_sensor[0]['humidity']]);
      }
      
    //Website Users Page
    elseif($_GET['p'] == 'users')
      {
      $sql = "SELECT * from user";
      $result = mysqli_query($db,$sql);
      $users = mysqli_fetch_all($result,MYSQLI_ASSOC);
      echo $twig->render('users.html', ['nav_menu' => $menu, 'scaleString' => $scaleString,'settings' => $settings[0], 'python_status' => $py_status, 'users' => $users, 'py_cmd'=> $python_cmd,'temperature' => $last_sensor[0]['temperature'], 'humidity' => $last_sensor[0]['humidity']]);
      }

    //Dashboard Home or Index page
    elseif($_GET['p'] == 'home' or !isset($_GET['p']) or $_GET['p'] == '')
      {
      $_GET['p'] = 'home';
        if(isset($_GET['chart']))
          {
          if($_GET['chart'] == 'd')
            {
            $sql = "SELECT * FROM (SELECT @row := @row +1 AS rownum, log_date, temperature, humidity  FROM (SELECT @row :=0) r, sensor_log) ranked WHERE rownum % 3 = 1 AND log_date >= NOW() - INTERVAL 1 DAY";
            }
          else if($_GET['chart'] == 'w')
            {
            $sql = "SELECT * FROM (SELECT @row := @row +1 AS rownum, log_date, temperature, humidity  FROM (SELECT @row :=0) r, sensor_log) ranked WHERE rownum % 5 = 1 AND log_date >= DATE(NOW()) - INTERVAL 7 DAY";
            }
          else if($_GET['chart'] == 'm')
            {
            $sql = "SELECT * FROM (SELECT @row := @row +1 AS rownum, log_date, temperature, humidity  FROM (SELECT @row :=0) r, sensor_log) ranked WHERE rownum % 6 = 1 AND log_date >= DATE(NOW()) - INTERVAL 30 DAY";
            }

          }
         else
            $sql = "SELECT * FROM (SELECT @row := @row +1 AS rownum, log_date, temperature, humidity  FROM (SELECT @row :=0) r, sensor_log) ranked WHERE rownum % 3 = 1 AND log_date >= NOW() - INTERVAL 1 DAY";
        $result = mysqli_query($db,$sql);
        $sensor_log = mysqli_fetch_all($result,MYSQLI_ASSOC);
        $log_dates = array();
        $avg_hmd = 0;
        $avg_tmp = 0;
        $tmp_data_string = '';
        $hmd_data_string = '';
        $x_axis_label    = '';
        foreach ($sensor_log as $log)
        {
         $date = new DateTime($log['log_date']);
         $date_array = $log_dates[$date->format('H')];
         $date_array[] = $log;
         $log_dates[$date->format('Y-m-d')][$date->format('G')] = $date_array;
        }
        foreach($log_dates as $date_time => $log_date)
         {
         foreach ($log_date as $time => $logs )
           {
           $size = sizeof($log_date[$time]);
           foreach($logs as $log)
           {
            $avg_hmd = $avg_hmd + $log['humidity'];
            $avg_tmp = $avg_tmp + $log['temperature'];
           }
           $comma = ',';
           $x_first_time = '';
           if ($tmp_data_string == '')
               {
               $comma = '';
               $x_first_time = $date_time;
               }

           if($time >= 12)
              {
              if($time > 12)
                 $time_str = ($time-12).'PM';
              else
                 $time_str = ($time).'PM';
              }
           else
              {
                 if($time == 0)
                    $time_str = '12AM';
                 else
                    $time_str = $time.'AM';
              }
              
           if($_GET['chart'] == 'w' or $_GET['chart'] == 'm')
                 {
                 #if($time == 12 or $time == 0)
                    $time_str = $date_time.' '.$time_str;
                 }
           $tmp_data_string = $tmp_data_string.$comma.'"'.round($avg_tmp/$size).'"';
           $hmd_data_string = $hmd_data_string.$comma.'"'.round($avg_hmd/$size).'"';
           $x_axis_label = $x_axis_label.$comma.'"'.$time_str.'"';
           $avg_hmd = 0;
           $avg_tmp = 0;
           }
          }
        
      echo $twig->render('index.html', ['nav_menu' => $menu, 'scaleString' => $scaleString,'devices' => $devices, 'triggers' => $triggers, 'gpio_pins' => $gpio_pins, 'settings' => $settings[0], 'python_status' => $py_status, 'temperature_string' => $tmp_data_string, 'humidity_string' => $hmd_data_string, 'x_axis_string' => $x_axis_label, 'py_cmd'=> $python_cmd,'temperature' => $last_sensor[0]['temperature'], 'humidity' => $last_sensor[0]['humidity']]);
      }
      
    //Website Devices Page
    elseif($_GET['p'] == 'devices')
      {
      echo $twig->render('devices.html', ['nav_menu' => $menu, 'scaleString' => $scaleString,'devices' => $devices, 'device_types' => $device_types, 'triggers' => $triggers, 'trigger_types' => $trigger_types, 'gpio_pins' => $gpio_pins, 'settings' => $settings[0], 'python_status' => $py_status, 'protocols' => $protocol_types, 'py_cmd'=> $python_cmd,'temperature' => $last_sensor[0]['temperature'], 'humidity' => $last_sensor[0]['humidity']]);
      }
      
    elseif($_GET['p'] == 'chart')
      {
      $comma = ',';
      if($_GET['type'] == 'avg' or $_GET['type'] == '')      
      {
           if(isset($_GET['chart']))
               {
               if($_GET['chart'] == 'd')
                 {
                 $sql = "SELECT * FROM (SELECT @row := @row +1 AS rownum, log_date, temperature, humidity  FROM (SELECT @row :=0) r, sensor_log) ranked WHERE rownum % 3 = 1 AND log_date >= NOW() - INTERVAL 1 DAY";
                 }
               else if($_GET['chart'] == 'w')
                 {
                 $sql = "SELECT * FROM (SELECT @row := @row +1 AS rownum, log_date, temperature, humidity  FROM (SELECT @row :=0) r, sensor_log) ranked WHERE rownum % 5 = 1 AND log_date >= DATE(NOW()) - INTERVAL 7 DAY";
                 }
               else if($_GET['chart'] == 'm')
                 {
                 $sql = "SELECT * FROM (SELECT @row := @row +1 AS rownum, log_date, temperature, humidity  FROM (SELECT @row :=0) r, sensor_log) ranked WHERE rownum % 6 = 1 AND log_date >= DATE(NOW()) - INTERVAL 30 DAY";
                 }

               }
              else
                 $sql = "SELECT * FROM (SELECT @row := @row +1 AS rownum, log_date, temperature, humidity  FROM (SELECT @row :=0) r, sensor_log) ranked WHERE rownum % 3 = 1 AND log_date >= NOW() - INTERVAL 1 DAY";
             $result = mysqli_query($db,$sql);
             $sensor_log = mysqli_fetch_all($result,MYSQLI_ASSOC);
             $log_dates = array();
             $avg_hmd = 0;
             $avg_tmp = 0;
             $tmp_data_string = '';
             $hmd_data_string = '';
             $x_axis_label    = '';
             foreach ($sensor_log as $log)
             {
              $date = new DateTime($log['log_date']);
              $date_array = $log_dates[$date->format('H')];
              $date_array[] = $log;
              $log_dates[$date->format('Y-m-d')][$date->format('G')] = $date_array;
             }
             foreach($log_dates as $date_time => $log_date)
              {
              foreach ($log_date as $time => $logs )
                {
                $size = sizeof($log_date[$time]);
                foreach($logs as $log)
                {
                 $avg_hmd = $avg_hmd + $log['humidity'];
                 $avg_tmp = $avg_tmp + $log['temperature'];
                }
                $x_first_time = '';
                $comma = ',';
                if ($tmp_data_string == '')
                    {
                    $comma = '';
                    $x_first_time = $date_time;
                    }

                if($time >= 12)
                   {
                   if($time > 12)
                      $time_str = ($time-12).'PM';
                   else
                      $time_str = ($time).'PM';
                   }
                else
                   {
                      if($time == 0)
                         $time_str = '12AM';
                      else
                         $time_str = $time.'AM';
                   }
                   
                if($_GET['chart'] == 'w' or $_GET['chart'] == 'm')
                      {
                      #if($time == 12 or $time == 0)
                         $time_str = $date_time.' '.$time_str;
                      }
                $tmp_data_string = $tmp_data_string.$comma.'"'.round($avg_tmp/$size).'"';
                $hmd_data_string = $hmd_data_string.$comma.'"'.round($avg_hmd/$size).'"';
                $x_axis_label = $x_axis_label.$comma.'"'.$time_str.'"';
                $avg_hmd = 0;
                $avg_tmp = 0;
                }
               }
           
           
           
              echo $twig->render('charts.html', ['nav_menu' => $menu, 'scaleString' => $scaleString,'devices' => $devices, 'settings' => $settings[0],'temperature_string' => $tmp_data_string, 'humidity_string' => $hmd_data_string, 'x_axis_string' => $x_axis_label,'python_status' => $py_status, 'temperature' => $last_sensor[0]['temperature'], 'humidity' => $last_sensor[0]['humidity']]);        
      }
      elseif($_GET['type'] == 'hilo')
      {
      
           if($_GET['chart'] == 'w' or !isset($_GET['chart']))
              $interval = '7';
           if($_GET['chart'] == 'm')
              $interval = '30';
           if($_GET['chart'] == 'y')
              $interval = '365';

           $sql = "select DATE_FORMAT(log_date, '%Y-%m-%d') as date, min(temperature) as min_temperature, max(temperature) as max_temperature from sensor_log WHERE log_date >= DATE(NOW()) - INTERVAL ".$interval." DAY group by DATE_FORMAT(log_date, '%d-%m-%Y') order by log_date";
           $result = mysqli_query($db,$sql);
           $max_temps = mysqli_fetch_all($result,MYSQLI_ASSOC);
           $sql = "select DATE_FORMAT(log_date, '%Y-%m-%d') as date, min(humidity) as min_humidity, max(humidity) as max_humidity from sensor_log WHERE log_date >= DATE(NOW()) - INTERVAL ".$interval." DAY group by DATE_FORMAT(log_date, '%d-%m-%Y') order by log_date";
           $result = mysqli_query($db,$sql);
           $max_humiditys = mysqli_fetch_all($result,MYSQLI_ASSOC);
           
           
           $hitemp_string = '';
           $lotemp_string = '';
           $hilo_x_string = '';      
           foreach ($max_temps as $temp)
             {
              $date = new DateTime($temp['date']);
                  if ($hitemp_string == '')
                    $comma = '';
                  else
                    $comma = ',';

              $hitemp_string = $hitemp_string.$comma.'"'.round($temp['max_temperature']).'"';
              $lotemp_string = $lotemp_string.$comma.'"'.round($temp['min_temperature']).'"';
              $hilo_x_string = $hilo_x_string.$comma.'"'.$date->format('Y-m-d').'"';
             }
            
           $hihumid_string = '';
           $lohumid_string = '';

           foreach ($max_humiditys as $hum)
             {
              $date = new DateTime($hum['date']);
                  if ($hihumid_string == '')
                    $comma = '';
                  else
                    $comma = ',';

              $hihumid_string = $hihumid_string.$comma.'"'.round($hum['max_humidity']).'"';
              $lohumid_string = $lohumid_string.$comma.'"'.round($hum['min_humidity']).'"';
             }

           echo $twig->render('charts.html', ['nav_menu' => $menu, 'scaleString' => $scaleString,'temperature' => $last_sensor[0]['temperature'], 'humidity' => $last_sensor[0]['humidity'], 'hitemp_string' => $hitemp_string, 'lotemp_string' => $lotemp_string,'hihumid_string' => $hihumid_string, 'lohumid_string' => $lohumid_string, 'hilo_x_string' => $hilo_x_string]);
      }
      else
        {
           echo $twig->render('charts.html', ['nav_menu' => $menu, 'scaleString' => $scaleString,'temperature' => $last_sensor[0]['temperature'], 'humidity' => $last_sensor[0]['humidity']]);        
        }
      }
      
    exit; 
}


function delete_logbook($db){
    $sql = "DELETE FROM journal WHERE id=".$_POST['logbook_id'];
    $result = mysqli_query($db,$sql);
    $sql = "DELETE FROM journal_event WHERE journal_id=".$_POST['logbook_id'];
    $result = mysqli_query($db,$sql);
}

function restore_archive($db){
    $sql = "UPDATE journal SET archived=0 WHERE id=".$_POST['logbook_id'];
    $result = mysqli_query($db,$sql);
}

function create_user($db){
    $fname = mysqli_real_escape_string($db, $_POST['create_fname']);
    $lname = mysqli_real_escape_string($db, $_POST['create_lname']);
    $uname = mysqli_real_escape_string($db, $_POST['create_username']);
    $pw_hash =  password_hash($_POST['create_password'], PASSWORD_DEFAULT);
    $sql = "INSERT INTO user(fname, lname, username, password) VALUES('".$fname."','".$lname."','".$uname."','".$pw_hash."')";
    $result = mysqli_query($db,$sql);
}

function apply_setting($db) {
    if($_POST['edit_id'] == '')
    {
    createDevice($db);
    }
    else  
    {
    $temp = $_POST['sel_temp'];
    $intv = $_POST['sel_interval'];
    $dur = $_POST['sel_duration'];
    $hmd = $_POST['sel_humid'];
    $timer = $_POST['sel_timer'];
    if($temp == "")
       $temp = 0;
    if($intv == "")
       $intv = 0;
    if($dur == "")
       $dur = 0;
    if($hmd == "")
       $hmd = 0;
    if($timer == '')
       $timer = 0;
    if(isset($_POST['sel_change']))
    {
      $tchange = $_POST['sel_change'];
          if($tchange == '')
             $tchange = 'NULL';
    }else
     $tchange = 'NULL';
    if(isset($_POST['sel_hchange']))
    {
      $hchange = $_POST['sel_hchange'];
          if($hchange == '')
             $hchange = 'NULL';

    }else
       $hchange = 'NULL';
 
    $gpio_pin = $_POST['sel_gpio_pin'];
    if($_POST['sel_gpio_pin'] == '')
       $gpio_pin = 0;
    $sql = "UPDATE devices SET d_name='".$_POST['sel_name']."',d_gpio=".$gpio_pin.",d_trigger=".$_POST['sel_trigger'].",d_tchange=".$tchange.",d_hchange=".$hchange.", d_temp=".$temp.",d_humid=".$hmd.",d_interval=".$intv.",d_duration=".$dur.",d_run=false,d_state=false,d_type=".$_POST['sel_type'].",d_protocol=".$_POST['sel_protocol'].",d_ip='".$_POST['sel_ip']."',d_timer=".$timer." WHERE devices.id=".$_POST['edit_id'];
    $result = mysqli_query($db,$sql);
    send_to_python_scheduler($_POST['edit_id'], 'apply');    
    }
}
 
function applySchedule($db) {
    $sql = "DELETE FROM schedule WHERE schedule.d_id=".$_POST['deviceScheduleID'];
    $result = mysqli_query($db,$sql);
    foreach ($_POST as $key => $value){
       if (strpos($key, 'seqAdd') === 0) {
          $time = $value;
          $chunks = explode(':', $time);
          $chunks[0] = trim($chunks[0], ' ');
          $chunks[1] = trim($chunks[1], ' '); 
          if (strpos( $time, 'AM') === false && $chunks[0] !== '12') {
            $chunks[0] = $chunks[0] + 12;
          } else if (strpos( $time, 'PM') === false && $chunks[0] == '12') {
            $chunks[0] = '00';
          }
          $sw = 0;
          if(isset($_POST[str_replace('seqAdd', 'sw', $key)]))
             $sw = 1;
          $chunks[0] = trim($chunks[0]);
          $chunks[1] = trim($chunks[1]);
          $chunks[2] = trim($chunks[2]);
          if(isset($_POST[str_replace('seqAdd', 'seqWhen', $key)]))
             $day_of_wk = $_POST[str_replace('seqAdd', 'seqWhen', $key)];
          $sql = "INSERT INTO schedule(runtime, d_state, d_id, d_dayofwk) VALUES ('".strftime('%Y-%m-%d %H:%M:%S',strtotime(preg_replace('/\s[A-Z]+/s', '', implode(':', $chunks))))."','".$sw."',".$_POST['deviceScheduleID'].",'".$day_of_wk."')";
          $result = mysqli_query($db,$sql);
          $sql = "UPDATE devices SET d_run=false,d_state=false WHERE devices.id=".$_POST['deviceScheduleID'];
          $result = mysqli_query($db,$sql);

       }
    }
    send_to_python_scheduler($_POST['deviceScheduleID'], 'apply_schedule');
}


function createDevice($db) {
    
    $temp = $_POST['sel_temp'];
    $intv = $_POST['sel_interval'];
    $dur = $_POST['sel_duration'];
    $hmd = $_POST['sel_humid'];
    $tmr = $_POST['sel_timer'];
    $gpio_pin = $_POST['sel_gpio_pin'];
    if($temp == "")
       $temp = 0;
    if($intv == "")
       $intv = 0;
    if($dur == "")
       $dur = 0;
    if($hmd == "")
       $hmd = 0;
    if($tmr == "")
       $tmr = 0;
    if($_POST['sel_gpio_pin'] == "")
       $gpio_pin = 0;
    $tchange = $_POST['sel_change'];
    if($tchange == '')
       $tchange = 'NULL';
    $hchange = $_POST['sel_hchange'];
    if($hchange == '')
       $hchange = 'NULL';

    $sql = "INSERT INTO devices(d_name, d_gpio, d_trigger, d_tchange, d_hchange, d_temp, d_humid, d_interval, d_duration, d_run, d_state, d_type, d_ip, d_protocol, d_timer) VALUES('".$_POST['sel_name']."',".$gpio_pin.",".$_POST['sel_trigger'].",".$tchange.", ".$hchange.",".$temp.",".$hmd.",".$intv.",".$dur.",false,false, ".$_POST['sel_type'].",'".$_POST['sel_ip']."',".$_POST['sel_protocol'].", ".$tmr.")";
    $result = mysqli_query($db,$sql);    
    send_to_python_scheduler('none', 'create');
}

function new_journal($db) {
    $date = date('Y-m-d H:i:s');
    $sql = "INSERT INTO journal(name,breeder,genetics,nutrients,method,media,lighting,start_date,note,archived) VALUES('".$_POST['book_name']."','".$_POST['book_breed']."','".$_POST['book_gene']."','".$_POST['book_nutes']."','".$_POST['book_method']."','".$_POST['book_media']."','".$_POST['book_light']."','".$date."','".$_POST['book_note']."',false)";
    $result = mysqli_query($db,$sql);
}


function delete_user($db) {
    $sql = "DELETE FROM user WHERE id=".$_POST['del_uid'];
    $result = mysqli_query($db,$sql);
}

function reset_password($db) {
    $pw_hash =  password_hash($_POST['pw_reset'], PASSWORD_DEFAULT);
    $sql = "UPDATE user set password='".$pw_hash."' WHERE id=".$_POST['pw_id_reset'];
    $result = mysqli_query($db,$sql);
}



function deleteDevice($db) {
    $sql = "DELETE FROM devices WHERE id=".$_POST['del_id'];
    $result = mysqli_query($db,$sql);
    $sql = "DELETE FROM schedule WHERE schedule.d_id=".$_POST['del_id'];
    $result = mysqli_query($db,$sql);

    send_to_python_scheduler($_POST['del_id'], 'delete');

}

function save_settings($db) {
    $wbcam = 0;
    $tmp   = 0;
    $hmd   = 0;
    $cels  = 0;
    $alrt  = 0;     
    if(isset($_POST['enable_webcam']))
       $wbcam = 1;
    if(isset($_POST['enable_temp']))
       $tmp   = 1;
    if(isset($_POST['enable_alerts']))
       $alrt   = 1;
    if(isset($_POST['enable_cels']))
       $cels  = 1;
    $sql = "INSERT INTO settings(d_name,e_webcam, e_sensor, s_samp, s_tadj, s_hadj, s_gpio, s_alert, s_alert_email, s_send_email, s_send_pw, e_cels) VALUES ('".$_POST['name_topic']."','".$wbcam."','".$tmp."','".$_POST['temp_sample']."', '".$_POST['temp_adj']."'  , '".$_POST['hum_adj']."'  , '".$_POST['temp_gpio']."', '".$alrt."', '".$_POST['alert_email']."','".$_POST['sender_email']."','".$_POST['sender_pw']."', '".$cels."')";
    $result = mysqli_query($db,$sql);
    $sql = "UPDATE menu SET m_enabled='.$wbcam.' WHERE m_url='webcam'";
    $result = mysqli_query($db,$sql);
    $sql = "UPDATE menu SET m_enabled='.$tmp.' WHERE m_url='chart'"; 
    $result = mysqli_query($db,$sql);    
    if($tmp)
       {
       $sql = "UPDATE triggers SET t_enabled=true WHERE t_id=20";
       $result = mysqli_query($db,$sql);
       $sql = "UPDATE triggers SET t_enabled=true WHERE t_id=25";
       $result = mysqli_query($db,$sql);
       }
    else
       {
       $sql = "UPDATE triggers SET t_enabled=false WHERE t_id=20";
       $result = mysqli_query($db,$sql);
       $sql = "UPDATE triggers SET t_enabled=false WHERE t_id=25";
       $result = mysqli_query($db,$sql);
       }
    send_to_python_scheduler('none', 'reload_settings');

}
function clearLog($db){
    if ($_POST['clr_log'] != '')
    {
    $sql = "DELETE FROM log WHERE d_id=".$_POST['clr_log'];
    $result = mysqli_query($db,$sql);
    }
}

function send_to_python_scheduler($id, $func){
     global $python_cmd;
     $python_cmd .= 'sendMessage("'.$id.'", "'.$func.'");';
}

?>