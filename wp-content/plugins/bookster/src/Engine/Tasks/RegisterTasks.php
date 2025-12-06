<?php
namespace Bookster\Engine\Tasks;

use Bookster\Features\Utils\SingletonTrait;
use Bookster\Features\Enums\CronEnum;

/**
 * Register Tasks
 */
class RegisterTasks {
    use SingletonTrait;

    protected function __construct() {
        add_filter( 'cron_schedules', [ $this, 'add_cron_schedules' ] );

        CleanTransactionTask::get_instance();
        SendApptNoticeEmailsTask::get_instance();
    }

    public function add_cron_schedules( $schedules ) {
        $schedules[ CronEnum::EVERY_5_MINS ] = [
            'interval' => 5 * MINUTE_IN_SECONDS,
            'display'  => __( 'Every 5 Minutes', 'bookster' ),
        ];
        $schedules[ CronEnum::MONTHLY ]      = [
            'interval' => 30 * DAY_IN_SECONDS,
            'display'  => __( 'Once Monthly', 'bookster' ),
        ];
        return $schedules;
    }
}
