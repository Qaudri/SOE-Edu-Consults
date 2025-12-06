<?php
namespace Bookster\Services;

use Bookster\Features\Enums\ObjectTypeEnum;
use Bookster\Features\Utils\SingletonTrait;
use Bookster\Models\TransactionModel;
use Bookster\Features\Errors\NotFoundException;

/**
 * Logic for Transaction Models
 *
 * @method static TransactionsService get_instance()
 */
class TransactionsService extends BaseService {
    use SingletonTrait;

    /** @var AssignmentsService */
    private $assignments_service;
    /** @var BookingsService */
    private $bookings_service;

    protected function __construct() {
        $this->assignments_service = AssignmentsService::get_instance();
        $this->bookings_service    = BookingsService::get_instance();
    }

    /**
     * @param int $transaction_id
     * @return TransactionModel
     */
    public function get_by_id( int $transaction_id ) {
        $transaction = TransactionModel::find( $transaction_id );
        if ( ! $transaction ) {
            throw new NotFoundException( 'Transaction Not Found', ObjectTypeEnum::TRANSACTION, $transaction_id );
        }
        return $transaction;
    }

    /**
     * @param int $appointment_id
     * @return TransactionModel[]
     */
    public function get_by_appt_id( $appointment_id ) {
        return TransactionModel::where( [ 'appointment_id' => $appointment_id ] );
    }

    /**
     * @param string $transaction_secret_id
     * @return TransactionModel[]
     */
    public function get_by_transaction_secret_id( $transaction_secret_id ) {
        return TransactionModel::where( [ 'transaction_secret_id' => $transaction_secret_id ] );
    }

    public function insert( array $attributes ): TransactionModel {
        $transaction = TransactionModel::insert( TransactionModel::prepare_saved_data( $attributes ) );
        if ( is_null( $transaction ) ) {
            global $wpdb;
            throw new \Exception( esc_html( 'Error Saving Transaction: ' . $wpdb->last_error ) );
        }
        return $transaction;
    }

    public function update( int $transaction_id, array $data ): TransactionModel {
        $transaction = TransactionModel::find( $transaction_id );
        if ( ! $transaction ) {
            throw new NotFoundException( 'Transaction Not Found', ObjectTypeEnum::TRANSACTION, $transaction_id );
        }

        $success = $transaction->update( TransactionModel::prepare_saved_data( $data ) );
        if ( false === $success ) {
            global $wpdb;
            throw new \Exception( esc_html( 'Error Saving Transaction: ' . $wpdb->last_error ) );
        }
        return $transaction;
    }

    public function delete_by_appt_id( $appointment_id ) {
        $success = TransactionModel::delete_where( [ 'appointment_id' => $appointment_id ] );

        if ( false === $success ) {
            global $wpdb;
            throw new \Exception( esc_html( 'Error Editing Data: ' . $wpdb->last_error ) );
        }

        return $success;
    }
}
