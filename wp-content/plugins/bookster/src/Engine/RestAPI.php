<?php
namespace Bookster\Engine;

use Bookster\Controllers\AddonsController;
use Bookster\Features\Utils\SingletonTrait;
use Bookster\Controllers\SettingsController;
use Bookster\Controllers\AgentsController;
use Bookster\Controllers\AsAgentController;
use Bookster\Controllers\CustomersController;
use Bookster\Controllers\AsCustomersController;
use Bookster\Controllers\ServicesController;
use Bookster\Controllers\ServicesCategoriesController;
use Bookster\Controllers\AppointmentsController;
use Bookster\Controllers\AnalyticsController;
use Bookster\Controllers\AuthController;
use Bookster\Controllers\BookingRequestController;

/**
 * Bookster Rest API
 */
class RestAPI {
    use SingletonTrait;

    /** Hooks Initialization */
    protected function __construct() {
        add_action( 'rest_api_init', [ $this, 'add_bookster_endpoint' ] );
    }

    /**
     * Add Bookster Endpoints
     */
    public function add_bookster_endpoint() {
        SettingsController::get_instance();
        ServicesCategoriesController::get_instance();
        ServicesController::get_instance();
        AgentsController::get_instance();
        CustomersController::get_instance();
        AppointmentsController::get_instance();

        BookingRequestController::get_instance();
        AnalyticsController::get_instance();
        AuthController::get_instance();
        AddonsController::get_instance();

        AsAgentController::get_instance();
        AsCustomersController::get_instance();
    }
}
