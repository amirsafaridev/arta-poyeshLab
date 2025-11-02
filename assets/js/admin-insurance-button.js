// assets/js/admin-insurance-button.js
(function(wp, $) {
    'use strict';

    // بررسی وجود jQuery و ووکامرس
    if (typeof $ === 'undefined') {
        console.warn('jQuery is required for admin-insurance-button.');
        return;
    }

    // تابع افزودن دکمه کنار دکمه add-line-item
    function addInsuranceButton() {
        // پیدا کردن دکمه add-line-item
        const addLineItemButton = $('.button.add-line-item');
        
        if (addLineItemButton.length === 0) {
            return;
        }

        // بررسی اینکه آیا دکمه قبلاً اضافه شده یا نه
        if (addLineItemButton.next('.button.add-insurance').length > 0) {
            return;
        }

        // ساخت دکمه و اضافه کردن بعد از دکمه add-line-item
        const insuranceButton = createInsuranceButton();
        insuranceButton.insertAfter(addLineItemButton);
    }

    // ساخت دکمه افزودن حق بیمه
    function createInsuranceButton() {
        const button = $('<button>', {
            type: 'button',
            class: 'button add-insurance',
            text: 'افزودن حق بیمه',
            style: 'margin-right: 5px;'
        });

        // رویداد کلیک دکمه
        button.on('click', function(e) {
            e.preventDefault();
            handleAddInsurance();
        });

        return button;
    }

    // مدیریت افزودن حق بیمه
    function handleAddInsurance() {
        // دریافت ID سفارش
        const orderId = getOrderId();
        if (!orderId) {
            alert('خطا: شناسه سفارش پیدا نشد.');
            return;
        }

        // دریافت مبلغ از کاربر
        const amount = prompt('مبلغ حق بیمه را وارد کنید (تومان):', '0');
        if (!amount || isNaN(amount) || parseFloat(amount) <= 0) {
            return;
        }

        // نمایش بارگذاری
        const button = $('.button.add-insurance');
        const originalText = button.text();
        button.prop('disabled', true).text('در حال افزودن...');

        // افزودن Fee از طریق AJAX
        const data = {
            action: 'apl_add_insurance_fee',
            order_id: orderId,
            amount: parseFloat(amount),
            nonce: typeof apl_ajax !== 'undefined' && apl_ajax.dashboard_nonce ? apl_ajax.dashboard_nonce : ''
        };

        $.ajax({
            url: typeof ajaxurl !== 'undefined' ? ajaxurl : (typeof apl_ajax !== 'undefined' ? apl_ajax.ajaxurl : '/wp-admin/admin-ajax.php'),
            type: 'POST',
            data: data,
            success: function(response) {
                if (response.success) {
                    alert('حق بیمه به مبلغ ' + amount + ' تومان به سفارش اضافه شد.');
                    // رفرش صفحه یا بارگذاری مجدد آیتم‌ها
                    if (typeof location !== 'undefined') {
                        location.reload();
                    }
                } else {
                    alert('خطا: ' + (response.data && response.data.message ? response.data.message : 'خطا در افزودن حق بیمه.'));
                }
                button.prop('disabled', false).text(originalText);
            },
            error: function(xhr, status, error) {
                console.error('Error adding insurance fee:', error);
                alert('خطا در افزودن حق بیمه. لطفاً دوباره تلاش کنید.');
                button.prop('disabled', false).text(originalText);
            }
        });
    }

    // دریافت ID سفارش
    function getOrderId() {
        // روش 1: از URL
        const urlMatch = window.location.href.match(/post[=\/](\d+)/) || window.location.href.match(/order[=\/](\d+)/);
        if (urlMatch && urlMatch[1]) {
            return parseInt(urlMatch[1]);
        }

        // روش 2: از input hidden
        const orderIdInput = $('#post_ID, input[name="order_id"], input[name="order-id"]');
        if (orderIdInput.length > 0) {
            return parseInt(orderIdInput.val());
        }

        // روش 3: از data attribute
        const orderElement = $('[data-order-id]');
        if (orderElement.length > 0) {
            return parseInt(orderElement.attr('data-order-id'));
        }

        return null;
    }

    // اجرای کد پس از بارگذاری صفحه
    $(document).ready(function() {
        // اضافه کردن دکمه
        addInsuranceButton();

        // اگر از AJAX برای بارگذاری بخش افزودن آیتم استفاده می‌شود، دکمه را دوباره اضافه کن
        $(document).on('DOMSubtreeModified', function() {
            if ($('.button.add-line-item').length > 0 && $('.button.add-insurance').length === 0) {
                setTimeout(addInsuranceButton, 500);
            }
        });

        // برای ووکامرس که از AJAX استفاده می‌کند
        $(document.body).on('wc_order_items_loaded wc_backbone_modal_loaded', function() {
            setTimeout(addInsuranceButton, 300);
        });

        // مشاهده تغییرات DOM برای وقتی که دکمه add-line-item اضافه می‌شود
        const observer = new MutationObserver(function(mutations) {
            if ($('.button.add-line-item').length > 0 && $('.button.add-insurance').length === 0) {
                setTimeout(addInsuranceButton, 200);
            }
        });

        // شروع مشاهده تغییرات در body
        if (document.body) {
            observer.observe(document.body, {
                childList: true,
                subtree: true
            });
        }
    });

})(window.wp, window.jQuery);
