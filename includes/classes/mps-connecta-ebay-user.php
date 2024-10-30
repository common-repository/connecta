<?php

defined('ABSPATH') or exit();

/*
|--------------------------------------------------------------------------
| MPS Ebay User
|--------------------------------------------------------------------------
|
| This class represents a user which has placed an order through eBay.
|
| Adds some metadata which signifies that the user has previously placed an ebay order.
|
| Check whether any given user has placed an ebay order using
| MPS_Ebay_User::is_ebay_user
|
 */
class MPS_Ebay_User extends WC_Customer
{
    /**
     * {NEW, EXISTING}
     * @var String
     */
    private $__ebay_account_status;

    /**
     * Fetch user data
     * @param Integer $user_id
     */
    public function __construct($user_id)
    {
        parent::__construct($user_id);
        if ($this->get_id()) {
            $this->__ebay_account_status = get_user_meta($this->get_id(), 'mps_ebay_account_status', true) ?: "NEW"; // Default is NEW
        }
    }

    /**
     * Either finds an existing user, or creates a new user
     * using data from an eBay order
     *
     * @param String $username
     * @param String $email
     * @param String $phone_number
     * @return (MPS_Ebay_User|False)
     */
    public static function find_or_create_user($username, $email, $phone_number)
    {

        $user = get_user_by('email', $email)->ID;
        $user = new WC_Customer($user);

        // is existing ebay user, just return the user, don't create another
        if ($user) {

            if (MPS_Ebay_User::is_ebay_user($user->get_id())) {

                // set the users status to 'existing'
                $ebay_user = new MPS_Ebay_User($user->get_id());
                $ebay_user->set_ebay_account_status('EXISTING');

            } else {

                // make existing user into an ebay user
                MPS_Ebay_User::make_ebay_user($user->get_id());
            }

            return new MPS_Ebay_User($user->get_id());
        }

        // is not an existing user, so create one
        if (!$user) {

            // auto password is the first 8 characters of the md5 hash of: "username=email"
            $auto_pass = substr(md5($username . "=" . $email), 0, 8);

            $user_id = wc_create_new_customer($email, $username, $auto_pass);

            if (is_wp_error($user_id)) {
                // failed to create user: likely because username is empty
                // or username/email already exists
                throw new ConnectaFailedToCreateUser();
            }

            // fetch the newly created user
            $user = get_user_by('id', $user_id);

            // set the phone number
            update_user_meta($user_id, 'billing_phone', $phone);

            // they're a new ebay customer (first order)
            add_user_meta($user->get_id(), 'mps_ebay_account_status', 'NEW');

            // inform the user that they should change their password
            update_user_option($user_id, 'default_password_nag', true, true);

            // send email with login credentials
            wp_mail($email, get_bloginfo('name') . ": Reset Password", sprintf("
                We have created an account for you, your login credentials are \n
                \n \n
                Email: %s
                Password: %s
                \n \n
                This password has been auto generated, please make sure to
                change it as soon as you login!
                ", $email, $auto_pass)
            );
        }

        // return the newly created ebay user
        return new MPS_Ebay_User($user->get_id());
    }

    /**
     * Check the eBay meta fields are set, to determine
     * if the user has ordered from ebay before
     *
     * @param Integer $user_id
     * @return boolean
     */
    public static function is_ebay_user($user_id)
    {
        return get_user_meta($user_id, 'mps_ebay_account_status', true);
    }

    /**
     * Makes a non-ebay user into an ebay user
     * @param Integer $user_id
     * @return void
     */
    public static function make_ebay_user($user_id)
    {
        return update_user_meta($user_id, 'mps_ebay_account_status', 'NEW');
    }

    /**
     * Gets the users account status
     * @return String
     */
    public function get_ebay_account_status()
    {
        return $this->__ebay_account_status;
    }

    /**
     * Gets the users account formatted status
     * @return String
     */
    public function get_ebay_account_formatted_status()
    {
        switch ($this->get_ebay_account_status()) {
            case "NEW":return "New Customer!";
            case "EXISTING":return "Existing Customer";
            default:return '-';
        }
    }

    /**
     * Sets the user status
     * @return Boolean success
     */
    public function set_ebay_account_status($value)
    {
        if (!in_array($value, ["NEW", "EXISTING"])) {
            return false;
        }

        $success = update_user_meta($this->get_id(), 'mps_ebay_account_status', $value);

        if ($success) {
            $this->__ebay_account_status = $value;
        }

        return $success;
    }
}
