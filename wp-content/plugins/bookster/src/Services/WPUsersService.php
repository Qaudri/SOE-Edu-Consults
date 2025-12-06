<?php
namespace Bookster\Services;

use Bookster\Features\Utils\SingletonTrait;

/**
 * WP Users Service
 *
 * @method static WPUserService get_instance()
 */
class WPUsersService extends BaseService {
    use SingletonTrait;

    public function delete( int $wp_user_id ) {
        return wp_delete_user( $wp_user_id );
    }

    /**
     * May be generate wp user
     *
     * @param  string $email
     * @param  string $first_name
     * @param  string $last_name
     * @param  string $role
     * @return int   $wp_user_id
     */
    public function maybe_generate_wp_user( $email, $first_name, $last_name, $role = false ): int {
        $user = get_user_by( 'email', $email );
        if ( ! $user ) {
            $full_name = $first_name . ' ' . $last_name;
            $user_data = [
                'user_pass'       => wp_generate_password(),
                'user_login'      => $email,
                'user_email'      => $email,
                'first_name'      => $first_name,
                'last_name'       => $last_name,
                'display_name'    => $full_name,
                'nickname'        => $full_name,
                'user_registered' => gmdate( 'Y-m-d h:i:s', time() ),
            ];
            if ( false !== $role ) {
                $user_data['role'] = $role;
            }

            $wp_user_id = wp_insert_user( $user_data );
            if ( is_wp_error( $wp_user_id ) ) {
                throw new \Exception( 'Error Creating User: ' . $wp_user_id->get_error_message() );
            }
            return $wp_user_id;
        } elseif ( false !== $role ) {
                $user->add_role( $role );
        }//end if

        return $user->ID;
    }
}
