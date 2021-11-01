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
        $receiver_name = 'Someone';
        $reviewer_name = 'Someone';
        $message       = ( isset( $_POST["message"] ) ) ? stripslashes(esc_textarea($_POST["message"])) : '';

        $email = ( isset( $action_args['to_email'] ) ) ? $action_args['to_email'] : '';
        $topic = $this->get_topic_id( $email );

        $listing_id    = 37;
        // $listing_id = ( isset( $action_args['listing_id'] ) ) ? $action_args['listing_id'] : 0;
        // $listing_title = get_the_title( $listing_id );
        $listing_title = 'Sun Apartment';
        
        $order_id          = 123;
        $listing_thumbnail = 'https://images.unsplash.com/photo-1480714378408-67cf0d13bc1b?ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&ixlib=rb-1.2.1&auto=format&fit=crop&w=1170&q=80';
        $user_avater_1     = 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&ixlib=rb-1.2.1&auto=format&fit=crop&w=687&q=80';
        $user_avater_2     = 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&ixlib=rb-1.2.1&auto=format&fit=crop&w=687&q=80';
        
        $order_notification_title = "Your Order #{$order_id} is Received";
        $order_fields = [
            "fields" => [
                "title"           => [ "stringValue" => $order_notification_title ],
                "font_icon"       => [ "stringValue" => 'fas fa-receipt' ],
                "font_icon_color" => [ "stringValue" => '#F65490' ],
                "data"            => [ "mapValue" => [ "fields" => [
                    'type'   => [ "stringValue" => 'orderNotification' ],
                    'action' => [ "stringValue" => 'navigateToNotificationScreen' ],
                ]]],
                "topic"        => [ "stringValue" => $topic ],
                "seen"         => [ "booleanValue" => false ],
                "date_created" => [ "stringValue" => $this->get_current_time() ],
            ]
        ];

        $listing_notification_title = "Your listing {$listing_title} has been submitted";
        $listing_fields = [
            "fields" => [
                "title"      => [ "stringValue" => $listing_notification_title ],
                "image"      => [ "stringValue" => $listing_thumbnail ],
                "image_icon" => [ "stringValue" => $user_avater_1 ],
                "data"       => [ "mapValue" => [ "fields" => [
                    'type'       => [ "stringValue" => 'listingNotification' ],
                    'action'     => [ "stringValue" => 'navigateToSingleListingScreen' ],
                    'listing_id' => [ "stringValue" => "{$listing_id}" ],
                ]]],
                "topic"        => [ "stringValue" => $topic ],
                "seen"         => [ "booleanValue" => false ],
                "date_created" => [ "stringValue" => $this->get_current_time() ],
            ]
        ];

        $message_notification_title = "A message from $sender_name is received";
        $message_fields = [
            "fields" => [
                "title"      => [ "stringValue" => $message_notification_title ],
                "image_icon" => [ "stringValue" => $user_avater_2 ],
                "data"       => [ "mapValue" => [ "fields" => [
                    'type'                 => [ "stringValue" => 'messageNotificaton' ],
                    'action'               => [ "stringValue" => 'navigateToMessageScreen' ],
                    'from_user_id'         => [ "stringValue" => "1" ],
                    // 'from_user_avater_src' => [ "stringValue" => $user_avater_2 ],
                    'listing_id'           => [ "stringValue" => "{$listing_id}" ],
                    'message'              => [ "stringValue" => $message ],
                ]]],
                "topic"        => [ "stringValue" => $topic ],
                "seen"         => [ "booleanValue" => false ],
                "date_created" => [ "stringValue" => $this->get_current_time() ],
            ]
        ];

        $group_message_notification_title = "$sender_name has sent a message to $receiver_name";
        $group_message_fields = [
            "fields" => [
                "title"           => [ "stringValue" => $group_message_notification_title ],
                "font_icon"       => [ "stringValue" => 'fas fa-user-friends' ],
                "font_icon_color" => [ "stringValue" => '#8C78FE' ],
                "data"            => [ "mapValue" => [ "fields" => [
                    'type'                 => [ "stringValue" => 'messageNotificaton' ],
                    'action'               => [ "stringValue" => 'navigateToMessageScreen' ],
                    'from_user_id'         => [ "stringValue" => "1" ],
                    // 'from_user_avater_src' => [ "stringValue" => $user_avater_1 ],
                    'to_user_id'           => [ "stringValue" => "1" ],
                    // 'to_user_avater_src'   => [ "stringValue" => $user_avater_2 ],
                    'listing_id'           => [ "stringValue" => "{$listing_id}" ],
                    'message'              => [ "stringValue" => $message ],
                ]]],
                "topic"        => [ "stringValue" => $topic ],
                "seen"         => [ "booleanValue" => false ],
                "date_created" => [ "stringValue" => $this->get_current_time() ],
            ]
        ];

        $review_notification_title = "$reviewer_name reviewed on {$listing_title}";
        $review_fields = [
            "fields" => [
                "title"      => [ "stringValue" => $review_notification_title ],
                "image"      => [ "stringValue" => $listing_thumbnail ],
                "image_icon" => [ "stringValue" => $user_avater_1 ],
                "data"       => [ "mapValue" => [ "fields" => [
                    'type'       => [ "stringValue" => 'reviewNotification' ],
                    'action'     => [ "stringValue" => 'navigateToSingleListingScreen' ],
                    'listing_id' => [ "stringValue" => "{$listing_id}" ],
                ]]],
                "topic"        => [ "stringValue" => $topic ],
                "seen"         => [ "booleanValue" => false ],
                "date_created" => [ "stringValue" => $this->get_current_time() ],
            ]
        ];

        $author_notification_title = "New author request has been received";
        $author_fields = [
            "fields" => [
                "title"      => [ "stringValue" => $author_notification_title ],
                "image_icon" => [ "stringValue" => $user_avater_2 ],
                "data"       => [ "mapValue" => [ "fields" => [
                    'type'    => [ "stringValue" => 'authorNotification' ],
                    'action'  => [ "stringValue" => 'navigateToAuthorProfileScreen' ],
                    'user_id' => [ "stringValue" => "1" ],
                ]]],
                "topic"        => [ "stringValue" => $topic ],
                "seen"         => [ "booleanValue" => false ],
                "date_created" => [ "stringValue" => $this->get_current_time() ],
            ]
        ];

        $fields = $order_fields;
        // $fields = $listing_fields;
        // $fields = $message_fields;
        // $fields = $group_message_fields;
        // $fields = $review_fields;
        // $fields = $author_fields;

        wp_remote_post( $this->get_route( $topic ), self::get_route_args( $fields ) );        
    }
}