<?php

if (!defined('SUCURISCAN_INIT') || SUCURISCAN_INIT !== true) {
    if (!headers_sent()) {
        /* Report invalid access if possible. */
        header('HTTP/1.1 403 Forbidden');
    }
    exit(1);
}

/**
 * Print a list of all the registered users that are currently in session.
 *
 * @return string The HTML code displaying a list of all the users logged in at the moment.
 */
function sucuriscan_loggedin_users_panel()
{
    // Get user logged in list.
    $params = array(
        'LoggedInUsers.List' => '',
        'LoggedInUsers.Total' => 0,
    );

    $logged_in_users = sucuriscan_get_online_users(true);

    if (is_array($logged_in_users) && !empty($logged_in_users)) {
        $params['LoggedInUsers.Total'] = count($logged_in_users);
        $counter = 0;

        foreach ((array) $logged_in_users as $logged_in_user) {
            $counter++;
            $logged_in_user['last_activity_datetime'] = SucuriScan::datetime($logged_in_user['last_activity']);
            $logged_in_user['user_registered_datetime'] = SucuriScan::datetime(strtotime($logged_in_user['user_registered']));

            $params['LoggedInUsers.List'] .= SucuriScanTemplate::getSnippet(
                'lastlogins-loggedin',
                array(
                    'LoggedInUsers.Id' => $logged_in_user['user_id'],
                    'LoggedInUsers.UserURL' => SucuriScan::adminURL('user-edit.php?user_id=' . $logged_in_user['user_id']),
                    'LoggedInUsers.UserLogin' => $logged_in_user['user_login'],
                    'LoggedInUsers.UserEmail' => $logged_in_user['user_email'],
                    'LoggedInUsers.LastActivity' => $logged_in_user['last_activity_datetime'],
                    'LoggedInUsers.Registered' => $logged_in_user['user_registered_datetime'],
                    'LoggedInUsers.RemoveAddr' => $logged_in_user['remote_addr'],
                    'LoggedInUsers.CssClass' => ($counter % 2 === 0) ? '' : 'alternate',
                )
            );
        }
    }

    return SucuriScanTemplate::getSection('lastlogins-loggedin', $params);
}

/**
 * Get a list of all the registered users that are currently in session.
 *
 * @param  boolean $add_current_user Whether the current user should be added to the list or not.
 * @return array                     List of registered users currently in session.
 */
function sucuriscan_get_online_users($add_current_user = false)
{
    $users = array();

    if (SucuriScan::isMultiSite()) {
        $users = get_site_transient('online_users');
    } else {
        $users = get_transient('online_users');
    }

    // If not online users but current user is logged in, add it to the list.
    if (empty($users) && $add_current_user) {
        $current_user = wp_get_current_user();

        if ($current_user->ID > 0) {
            sucuriscan_set_online_user($current_user->user_login, $current_user);

            return sucuriscan_get_online_users();
        }
    }

    return $users;
}

/**
 * Update the list of the registered users currently in session.
 *
 * Useful when you are removing users and need the list of the remaining users.
 *
 * @param  array   $logged_in_users List of registered users currently in session.
 * @return boolean                  Either TRUE or FALSE representing the success or fail of the operation.
 */
function sucuriscan_save_online_users($logged_in_users = array())
{
    $expiration = 30 * 60;

    if (SucuriScan::isMultiSite()) {
        return set_site_transient('online_users', $logged_in_users, $expiration);
    } else {
        return set_transient('online_users', $logged_in_users, $expiration);
    }
}

if (!function_exists('sucuriscan_unset_online_user_on_logout')) {
    /**
     * Remove a logged in user from the list of registered users in session when
     * the logout page is requested.
     *
     * @return void
     */
    function sucuriscan_unset_online_user_on_logout()
    {
        $remote_addr = SucuriScan::getRemoteAddr();
        $current_user = wp_get_current_user();
        $user_id = $current_user->ID;

        sucuriscan_unset_online_user($user_id, $remote_addr);
    }

    add_action('wp_logout', 'sucuriscan_unset_online_user_on_logout');
}

/**
 * Remove a logged in user from the list of registered users in session using
 * the user identifier and the ip address of the last computer used to login.
 *
 * @param  integer $user_id     User identifier of the account that will be logged out.
 * @param  integer $remote_addr IP address of the computer where the user logged in.
 * @return boolean              Either TRUE or FALSE representing the success or fail of the operation.
 */
function sucuriscan_unset_online_user($user_id = 0, $remote_addr = 0)
{
    $logged_in_users = sucuriscan_get_online_users();

    // Remove the specified user identifier from the list.
    if (is_array($logged_in_users) && !empty($logged_in_users)) {
        foreach ($logged_in_users as $i => $user) {
            if ($user['user_id'] == $user_id
                && strcmp($user['remote_addr'], $remote_addr) == 0
            ) {
                unset($logged_in_users[ $i ]);
                break;
            }
        }
    }

    return sucuriscan_save_online_users($logged_in_users);
}

if (!function_exists('sucuriscan_set_online_user')) {
    /**
     * Add an user account to the list of registered users in session.
     *
     * @param  string  $user_login The name of the user account that just logged in the site.
     * @param  boolean $user       The WordPress object containing all the information associated to the user.
     * @return void
     */
    function sucuriscan_set_online_user($user_login = '', $user = false)
    {
        if ($user) {
            // Get logged in user information.
            $current_user = ($user instanceof WP_User) ? $user : wp_get_current_user();
            $current_user_id = $current_user->ID;
            $remote_addr = SucuriScan::getRemoteAddr();
            $current_time = current_time('timestamp');
            $logged_in_users = sucuriscan_get_online_users();

            // Build the dataset array that will be stored in the transient variable.
            $current_user_info = array(
                'user_id' => $current_user_id,
                'user_login' => $current_user->user_login,
                'user_email' => $current_user->user_email,
                'user_registered' => $current_user->user_registered,
                'last_activity' => $current_time,
                'remote_addr' => $remote_addr,
            );

            if (!is_array($logged_in_users) || empty($logged_in_users)) {
                $logged_in_users = array( $current_user_info );
                sucuriscan_save_online_users($logged_in_users);
            } else {
                $do_nothing = false;
                $update_existing = false;
                $item_index = 0;

                // Check if the user is already in the logged-in-user list and update it if is necessary.
                foreach ($logged_in_users as $i => $user) {
                    if ($user['user_id'] == $current_user_id
                        && strcmp($user['remote_addr'], $remote_addr) == 0
                    ) {
                        if ($user['last_activity'] < ($current_time - (15 * 60))) {
                            $update_existing = true;
                            $item_index = $i;
                            break;
                        } else {
                            $do_nothing = true;
                            break;
                        }
                    }
                }

                if ($update_existing) {
                    $logged_in_users[ $item_index ] = $current_user_info;
                    sucuriscan_save_online_users($logged_in_users);
                } elseif ($do_nothing) {
                    // Do nothing.
                } else {
                    $logged_in_users[] = $current_user_info;
                    sucuriscan_save_online_users($logged_in_users);
                }
            }
        }
    }

    add_action('wp_login', 'sucuriscan_set_online_user', 10, 2);
}
