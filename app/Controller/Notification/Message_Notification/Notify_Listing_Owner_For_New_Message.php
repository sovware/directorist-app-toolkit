<?php

namespace DirectoristAppToolkit\Controller\Notification\Message_Notification;


use DirectoristAppToolkit\Controller\Notification\Helper;

class Notify_Listing_Owner_For_New_Message {

    use Helper\Route_Helper;

    public function __construct() {

        add_action( '__init', function() {

            $email       = 'dev-email@flywheel.local';
            $user        = ( ! empty( $email ) ) ? get_user_by( 'email', $email ) : wp_get_current_user();
            $user_id     = ( ! empty( $user ) && ! empty( $user->ID ) ) ? $user->ID : 0;
            $user_pic_id = ( ! empty( $user_id ) ) ? get_user_meta( $user_id, 'pro_pic', true ) : '';
            $user_pic    = ( ! empty( $user_pic_id ) ) ? wp_get_attachment_url( $user_pic_id ) : '';

            var_dump( $user_pic );

        });

        add_action( 'directorist_email_on_send_contact_messaage_to_listing_owner', [ $this, 'send_notification' ] );
    }

    public function send_notification( $action_args = [] ) {
        $sender_name   = ( isset( $action_args['sender_name'] ) ) ? $action_args['sender_name'] : 'someone';
        $message       = ( isset( $_POST["message"] ) ) ? stripslashes(esc_textarea($_POST["message"])) : '';

        $email = ( isset( $action_args['to_email'] ) ) ? $action_args['to_email'] : '';
        $topic = $this->get_topic_id( $email );

        $listing_id = ( isset( $action_args['listing_id'] ) ) ? $action_args['listing_id'] : 0;
        
        $sender        = ( ! empty( $action_args['from_email'] ) ) ? get_user_by( 'email', $action_args['from_email'] ) : wp_get_current_user();
        $sender_id     = ( ! empty( $sender ) && ! empty( $sender->ID ) ) ? $sender->ID : 0;
        $sender_pic_id = ( ! empty( $sender_id ) ) ? get_user_meta( $sender_id, 'pro_pic', true ) : '';
        $sender_avater = ( ! empty( $sender_pic_id ) ) ? wp_get_attachment_url( $sender_pic_id ) : '';
        
        $notification_title = "A message from {$sender_name} is received";
        
        $message_fields = [
            "fields" => [
                "title"      => [ "stringValue" => $notification_title ],
                "data"       => [ "mapValue" => [ "fields" => [
                    'type'         => [ "stringValue" => 'messageNotificaton' ],
                    'action'       => [ "stringValue" => 'navigateToMessageScreen' ],
                    'from_user_id' => [ "stringValue" => "1" ],
                    'listing_id'   => [ "stringValue" => "{$listing_id}" ],
                    'message'      => [ "stringValue" => $message ],
                ]]],
                "topic"         => [ "stringValue" => $topic ],
                "seen"          => [ "booleanValue" => false ],
                "to_email"      => [ "stringValue" => $email ],
                "send_to_admin" => [ "booleanValue" => true ],
                "date_created"  => [ "stringValue" => $this->get_current_time() ],
            ]
        ];

        // Sender Avater
        if ( ! empty( $sender_avater ) ) {
            $message_fields['fields']['image_icon'] = [ "stringValue" => $sender_avater ];
        } else {
            $message_fields['fields']['fontIcon'] = [ "stringValue" => 'fas fa-user' ];
        }

        wp_remote_post( $this->get_route( $topic ), self::get_route_args( $message_fields ) );
        
        // $response = wp_remote_post( $this->get_route( $topic ), self::get_route_args( $message_fields ) );

        // $log = [
        //     '$response' => $response,
        //     '$topic'    => $this->get_route( $topic ),
        // ];
        
        // file_put_contents( dirname( __FILE__ ) . '/log.json', json_encode( $log ) );
    }
}