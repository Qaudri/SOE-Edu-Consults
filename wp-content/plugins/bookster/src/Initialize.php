<?php
namespace Bookster;

use Bookster\Features\Utils\SingletonTrait;

/**
 * Bookster Plugin Initializer
 */
class Initialize {

    use SingletonTrait;

    /**
     * The Constructor that load the engine classes
     */
    protected function __construct() {
        \Bookster\Engine\ActDeact::get_instance();
        \Bookster\Engine\RestAPI::get_instance();
        \Bookster\Engine\Ajax::get_instance();
        \Bookster\Engine\Auth::get_instance();
        \Bookster\Engine\Admin::get_instance();

        \Bookster\Engine\Booking\BookingLogic::get_instance();
        \Bookster\Engine\Tasks\RegisterTasks::get_instance();
        \Bookster\Engine\Register\RegisterFacade::get_instance();

        \Bookster\Engine\BEPages\ManagerPage::get_instance();
        \Bookster\Engine\BEPages\AgentPage::get_instance();
        \Bookster\Engine\BEPages\IntroPage::get_instance();

        \Bookster\Engine\FEBlocks\CustomerDashboardShortcode::get_instance();
        \Bookster\Engine\FEBlocks\BookingButtonShortcode::get_instance();
        \Bookster\Engine\FEBlocks\BookingFormShortcode::get_instance();
        \Bookster\Engine\FEBlocks\BookingButtonBlock::get_instance();
        \Bookster\Engine\FEBlocks\CustomerDashboardBlock::get_instance();
        \Bookster\Engine\FEBlocks\BookingFormBlock::get_instance();

        \Bookster\Engine\Intergration\EmailNotification::get_instance();
    }
}
