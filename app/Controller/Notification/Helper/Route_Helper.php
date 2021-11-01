<?php

namespace DirectoristAppToolkit\Controller\Notification\Helper;

trait Route_Helper {
    public static function get_route( $document_id = '' ) {
        return "https://firestore.googleapis.com/v1/projects/directorist-app/databases/(default)/documents/notifications/{$document_id}/notifications";
    }

    public static function get_route_args( $args ) {
        return [
            'method'      => 'POST',
            'headers'     => self::get_route_headers(),
            'httpversion' => '1.0',
            'sslverify'   => false,
            'body'        => json_encode( $args )
        ];
    }

    public static function get_route_headers() {
        return [
            'Content-Type' => 'application/json;charset=UTF-8',
                // 'Authorization' => 'key=AAAAK1QbsOQ:APA91bGjR5P97RhV3Pom-hOIDUMwrBBQ9Lv5frAYjeVaaqK90O8XO4pUKV8168DekBz-7ffllrJfbhibWh9C1RY00GIwIHrb0pCAk_nEAF3Qt4YLqi80XBBAqaDUADSfmVNuUEKP2VIu',
        ];
    }

    public static function get_topic_id( $email = '' ) {
        return join( '_', unpack( 'C*', $email ) );
    }

    public static function get_current_time() {
        return date(DATE_ISO8601, strtotime( current_time( 'mysql' ) ));
    }
}
