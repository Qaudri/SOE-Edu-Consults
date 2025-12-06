<?php
namespace Bookster\Features;

use Bookster\Features\Enums\LogLevelEnum;
use Bookster\Models\LogModel;
use Bookster\Features\Errors\Interfaces\LoggableException;

/**
 * Logger Logic
 */
class Logger {

    public const LOG_LEVEL_OPTION = 'bookster_log_level';
    public const LOG_LEVELS       = [
        LogLevelEnum::NONE    => 0,
        LogLevelEnum::ERROR   => 1,
        LogLevelEnum::WARNING => 2,
        LogLevelEnum::INFO    => 3,
        LogLevelEnum::DEBUG   => 4,
    ];

    public static function needs_to_log( string $log_level ): bool {
        $log_level_option = get_option( self::LOG_LEVEL_OPTION, LogLevelEnum::NONE );
        return self::LOG_LEVELS[ $log_level ] <= self::LOG_LEVELS[ $log_level_option ];
    }

    public static function log( LogModel $log, bool $force = false ) {
        if ( false === $force && ! self::needs_to_log( $log->log_level ) ) {
            return;
        }

        $log->log_detail = wp_json_encode( $log->log_detail );
        $log->save();
    }

    public static function log_throwable( \Throwable $ex, bool $force = false ) {
        if ( $ex instanceof LoggableException ) {
            self::log( $ex->get_log_model(), $force );

        } else {
            $log                  = new LogModel();
            $log->log_object_id   = null;
            $log->log_object_type = null;
            $log->log_level       = LogLevelEnum::ERROR;
            $log->log_message     = $ex->getMessage();
            $log->log_detail      = [
                'trace' => $ex->getTrace(),
            ];

            self::log( $log, $force );
        }
    }

    public static function log_msg( string $message, $log_level = LogLevelEnum::ERROR, bool $force = false ) {
        $log                  = new LogModel();
        $log->log_object_id   = null;
        $log->log_object_type = null;
        $log->log_level       = $log_level;
        $log->log_message     = $message;
        $log->log_detail      = [];

        self::log( $log, $force );
    }

    public static function log_error( string $message ) {
        self::log_msg( $message, LogLevelEnum::ERROR );
    }

    public static function log_warning( string $message ) {
        self::log_msg( $message, LogLevelEnum::WARNING );
    }

    public static function log_info( string $message ) {
        self::log_msg( $message, LogLevelEnum::INFO );
    }

    public static function log_debug( string $message ) {
        self::log_msg( $message, LogLevelEnum::DEBUG );
    }
}
