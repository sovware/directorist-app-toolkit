<?php

namespace DirectoristAppToolkit\Controller\Notification\Message_Notification;

use DirectoristAppToolkit\Helper\Traits as HelperTraits;


class Init {

    use HelperTraits\Service_Registrar;

    public function __construct() {
        $this->register_serivces( $this->get_controllers() );
    }

    /**
     * Get the controllers
     * 
     * @return array a list of controllers
     * 
     */
    public static function get_controllers() {
        return [
            Notify_Listing_Owner_For_New_Message::class,
        ];
    }
}