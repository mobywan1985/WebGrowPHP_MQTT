<?php
/*include("../config.php");
//If autologin is not successful redirect to the login page
if(isset($_SESSION['username'])){
//Check post variables for and form submits and run functions
    if(isset($_POST['toggle_on'])){
       $sql = "UPDATE devices SET d_state=".$_POST['toggle_on']." WHERE devices.id=".$_POST['sel_id'];
       $result = mysqli_query($db,$sql);
       $state = 'off';
       if($_POST['toggle_on'] == 'true')
          {  
          $state = 'on';
          }
#       send_to_python_scheduler($_POST['sel_id'], $state,'none' ,$_POST['gpio']);

    }

    if(isset($_POST['run_run'])){
       if($_POST['run_run'] == 'true')
         toggle_run($db);
       else
         toggle_stop($db);  
    }
    
}

    function toggle_run($db) {
        $sql = "UPDATE devices SET d_run=true,d_state=false WHERE devices.id=".$_POST['sel_id'];
        $result = mysqli_query($db,$sql);
        print($sql);
#        send_to_python_scheduler($_POST['sel_id'], 'run', $_POST['trigger']);
        
    }  

    function toggle_stop($db) {
        $sql = "UPDATE devices SET d_run=false,d_state=false WHERE devices.id=".$_POST['sel_id'];
        $result = mysqli_query($db,$sql);
#        send_to_python_scheduler($_POST['sel_id'], 'stop', $_POST['trigger']);
    }




    function send_to_python_scheduler($id, $func){
#        $sockethelper = new sockethelper('localhost',5001);
        $myArray = array('device' => $id, 'function' => $func);
        $myArrayJsonEncoded = json_encode($myArray);
#        $sockethelper->send_data($myArrayJsonEncoded);
#        $sockethelper->close_socket();
        $mqtt = MQTTClient_Publisher($myArrayJsonEncoded);
    }

    function MQTTClient_Publisher($msg){
           $client = new Mosquitto\Client("PHP");
           $client->connect("localhost", 9001, 5);
           $client->loop();
           $mid = $client->publish('php_function', $msg, 2, 0);
           $client->loop();
           $client->disconnect();
           unset($client);
    }


    class SocketHelper{
       
       public $socket;
       public function __construct($host,$port){
           $this->socket = socket_create(AF_INET,SOCK_STREAM,SOL_TCP);
               $result = socket_connect($this->socket,$host,$port);
       }
       public function send_data($content){
           socket_write($this->socket,$content,strlen($content));
       }
       public function read_data(){
               while($out = socket_read($this->socket,1024)){
                   return $out;
           }
       }
       public function close_socket(){
               socket_close($this->socket);
       }
    }*/
?>