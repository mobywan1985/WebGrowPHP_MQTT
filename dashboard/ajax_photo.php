<?php
require_once '../config.php';

$target_dir = "uploads/";
$target_date = date("Y_m_d_H_i_s");
$target_file = $target_dir . $target_date.'_'.basename($_FILES["file"]["name"]);

if (move_uploaded_file($_FILES["file"]["tmp_name"], $target_file)) {
   $status = 1;
   $sql = "INSERT INTO journal_event(journal_id, event_date, event_type, event_user, event_image) VALUES ('".$_POST['jid']."', '".date('Y-m-d H:i:s')."', '25', '".$_SESSION["username"]."', '".$target_file."')";
   $result = mysqli_query($db,$sql);
}

    /**
     *	Response 
     *	return json response to the dropzone
     *	@var data array
     */
$data = [
      "postdata" => $_POST['jid'],
      "file" => $target_date.'_'.basename($_FILES["file"]["name"]),
      "dropzone" => $_POST["dropzone"]
      ];
    header('Content-type: application/json');
    echo json_encode($data);
    exit();
?>