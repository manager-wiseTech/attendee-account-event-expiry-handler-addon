<?php 
	//global $wpdb;
	// $table_name = $wpdb->prefix."events_expiry_records";
	// $id = $_GET['event_id'];
	// $event_data = $wpdb->get_results("SELECT * FROM $table_name WHERE event_id = $id");
	// foreach ($event_data as $e_data) {
	// 	$event_current_expiry_date = $e_data->event_expiry_date;
	// }
	?>
	<h1 align="center">Change Event Expiry</h1>
	<form method="post" action="">
		<table>
			<tr>
				<td>Event Name</td>
				<td><b><?php echo $_GET['event_name']; ?></b></td>
			</tr>
			<tr>
				<td>Current Expiry Date</td>
				<td><b><?php echo $_GET['expiry']; ?></b></td>
			</tr>
			<tr>
				<td>Select New Expiry Date</td>
				<td><input type="date" name="event_expiry_date"></td>
			</tr>
			<tr>
				<td><input class="button-primary" type="submit" name="updateExpiry" value="Change Expiry date"></td>
			</tr>
		</table>
	</form>

	<?php
	if (isset($_POST['updateExpiry'])) {
		$event_id = array('event_id' => $_GET['event_id']);
		$new_date = array('event_expiry_date' => $_POST['event_expiry_date']);
		global $wpdb;
		$table_name = $wpdb->prefix."events_expiry_records";

		$updated = $wpdb->update($table_name, $new_date, $event_id);
		if ( false === $updated ) {
         $update = 0;
	      } else {
	          $update = 1;
	      }

		echo "<script>window.open('admin.php?page=event-expiry-list&update=".$update."','_self')</script>";
		
	}

	
?>