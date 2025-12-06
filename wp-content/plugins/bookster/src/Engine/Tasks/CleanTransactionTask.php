<?php
namespace Bookster\Engine\Tasks;

use Bookster\Features\Tasks\BaseTask;
use Bookster\Features\Utils\SingletonTrait;
use Bookster\Models\TransactionModel;
use Bookster\Features\Tasks\Dispatcher\IntervalDispatcher;
use Bookster\Features\Enums\CronEnum;

/**
 * Clean Orphaned Transactions Task
 *
 * @method static CleanTransactionTask get_instance()
 */
class CleanTransactionTask extends BaseTask {
    use SingletonTrait;

    protected $task_name = 'bookster_task_clean_transaction';

    protected function __construct() {
        parent::init_hooks();
    }

    public function task_callback( $args ) {
        global $wpdb;
        $table = $wpdb->prefix . TransactionModel::TABLE;
        $wpdb->query( "DELETE FROM {$table} WHERE appointment_id IS NULL and transaction_status = 'created' AND created_at < DATE_SUB( NOW(), INTERVAL 1 MONTH )" ); // phpcs:ignore
    }

    protected function create_dispatcher() {
        return new IntervalDispatcher( $this->task_name, CronEnum::MONTHLY );
    }
}
