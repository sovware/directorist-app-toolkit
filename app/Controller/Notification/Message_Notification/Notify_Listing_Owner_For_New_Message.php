<?php

namespace DirectoristAppToolkit\Controller\Notification\Message_Notification;


use DirectoristAppToolkit\Controller\Notification\Helper;

class Notify_Listing_Owner_For_New_Message {

    use Helper\Route_Helper;

    public function __construct() {
        add_action( 'directorist_email_on_send_contact_messaage_to_listing_owner', [ $this, 'send_notification' ] );
    }

    public function send_notification( $action_args = [] ) {
        $sender_name   = ( isset( $action_args['sender_name'] ) ) ? $action_args['sender_name'] : 'someone';
        $message       = ( isset( $_POST["message"] ) ) ? stripslashes(esc_textarea($_POST["message"])) : '';

        $email = ( isset( $action_args['to_email'] ) ) ? $action_args['to_email'] : '';
        $topic = $this->get_topic_id( $email );

        $listing_id         = ( isset( $action_args['listing_id'] ) ) ? $action_args['listing_id'] : 0;
        $user_avater        = 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&ixlib=rb-1.2.1&auto=format&fit=crop&w=687&q=80';
        $notification_title = "A message from {$sender_name} is received";
        
        $message_fields = [
            "fields" => [
                "title"      => [ "stringValue" => $notification_title ],
                "image_icon" => [ "stringValue" => $user_avater ],
                "data"       => [ "mapValue" => [ "fields" => [
                    'type'         => [ "stringValue" => 'messageNotificaton' ],
                    'action'       => [ "stringValue" => 'navigateToMessageScreen' ],
                    'from_user_id' => [ "stringValue" => "1" ],
                    'listing_id'   => [ "stringValue" => "{$listing_id}" ],
                    'message'      => [ "stringValue" => $message ],
                ]]],
                "topic"         => [ "stringValue" => $topic ],
                "to_email"      => [ "stringValue" => $email ],
                "send_to_admin" => [ "booleanValue" => true ],
                "date_created"  => [ "stringValue" => $this->get_current_time() ],
            ]
        ];

        $response = wp_remote_post( $this->get_route( $topic ), self::get_route_args( $message_fields ) );   
        
        file_put_contents( dirname( __FILE__ ) . '/log.json', json_encode( $response ) );
    }
}