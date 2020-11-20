<?php
/*
 * Copyright (c) 2020 PayGate (Pty) Ltd
 *
 * Author: App Inlet (Pty) Ltd
 *
 * Released under the GNU General Public License
 *
 */

class ControllerExtensionPaymentPaySubs extends Controller
{
    private $error = array();

    public function index()
    {
        $this->load->language( 'extension/payment/paysubs' );
        $this->document->setTitle( $this->language->get( 'heading_title' ) );
        $this->load->model( 'setting/setting' );

        if (  ( $this->request->server['REQUEST_METHOD'] == 'POST' ) && $this->validate() ) {
            $this->model_setting_setting->editSetting( 'payment_paysubs', $this->request->post );

            $this->session->data['success'] = $this->language->get( 'text_success' );

            $this->response->redirect( $this->url->link( 'marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true ) );
        }

        $data['heading_title'] = $this->language->get( 'heading_title' );

        $this->load->model( 'localisation/order_status' );
        $data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();
        if ( isset( $this->error['warning'] ) ) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }

        if ( isset( $this->error['email'] ) ) {
            $data['error_email'] = $this->error['email'];
        } else {
            $data['error_email'] = '';
        }

        if ( isset( $this->error['merchant'] ) ) {
            $data['error_merchant'] = $this->error['merchant'];
        } else {
            $data['error_merchant'] = '';
        }

        if ( isset( $this->error['suffix'] ) ) {
            $data['error_suffix'] = $this->error['suffix'];
        } else {
            $data['error_suffix'] = '';
        }

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get( 'text_home' ),
            'href' => $this->url->link( 'common/home', 'user_token=' . $this->session->data['user_token'], true ),
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get( 'text_extension' ),
            'href' => $this->url->link( 'marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true ),
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get( 'heading_title' ),
            'href' => $this->url->link( 'extension/payment/paysubs', 'user_token=' . $this->session->data['user_token'], true ),
        );

        $data['action'] = $this->url->link( 'extension/payment/paysubs', 'user_token=' . $this->session->data['user_token'], true );
        $data['cancel'] = $this->url->link( 'marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true );

        $data['entry_merchant_id']      = $this->language->get( 'entry_merchant_id' );
        $data['entry_completed_status'] = $this->language->get( 'entry_completed_status' );
        $data['entry_failed_status']    = $this->language->get( 'entry_failed_status' );

        if ( isset( $this->request->post['payment_paysubs_completed_status_id'] ) ) {
            $data['payment_paysubs_completed_status_id'] = $this->request->post['payment_paysubs_completed_status_id'];
        } else {
            $data['payment_paysubs_completed_status_id'] = $this->config->get( 'payment_paysubs_completed_status_id' );
        }

        if ( isset( $this->request->post['payment_paysubs_failed_status_id'] ) ) {
            $data['payment_paysubs_failed_status_id'] = $this->request->post['payment_paysubs_failed_status_id'];
        } else {
            $data['payment_paysubs_failed_status_id'] = $this->config->get( 'payment_paysubs_failed_status_id' );
        }

        if ( isset( $this->request->post['payment_paysubs_merchant_id'] ) ) {
            $data['payment_paysubs_merchant_id'] = $this->request->post['payment_paysubs_merchant_id'];
        } else {
            $data['payment_paysubs_merchant_id'] = $this->config->get( 'payment_paysubs_merchant_id' );
        }

        if ( isset( $this->request->post['payment_paysubs_sort_order'] ) ) {
            $data['payment_paysubs_sort_order'] = $this->request->post['payment_paysubs_sort_order'];
        } else {
            $data['payment_paysubs_sort_order'] = $this->config->get( 'payment_paysubs_sort_order' );
        }

        if ( isset( $this->request->post['payment_paysubs_status'] ) ) {
            $data['payment_paysubs_status'] = $this->request->post['payment_paysubs_status'];
        } else {
            $data['payment_paysubs_status'] = $this->config->get( 'payment_paysubs_status' );
        }

        if ( isset( $this->request->post['payment_paysubs_suffix_order'] ) ) {
            $data['payment_paysubs_suffix_order'] = $this->request->post['payment_paysubs_suffix_order'];
        } else {
            $data['payment_paysubs_suffix_order'] = $this->config->get( 'payment_paysubs_suffix_order' );
        }

        $data['recur_payment_enabled']   = $this->model_setting_setting->getSettingValue( 'payment_paysubs_recur_payment_enabled' );
        $data['recur_payment_frequency'] = $this->model_setting_setting->getSettingValue( 'payment_paysubs_recur_payment_frequency' );

        $data['header']      = $this->load->controller( 'common/header' );
        $data['column_left'] = $this->load->controller( 'common/column_left' );
        $data['footer']      = $this->load->controller( 'common/footer' );

        $this->response->setOutput( $this->load->view( 'extension/payment/paysubs', $data ) );
    }

    protected function validate()
    {
        if ( !$this->user->hasPermission( 'modify', 'extension/payment/paysubs' ) ) {
            $this->error['warning'] = $this->language->get( 'error_permission' );
        }

        if ( !$this->request->post['payment_paysubs_merchant_id'] ) {
            $this->error['merchant'] = $this->language->get( 'error_merchant' );
        }

        if ( $suffix = $this->request->post['payment_paysubs_suffix_order'] ) {
            if ( preg_match( '/[^a-zA-Z0-9]+/', $suffix ) ) {
                $this->error['suffix'] = $this->language->get( 'error_suffix' );
            }
        }

        return !$this->error;
    }
}
