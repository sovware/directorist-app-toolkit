<?php

namespace DirectoristAppToolkit\Controller\Notification\Helper;

trait Route_Helper {
    public static function get_route( $document_id = '' ) {
        $project_id = get_directorist_option('app_firebase_project_id', '', true);

        return "https://firestore.googleapis.com/v1/projects/{$project_id}/databases/(default)/documents/notifications/{$document_id}/notifications";
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
        // $authorization_key = get_directorist_option('app_firebase_authorization_key', '', true);

        return [
            'Content-Type' => 'application/json;charset=UTF-8',
                // 'Authorization' => "key={$authorization_key}",
        ];
    }

    public static function get_topic_id( $email = '' ) {
        return join( '_', unpack( 'C*', $email ) );
    }

    public static function get_current_time() {
        return date(DATE_ISO8601, strtotime( current_time( 'mysql' ) ));
    }
}
