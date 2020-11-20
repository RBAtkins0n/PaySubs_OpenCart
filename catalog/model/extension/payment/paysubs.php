<?php
/**
 *
 * PayGate Opencart Plugin
 *
 * @author App Inlet (Pty) Ltd
 * @author info@appinlet.com
 * @version 1.0.1
 * @package Opencart
 * @subpackage payment
 * @copyright Copyright (C) 2020 PayGate (Pty) Ltd
 */

class ModelExtensionPaymentPaySubs extends Model
{
    public function getMethod( $address, $total )
    {
        //echo "<script>alert('status false')</script>";

        $this->load->language( 'extension/payment/paysubs' );

        $query = $this->db->query( "SELECT * FROM " . DB_PREFIX . "zone_to_geo_zone WHERE geo_zone_id = '" . (int) $this->config->get( 'payment_paysubs_geo_zone_id' ) . "' AND country_id = '" . (int) $address['country_id'] . "' AND (zone_id = '" . (int) $address['zone_id'] . "' OR zone_id = '0')" );

        if ( $this->config->get( 'payment_paysubs_total' ) > $total ) {
            $status = false;
        } elseif ( !$this->config->get( 'payment_paysubs_geo_zone_id' ) ) {
            $status = true;
        } elseif ( $query->num_rows ) {
            $status = true;
        } else {
            $status = false;
        }

        $currencies = array(
            'ZAR',
            'NAD',
            'BWP',
        );

        if ( !in_array( strtoupper( $this->config->get( 'config_currency' ) ), $currencies ) ) {
            $status = true;
        }

        $method_data = array();

        if ( $status ) {
            $method_data = array(
                'code'       => 'paysubs',
                'title'      => $this->language->get( 'text_paygate_checkout' ),
                'terms'      => '',
                'sort_order' => $this->config->get( 'payment_paysubs_sort_order' ),
            );
        }

        return $method_data;
    }
}
