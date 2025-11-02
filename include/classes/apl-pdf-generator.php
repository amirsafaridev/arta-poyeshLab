<?php
/**
 * PDF Generator Class
 * 
 * @package ArtaPoyeshLab
 * @since 1.0.0
 */

namespace APL\Classes;

if (!defined('ABSPATH')) {
    exit;
}

class APL_PDF_Generator {
    
    public function __construct() {
        // Initialize PDF generator
    }
    
    /**
     * Generate invoice PDF for order
     */
    public function generate_invoice_pdf($order_id) {
        // Ensure APL_Gregorian_Jalali class is loaded
        if (!class_exists('APL_Gregorian_Jalali')) {
            require_once ARTA_POYESHLAB_PLUGIN_DIR . 'include/classes/apl-gregorian_jalali.php';
        }
        
        // Get the order
        $order = wc_get_order($order_id);
        
        if (!$order) {
            return false;
        }
        
        // Convert order date to Jalali
        $order_date = $order->get_date_created();
        $jalali_date = \APL_Gregorian_Jalali::gregorian_to_jalali(
            $order_date->format('Y'),
            $order_date->format('n'),
            $order_date->format('j'),
            true
        );
        
        // Get order data
        $order_data = array(
            'id' => $order->get_id(),
            'number' => $order->get_order_number(),
            'status' => $order->get_status(),
            'total' => $order->get_total(),
            'currency' => $order->get_currency(),
            'currency_symbol' => get_woocommerce_currency_symbol($order->get_currency()),
            'date' => $jalali_date,
            'billing' => array(
                'first_name' => $order->get_billing_first_name(),
                'last_name' => $order->get_billing_last_name(),
                'email' => $order->get_billing_email(),
                'phone' => $order->get_billing_phone(),
                'address' => $order->get_formatted_billing_address()
            ),
            'items' => array()
        );
        
        // Get order items
        foreach ($order->get_items() as $item_id => $item) {
            $order_data['items'][] = array(
                'name' => $item->get_name(),
                'quantity' => $item->get_quantity(),
                'total' => $item->get_total(),
                'subtotal' => $item->get_subtotal()
            );
        }
        
        // Generate PDF URL
        return $this->get_pdf_url($order_data);
    }
    
    /**
     * Get PDF URL for order
     */
    private function get_pdf_url($order_data) {
        // Create a unique URL for the PDF
        $pdf_url = add_query_arg(array(
            'apl_action' => 'view_invoice',
            'order_id' => $order_data['id'],
            'nonce' => wp_create_nonce('apl_invoice_' . $order_data['id'])
        ), home_url());
        
        return $pdf_url;
    }
    
    /**
     * Output invoice PDF directly
     */
    public function output_invoice_pdf($order_id) {
        // Ensure APL_Gregorian_Jalali class is loaded
        if (!class_exists('APL_Gregorian_Jalali')) {
            require_once ARTA_POYESHLAB_PLUGIN_DIR . 'include/classes/apl-gregorian_jalali.php';
        }
        
        // Get the order
        $order = wc_get_order($order_id);
        
        if (!$order) {
            wp_die('سفارش یافت نشد');
        }
        
        // Convert order date to Jalali
        $order_date = $order->get_date_created();
        $jalali_date = \APL_Gregorian_Jalali::gregorian_to_jalali(
            $order_date->format('Y'),
            $order_date->format('n'),
            $order_date->format('j'),
            true
        );
        
        // Get order data
        $order_data = array(
            'id' => $order->get_id(),
            'number' => $order->get_order_number(),
            'status' => $order->get_status(),
            'total' => $order->get_total(),
            'currency' => $order->get_currency(),
            'currency_symbol' => get_woocommerce_currency_symbol($order->get_currency()),
            'date' => $jalali_date,
            'billing' => array(
                'first_name' => $order->get_billing_first_name(),
                'last_name' => $order->get_billing_last_name(),
                'email' => $order->get_billing_email(),
                'phone' => $order->get_billing_phone(),
                'address' => $order->get_formatted_billing_address()
            ),
            'items' => array()
        );
        
        // Get order items
        foreach ($order->get_items() as $item_id => $item) {
            $order_data['items'][] = array(
                'name' => $item->get_name(),
                'quantity' => $item->get_quantity(),
                'total' => $item->get_total(),
                'subtotal' => $item->get_subtotal()
            );
        }
        
        // Get insurance fee if exists
        $insurance_fee = 0;
        foreach ($order->get_fees() as $fee_id => $fee) {
            if ($fee->get_name() === 'حق بیمه') {
                // Fee amount is stored as negative, so we take absolute value
                $insurance_fee = abs($fee->get_total());
                break;
            }
        }
        $order_data['insurance_fee'] = $insurance_fee;
        
        // Output PDF content
        $this->output_pdf($order_data);
    }
    
    /**
     * Output PDF content
     */
    private function output_pdf($order_data) {
        // Use a simple approach: generate HTML that can be saved as PDF
        $this->generate_printable_html($order_data);
    }
    
    /**
     * Generate printable HTML for PDF
     */
    private function generate_printable_html($order_data) {
        // Set proper headers for HTML content
        header('Content-Type: text/html; charset=UTF-8');
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');
        
        $html = $this->get_invoice_html($order_data);
        
        echo '<!DOCTYPE html>
<html dir="rtl" lang="fa">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>فاکتور #' . $order_data['number'] . '</title>
      <style>
        @media print {
            body { 
                margin: 0; 
                padding: 0;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .no-print { display: none !important; }
            .invoice-container {
                box-shadow: none !important;
                border: none !important;
                margin: 0 !important;
                padding: 10px !important;
            }
            .invoice-info {
                gap: 20px !important;
            }
            .items-table {
                box-shadow: none !important;
            }
            .items-table th {
                background: #333 !important;
                color: white !important;
            }
            @page {
                margin: 1cm;
                size: A4;
            }
        }
        body {
            font-family: "Tahoma", "Arial", sans-serif;
            direction: rtl;
            margin: 0;
            padding: 20px;
            background: white;
            color: #000;
            line-height: 1.4;
        }
        .invoice-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border: 1px solid #ddd;
        }
        .header {
            text-align: center;
            border-bottom: 3px solid #333;
            padding: 30px 20px;
            margin-bottom: 40px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        .header h1 {
            color: #333;
            margin: 0 0 10px 0;
            font-size: 28px;
            font-weight: bold;
        }
        .header p {
            color: #666;
            margin: 8px 0;
            font-size: 16px;
        }
        .header .invoice-number {
            background: #333;
            color: white;
            padding: 8px 20px;
            border-radius: 20px;
            display: inline-block;
            margin-top: 10px;
            font-weight: bold;
        }
        .invoice-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            margin-bottom: 30px;
        }
        .invoice-details, .customer-details {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            border: 1px solid #e9ecef;
        }
        .invoice-details h3, .customer-details h3 {
            color: #333;
            border-bottom: 2px solid #666;
            padding-bottom: 10px;
            margin-bottom: 20px;
            font-size: 16px;
            font-weight: bold;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
        }
        .detail-label {
            font-weight: bold;
            color: #555;
        }
        .detail-value {
            color: #333;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .items-table th,
        .items-table td {
            border: 1px solid #e9ecef;
            padding: 15px 12px;
            text-align: right;
        }
        .items-table th {
            background: #333;
            color: white;
            font-weight: bold;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .items-table tbody tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        .items-table tbody tr:hover {
            background-color: #e9ecef;
        }
        .items-table .text-left {
            text-align: left;
        }
        .items-table .text-center {
            text-align: center;
        }
        .items-table .text-right {
            text-align: right;
        }
        .total-section {
            text-align: left;
            margin-top: 20px;
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            border: 1px solid #e9ecef;
        }
        .total-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding: 12px 0;
            border-bottom: 1px solid #e9ecef;
        }
        .total-row.final {
            border-top: 3px solid #333;
            border-bottom: none;
            font-weight: bold;
            font-size: 20px;
            margin-top: 15px;
            padding: 15px 0;
            background: #f8f9fa;
            border-radius: 4px;
            padding-left: 15px;
            padding-right: 15px;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }
        .status-completed {
            background-color: #d4edda;
            color: #155724;
        }
        .status-processing {
            background-color: #cce5ff;
            color: #004085;
        }
        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }
        .status-cancelled, .status-failed {
            background-color: #f8d7da;
            color: #721c24;
        }
        .status-refunded {
            background-color: #e2e3f1;
            color: #383d73;
        }
        .status-on-hold {
            background-color: #fff3cd;
            color: #856404;
        }
        .status-checkout-draft {
            background-color: #f8f9fa;
            color: #495057;
        }
        .footer {
            margin-top: 40px;
            text-align: center;
            border-top: 1px solid #ddd;
            padding-top: 20px;
            color: #666;
            font-size: 12px;
        }
        .print-button {
            position: fixed;
            top: 20px;
            left: 20px;
            background: #333;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <button class="print-button no-print" onclick="window.print()">چاپ فاکتور</button>
    
    ' . $html . '
    
    <script>
        // Auto-print when loaded
        window.onload = function() {
            setTimeout(function() {
                window.print();
            }, 500);
        };
        
        // Handle print dialog
        window.onafterprint = function() {
            // Optional: close window after printing
            // window.close();
        };
    </script>
</body>
</html>';
    }
    
    
    /**
     * Format price with Persian numbers
     */
    private function format_price($price) {
        return number_format($price, 0, '.', ',');
    }
    private function get_invoice_html($order_data) {
        $status_labels = array(
            'completed' => 'تکمیل شده',
            'processing' => 'در حال انجام',
            'pending' => 'در انتظار پرداخت',
            'cancelled' => 'لغو شده',
            'failed' => 'ناموفق',
            'refunded' => 'مسترد شده',
            'on-hold' => 'در انتظار بررسی',
            'checkout-draft' => 'پیش‌نویس'
        );
        
        $status_class = 'status-' . $order_data['status'];
        $status_label = isset($status_labels[$order_data['status']]) ? $status_labels[$order_data['status']] : $order_data['status'];
        
        $html = '<div class="invoice-container">
            <div class="header">
                <h1>آزمایشگاه پوش</h1>
                <p>فاکتور فروش</p>
                <div class="invoice-number">شماره فاکتور: #' . $order_data['number'] . '</div>
            </div>
            
            <div class="invoice-info">
                <div class="invoice-details">
                    <h3>اطلاعات فاکتور</h3>
                    <div class="detail-row">
                        <span class="detail-label">شماره سفارش:</span>
                        <span class="detail-value">#' . $order_data['number'] . '</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">تاریخ:</span>
                        <span class="detail-value">' . $order_data['date'] . '</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">وضعیت:</span>
                        <span class="detail-value">
                            <span class="status-badge ' . $status_class . '">' . $status_label . '</span>
                        </span>
                    </div>
                </div>
                
                <div class="customer-details">
                    <h3>اطلاعات مشتری</h3>
                    <div class="detail-row">
                        <span class="detail-label">نام:</span>
                        <span class="detail-value">' . $order_data['billing']['first_name'] . ' ' . $order_data['billing']['last_name'] . '</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">ایمیل:</span>
                        <span class="detail-value">' . $order_data['billing']['email'] . '</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">تلفن:</span>
                        <span class="detail-value">' . $order_data['billing']['phone'] . '</span>
                    </div>
                </div>
            </div>
            
            <table class="items-table">
                <thead>
                    <tr>
                        <th class="text-right">نام محصول</th>
                        <th class="text-center">تعداد</th>
                        <th class="text-left">قیمت واحد</th>
                        <th class="text-left">قیمت کل</th>
                    </tr>
                </thead>
                <tbody>';
        
        foreach ($order_data['items'] as $item) {
            $html .= '<tr>
                <td>' . esc_html($item['name']) . '</td>
                <td class="text-center">' . $item['quantity'] . '</td>
                <td class="text-left">' . $this->format_price($item['subtotal'] / $item['quantity']) . ' ' . $order_data['currency_symbol'] . '</td>
                <td class="text-left">' . $this->format_price($item['total']) . ' ' . $order_data['currency_symbol'] . '</td>
            </tr>';
        }
        
        $html .= '</tbody>
            </table>
            
            <div class="total-section">';
        
        // Display insurance fee if exists
        if (isset($order_data['insurance_fee']) && $order_data['insurance_fee'] > 0) {
            $html .= '
                <div class="total-row">
                    <span>حق بیمه:</span>
                    <span>' . $this->format_price($order_data['insurance_fee']) . ' ' . $order_data['currency_symbol'] . '</span>
                </div>';
        }
        
        $html .= '
                <div class="total-row final">
                    <span>مبلغ کل:</span>
                    <span>' . $this->format_price($order_data['total']) . ' ' . $order_data['currency_symbol'] . '</span>
                </div>
            </div>
            
            <div class="footer">
                <p>با تشکر از انتخاب شما</p>
                <p>آزمایشگاه پوش - ارائه خدمات آزمایشگاهی با کیفیت</p>
            </div>
        </div>';
        
        return $html;
    }
}
