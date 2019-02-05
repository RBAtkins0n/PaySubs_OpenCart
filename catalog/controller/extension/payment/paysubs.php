<?php
/*
 * Copyright (c) 2019 PayGate (Pty) Ltd
 *
 * Author: App Inlet (Pty) Ltd
 * 
 * Released under the GNU General Public License
 * 
 */
class ControllerExtensionPaymentPaySubs extends Controller
{
    public function index()
    {
        $this->load->model( 'checkout/order' );
        $this->load->language( 'payment/paysubs' );
        $this->load->model( 'setting/setting' );

        $order_info = $this->model_checkout_order->getOrder( $this->session->data['order_id'] );

        if ( $order_info ) {

            $data['business']  = $this->config->get( 'payment_paysubs_merchant_id' );
            $data['item_name'] = html_entity_decode( $this->config->get( 'config_name' ), ENT_QUOTES, 'UTF-8' );

            $data['products'] = array();

            foreach ( $this->cart->getProducts() as $product ) {
                $option_data = array();

                foreach ( $product['option'] as $option ) {
                    if ( $option['type'] != 'file' ) {
                        $value = $option['value'];
                    } else {
                        $filename = $this->encryption->decrypt( $option['value'] );
                        $value    = utf8_substr( $filename, 0, utf8_strrpos( $filename, '.' ) );
                    }

                    $option_data[] = array(
                        'name'  => $option['name'],
                        'value' => ( utf8_strlen( $value ) > 20 ? utf8_substr( $value, 0, 20 ) . '..' : $value ),
                    );
                }

                $data['products'][] = array(
                    'name'     => $product['name'],
                    'model'    => $product['model'],
                    'price'    => $this->currency->format( $product['price'], $order_info['currency_code'], false, false ),
                    'quantity' => $product['quantity'],
                    'option'   => $option_data,
                    'weight'   => $product['weight'],
                );
            }

            $data['discount_amount_cart'] = 0;

            $total = $this->currency->format( $order_info['total'] - $this->cart->getSubTotal(), $order_info['currency_code'], false, false );

            if ( $total > 0 ) {
                $data['products'][] = array(
                    'name'     => $this->language->get( 'text_total' ),
                    'model'    => '',
                    'price'    => $total,
                    'quantity' => 1,
                    'option'   => array(),
                    'weight'   => 0,
                );
            } else {
                $data['discount_amount_cart'] -= $total;
            }

            $data['paysubs_merchant_id'] = $this->config->get( 'payment_paysubs_merchant_id' );
            $data['currency_code']       = $order_info['currency_code'];
            $data['first_name']          = html_entity_decode( $order_info['payment_firstname'], ENT_QUOTES, 'UTF-8' );
            $data['last_name']           = html_entity_decode( $order_info['payment_lastname'], ENT_QUOTES, 'UTF-8' );
            $data['address1']            = html_entity_decode( $order_info['payment_address_1'], ENT_QUOTES, 'UTF-8' );
            $data['address2']            = html_entity_decode( $order_info['payment_address_2'], ENT_QUOTES, 'UTF-8' );
            $data['city']                = html_entity_decode( $order_info['payment_city'], ENT_QUOTES, 'UTF-8' );
            $data['zip']                 = html_entity_decode( $order_info['payment_postcode'], ENT_QUOTES, 'UTF-8' );
            $data['country']             = $order_info['payment_iso_code_2'];
            $data['email']               = $order_info['email'];
            $data['return']              = $this->url->link( 'checkout/success' );
            $data['notify_url']          = $this->url->link( 'extension/payment/paysubs/callback', '', 'SSL' );
            $data['cancel_return']       = $this->url->link( 'extension/payment/paysubs/callback', '', 'SSL' );

            $data['paymentaction'] = 'sale';

            if ( $this->config->get( 'payment_paysubs_suffix_order' ) != "" ) {
                $data['custom'] = $this->session->data['order_id'] . $this->config->get( 'payment_paysubs_suffix_order' );
            } else {
                $data['custom'] = $this->session->data['order_id'];
            }

            $data['recur_payment_enabled']   = $this->model_setting_setting->getSettingValue( 'payment_paysubs_recur_payment_enabled' );
            $data['recur_payment_frequency'] = $this->model_setting_setting->getSettingValue( 'payment_paysubs_recur_payment_frequency' );

            $recur_frequencies               = ['', 'D', 'W', 'M', 'Q', '6', 'Y'];
            $data['recur_payment_frequency'] = $recur_frequencies[$data['recur_payment_frequency']];

            return $this->load->view( 'extension/payment/paysubs', $data );
        }
    }

    public function callback()
    {
        if ( !isset( $this->request->post['p1'] ) ) {
            $this->request->post = $this->request->get;
        }
        $this->load->language( 'extension/payment/paysubs' );

        if ( isset( $this->session->data['order_id'] ) ) {
            $order_id = $this->session->data['order_id'];

            $this->load->model( 'checkout/order' );

            $order_info = $this->model_checkout_order->getOrder( $order_id );

            $data['title'] = sprintf( $this->language->get( 'heading_title' ), $this->config->get( 'config_name' ) );

            if ( !$this->request->server['HTTPS'] ) {
                $data['base'] = $this->config->get( 'config_url' );
            } else {
                $data['base'] = $this->config->get( 'config_ssl' );
            }

            $data['heading_title'] = sprintf( $this->language->get( 'heading_title' ), $this->config->get( 'config_name' ) );

            $data['text_success_wait'] = sprintf( $this->language->get( 'text_success_wait' ), $this->url->link( 'checkout/success' ) );
            $data['text_failure_wait'] = sprintf( $this->language->get( 'text_failure_wait' ), $this->url->link( 'checkout/checkout', '', 'SSL' ) );

            if ( $order_info ) {
                if ( isset( $this->request->post['p3'] ) && strstr( $this->request->post['p3'], 'APPROVED' ) && !strstr( $this->request->post['p4'], 'Duplicate' ) ) {
                    $this->model_checkout_order->addOrderHistory( $order_id, $this->config->get( 'payment_paysubs_completed_status_id' ) );
                    $data['continue'] = $this->url->link( 'checkout/success' );

                    if ( file_exists( DIR_TEMPLATE . $this->config->get( 'config_template' ) . 'extension/payment/paysubs_success' ) ) {
                        $this->response->setOutput( $this->load->view( $this->config->get( 'config_template' ) . 'extension/payment/paysubs_success', $data ) );
                    } else {
                        $this->response->setOutput( $this->load->view( 'extension/payment/paysubs_success', $data ) );
                    }

                } else {
                    $this->model_checkout_order->addOrderHistory( $order_id, $this->config->get( 'payment_paysubs_failed_status_id' ) );
                    $data['continue'] = $this->url->link( 'checkout/cart' );

                    if ( file_exists( DIR_TEMPLATE . $this->config->get( 'config_template' ) . 'extension/payment/paysubs_failure' ) ) {
                        $this->response->setOutput( $this->load->view( $this->config->get( 'config_template' ) . 'extension/payment/paysubs_failure', $data ) );
                    } else {
                        $this->response->setOutput( $this->load->view( 'extension/payment/paysubs_failure', $data ) );
                    }

                }
            }
        } else {
            $this->response->setOutput( 'Transaction was not successful... Redirecting' );
            header( "Refresh: 10;url=index.php?route=checkout/cart" );
        }
    }
}
