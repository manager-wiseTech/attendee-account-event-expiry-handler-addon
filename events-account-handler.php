<?php
/**
 * Plugin Name:       Events Attendee Account and Expiry Handler
 * Plugin URI:        https://www.finaldatasolutions.com
 * Description:       Handle the attendees account creation and event expiries and re-certification..
 * Version:           1.0.0
 * Author:            Ibrar Ayoub
 * Author URI:        https://finaldatasolutions.com/
 **/

require 'plugin-update-checker-master/plugin-update-checker.php';
$myUpdateChecker = Puc_v4_Factory::buildUpdateChecker(
	'https://github.com/manager-wiseTech/attendee-account-event-expiry-handler-addon/',
	__FILE__,
	'attendee-account-event-expiry-handler-addon'
);

//Set the branch that contains the stable release.
$myUpdateChecker->setBranch('main');

//Optional: If you're using a private repository, specify the access token like this:
$myUpdateChecker->setAuthentication('your-token-here');

add_action("admin_menu","event_account_handler_menu");
function event_account_handler_menu(){
	add_menu_page( "Set Expiry Date", "Set Event Expiry", 'manage_options', 'attendee-handler-plugin', 'event_account_handler_menu_cb_fn', 'dashicons-analytics', 10 );
	add_submenu_page('attendee-handler-plugin', "Set Expiry Date", "Set Event Expiry", 'manage_options', 'attendee-handler-plugin', 'event_account_handler_menu_cb_fn', 'dashicons-analytics');
	add_submenu_page('attendee-handler-plugin',"Event Expiry List","Event Expiry List",'manage_options','event-expiry-list','event_expiry_list_cb_fn');
}
function event_account_handler_menu_cb_fn(){
	
	 global $wpdb;
	 // $id = 9832;
	 // $table_name = $wpdb->prefix."events_category_rel";
	 // $results = $wpdb->get_results( "SELECT * FROM $table_name WHERE event_id = $id");
	 // foreach ($results as $event_cat) {
	 // 	echo $cat = $event_cat->cat_id;	
	 // }
	 // $table_name = $wpdb->prefix."events_detail";
	 
	 // $results = $wpdb->get_results( "SELECT * FROM $table_name WHERE registration_end > CURRENT_DATE");

	 // foreach ($results as $nearby_evetns) {
	 // 	echo $nearby_evetns->event_name."<br>";
	 // }

	$table_name = $wpdb->prefix."events_detail";
	$results = $wpdb->get_results( "SELECT * FROM $table_name ORDER BY id DESC LIMIT 100");

	?>
	<form method="POST" action="#">
		<table>
			<tr>
				<td><label>Select an Event</label></td>
				<td>
					<select name="event">
						<option value="">Select Event</option>
						<?php
						foreach ($results as $result) {
							echo '<option value ="'.$result->id.'">'.$result->event_name.' ('.$result->start_date.')</option>';
						}
						?>
					</select>
				</td>
			</tr>
			<tr>
				<td><label>Select Expiry Date</label></td>
				<td><input type="date" name="event_expiry_date"></td>
			</tr>
			<tr>
				<td><input class="button-primary" type="submit" name="setexpiry" value="Add Expiry Date"></td>
			</tr>
		</table>
	</form>

	<?php
	if (isset($_POST['setexpiry'])) {
		$event_id = $_POST['event'];
		$expiry_date = $_POST['event_expiry_date'];
		global $wpdb;
		$table_name = $wpdb->prefix.'events_expiry_records';
		$check_event_exist = $wpdb->get_var("SELECT COUNT(*) FROM $table_name  WHERE event_id = $event_id");
		if ($check_event_exist) {
			echo "<script>alert('Expiry Date of this event is already added.')</script>";
			exit();
		}
		$table_name = $wpdb->prefix."events_detail";
		$results = $wpdb->get_results( "SELECT * FROM $table_name where id = $event_id");
		foreach ($results as $result) {
			$event_date = $result->start_date;
		}
		$event_date = strtotime($event_date);
		$expiry = strtotime($expiry_date);
		if ($event_date > $expiry) {
			echo "<script>alert('Expiry date should be latest then Event date.')</script>";
			exit();
		}
		else{
			insert_expiry_date($event_id,$expiry_date);
		}
	}
}
function event_expiry_list_cb_fn(){
	$action = isset($_GET['action'])? trim($_GET['action']):"";
	if ($action == 'changeExpiry') {
		$event_id = isset($_GET['event_id'])?intval($_GET['event_id']):"";
			ob_start();
			include_once plugin_dir_path(__FILE__).'views/change-expiry-date.php';
			$template = ob_get_contents();
			ob_end_clean();
			echo $template;
	}
	else{

		ob_start();
		include_once plugin_dir_path(__FILE__).'views/event-expiry-list.php';
		$template = ob_get_contents();
		ob_end_clean();
		echo $template;
	}
	
}

function insert_expiry_date($event_id,$event_expiry_date){
	global $wpdb;
	$table_name = $wpdb->prefix."events_expiry_records";
	  
	  if ($wpdb->insert($table_name,array('event_id'=>$event_id,'event_expiry_date'=>$event_expiry_date),array('%d','%s'))) {

	  			echo "<script>alert('Expiry date Added successfully.')</script>";

	  }
}
add_shortcode('attendee-dashboard','generate_attende_account_html');
function generate_attende_account_html($atts){
	if (!is_user_logged_in()) {
	
		woocommerce_login_form();

	}
	else{

		$user = wp_get_current_user();
		$content = "";
		$content .= "<h3>Dashboard<h3>";
		$content .= "<h4>Purchased Courses<h4>";
		global $wpdb;
		$table_name = $wpdb->prefix."events_attendee";
		$results = $wpdb->get_results ( "SELECT * FROM $table_name WHERE email = '$user->user_email'");
		$value_number = 1;
		foreach ($results as $result) {
		 	$eve_detail_tb = $wpdb->prefix."events_detail";
		  	$events = $wpdb->get_results("SELECT * FROM $eve_detail_tb WHERE id = $result->event_id");
			  	foreach ($events as $event) {
			  		$content .= '<h3>'.$event->event_name.'</h3>';
			  		$content .= '<p>Certificate issue Date: '.$event->start_date.'<br>';
			  		$content .= 'Certificate Expiry Date: <span id="expiredate'.$value_number.'">'.get_event_expiry_date($event->id) .'</span></p>';
			  		$content .= '<p>Remaining Time: <span style="color:red" id="demo'.$value_number.'"></span></p>';
			  		//certify button code
			  		$expirydate = strtotime(get_event_expiry_date($event->id));
			  		$taodaydate = strtotime(date('Y-m-d'));
			  		// if ($taodaydate > $expirydate) {
			  		// 	$content .= '<a href="recertification-page//?ee='.$event->id.'" class="button">Re-Certify</a>';
			  		// }
			  		
			  		$value_number++;

		  	}//end of event detail loop

		}//end of attendee detail loop
		$content .= '
					<script type="text/javascript">
					  	var expirydate = new Array('. $value_number .');
					  	for (var i = 1; i < '. $value_number.'; i++) {
					  			expirydate[i] = document.getElementById("expiredate"+i).innerHTML;	
					  	}
					  	
					  	function countdown(expirydate,timer_demo){
					  		
					  		var deadline = new Date(expirydate).getTime(); 
							var x = setInterval(function() { 
							var now = new Date().getTime(); 
							var t = deadline - now; 
							var days = Math.floor(t / (1000 * 60 * 60 * 24)); 
							var hours = Math.floor((t%(1000 * 60 * 60 * 24))/(1000 * 60 * 60)); 
							var minutes = Math.floor((t % (1000 * 60 * 60)) / (1000 * 60)); 
							var seconds = Math.floor((t % (1000 * 60)) / 1000); 
							document.getElementById("demo"+timer_demo).innerHTML = days + "d "  
							+ hours + "h " + minutes + "m " + seconds + "s "; 
							    if (t < 0) { 
							        clearInterval(x); 
							        document.getElementById("demo"+timer_demo).innerHTML = "EXPIRED"; 
							    } 
							    if (expirydate == "")
							  		{
							  			document.getElementById("demo"+timer_demo).innerHTML = "Expiry date is not set for this event.";
							  		}
							}, 1000);

					  	}
					  	for (var i = 1; i <'. $value_number.'; i++){
					  		countdown(expirydate[i],i);
					  	}
					  </script>
		';

		return $content;
	
	}
}
function get_event_expiry_date($event_id){
		global $wpdb;
		$expiry_table_name = $wpdb->prefix.'events_expiry_records';
		$expiry_date = $wpdb->get_results("SELECT event_expiry_date FROM $expiry_table_name WHERE event_id = $event_id");
		return $expiry_date[0]->event_expiry_date;
}
add_shortcode('events-occuring','get_list_of_occuring_events');
function get_list_of_occuring_events($atts){

	return ;
}
register_activation_hook( __FILE__, 'attendee_handler_activation' );
function attendee_handler_activation(){
	global $wpdb;
    $table_name = $wpdb->prefix . "events_expiry_records";
    $sql = 'CREATE TABLE IF NOT EXISTS '.$table_name.' (
    `r_id` int(11) NOT NULL AUTO_INCREMENT,
    `event_id` int(11) NOT NULL,
    `event_expiry_date` VARCHAR(25) NOT NULL,
    PRIMARY KEY (`r_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1;';    
    require_once( ABSPATH . '/wp-admin/includes/upgrade.php' );
    dbDelta($sql);

    $table_name = $wpdb->prefix . "custom_user_tb";
    $sql = 'CREATE TABLE IF NOT EXISTS '.$table_name.' (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `user_email` varchar(25) NOT NULL,
    `event_id` int(11) NOT NULL,
    PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1;';    
    dbDelta($sql);

}
add_action('action_hook_espresso_update_attendee_payment_status','create_user_from_email');
function create_user_from_email(){
		 global $wpdb;
		 $table_name = $wpdb->prefix."events_attendee";
		 $results = $wpdb->get_results ( "SELECT * FROM $table_name  ORDER BY id DESC LIMIT 100");
            foreach ($results as $result) {

            $user_id = username_exists( $result->email );

            	if ( ! $user_id && false == email_exists( $result->email ) ) {
       				$password = wp_generate_password( 12, false );
                    $user_id = wp_create_user( $result->email, $password, $result->email );
                    $user = new WP_User( $user_id );
					$user->set_role( 'customer' );
                    $msg = "Hello ...Welcome at first aid training";
					$msg .= 'Your username is : '.$result->email.'  and your password is: '.$password;
				 	// Email the user
                    $headers = $_SERVER['SERVER_NAME'];
      				$headers .= 'From: <no-reply@'.$headers.'>' . "\r\n";
      				if (wp_mail( $result->email, 'Welcome!', $msg,$headers)) {
      					add_data_in_custom_tb($result->email,$result->event_id);
      				}
					//echo "Create account Enter in table";
                    
                }

                else
                {
                	$wpdb->get_results("SELECT * FROM $table_name WHERE email = '$result->email'");
                	
                	$flag = check_email_existence($result->email,$result->event_id);
               		
                	if(!$flag)
                	{
                		$user = get_user_by( 'email', $result->email );
                		$password = md5($user->user_pass);
						$msg = 'Hello..!! Welcome Back. You already have an account. Your username is : '.$result->email.' . Please login to your account. If you have forgotten your account login password then you can reset your account password using forget password link on the '.esc_url( wp_login_url( get_permalink() ) );
					 	// Email the user
	                    $headers = $_SERVER['SERVER_NAME'];
	      				$headers .= 'From: <no-reply@'.$headers.'>' . "\r\n";
	      			
	      				if (wp_mail( $result->email, 'Welcome!', $msg,$headers)) {
	      					add_data_in_custom_tb($result->email,$result->event_id);
	      				}
                		//echo "Already have an account...only email send.";
                	}
                }

           }

            

    		


}

function check_email_existence($email,$event_id){
	global $wpdb;
	$table_name = $wpdb->prefix."custom_user_tb";
	$wpdb->get_results("SELECT * FROM $table_name WHERE user_email = '$email' AND event_id = $event_id");
	if ($wpdb->num_rows) {
		return 1;
	}
	else{
		return 0;
	}
}
function add_data_in_custom_tb($email,$event_id){
	global $wpdb;
	$table_name = $wpdb->prefix.'custom_user_tb';
	$wpdb->insert($table_name, 
    array(
      'user_email'		=> $email,
      'event_id'		=> $event_id
    ),
    array(
      '%s',
      '%d'
    ) 
  );	
}
?>