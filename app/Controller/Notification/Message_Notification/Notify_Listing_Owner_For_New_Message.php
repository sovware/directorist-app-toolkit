<?php

namespace DirectoristAppToolkit\Controller\Notification\Message_Notification;

use DirectoristAppToolkit\Controller\Notification\Helper;


class Notify_Listing_Owner_For_New_Message {

    use Helper\Route_Helper;

    public function __construct() {
        add_action( 'directorist_email_on_send_contact_messaage_to_listing_owner', [ $this, 'send_notification' ] );
    }

    public function send_notification( $action_args = [] ) {
        $sender_name = ( isset( $action_args['sender_name'] ) ) ? $action_args['sender_name'] : 'someone';
        $message = ( isset( $_POST["message"] ) ) ? stripslashes(esc_textarea($_POST["message"])) : '';

        $notification_title = "A message from $sender_name is received";
        $notification_body = $message;

        $message_title = "A message from $sender_name is received";
        $message_body = $message;

        $fields = [
            "fields" => [
                "title" => [ "stringValue" => $notification_title ],
                "body"  => [ "stringValue" => $notification_body ],
                "data"  => [ "mapValue" => [ "fields" => [
                    "title" => [ "stringValue" => $message_title ],
                    "body"  => [ "stringValue" => $message_body ],
                ]]],
                // "topic"        => [ "stringValue" => "admin" ],
                "topic"        => [ "stringValue" => "test" ],
                "date_created" => [ "stringValue" => current_time( 'mysql' ) ],
            ]
        ];

        wp_remote_post( $this->notification_route, self::get_route_args( $fields ) );
    }
}