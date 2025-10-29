# Persian Date Picker

یک تقویم جلالی ساده و قابل استفاده مجدد که بدون وابستگی خارجی کار می‌کند.

## ویژگی‌ها

- ✅ بدون وابستگی خارجی
- ✅ سازگار با WordPress
- ✅ طراحی ساده و تمیز
- ✅ پشتیبانی از RTL
- ✅ قابل استفاده مجدد در پروژه‌های مختلف
- ✅ پشتیبانی از min/max date
- ✅ دکمه‌های "امروز" و "پاک کردن"
- ✅ بسته شدن خودکار پس از انتخاب
- ✅ انیمیشن‌های نرم

## نحوه استفاده

### HTML
```html
<input type="text" class="persian-datepicker" placeholder="تاریخ را انتخاب کنید">
```

### JavaScript
```javascript
// راه‌اندازی ساده
$('.persian-datepicker').persianDatepicker();

// راه‌اندازی با تنظیمات
$('.persian-datepicker').persianDatepicker({
    format: 'YYYY/MM/DD',
    autoClose: true,
    showToday: true,
    showClear: true,
    minDate: '1400/01/01',
    maxDate: '1450/12/29',
    onSelect: function(formattedDate, dateObj) {
        console.log('تاریخ انتخاب شده:', formattedDate);
        console.log('شیء تاریخ:', dateObj);
    }
});
```

## تنظیمات

| نام | نوع | پیش‌فرض | توضیح |
|-----|-----|---------|-------|
| `format` | string | 'YYYY/MM/DD' | فرمت نمایش تاریخ |
| `placeholder` | string | 'تاریخ را انتخاب کنید' | متن placeholder |
| `autoClose` | boolean | true | بسته شدن خودکار پس از انتخاب |
| `showToday` | boolean | true | نمایش دکمه "امروز" |
| `showClear` | boolean | true | نمایش دکمه "پاک کردن" |
| `minDate` | string | null | حداقل تاریخ قابل انتخاب |
| `maxDate` | string | null | حداکثر تاریخ قابل انتخاب |
| `onSelect` | function | null | تابع فراخوانی پس از انتخاب |
| `onShow` | function | null | تابع فراخوانی هنگام نمایش |
| `onHide` | function | null | تابع فراخوانی هنگام مخفی شدن |

## مثال‌های استفاده

### استفاده در فرم
```javascript
$('#appointment-date').persianDatepicker({
    format: 'YYYY/MM/DD',
    onSelect: function(date) {
        // اعتبارسنجی تاریخ
        if (date < '1400/01/01') {
            alert('تاریخ نمی‌تواند قبل از 1400 باشد');
            return;
        }
        // انجام عملیات دیگر
        updateFormValidation();
    }
});
```

### استفاده با محدودیت تاریخ
```javascript
$('#birth-date').persianDatepicker({
    minDate: '1300/01/01',
    maxDate: '1400/12/29',
    onSelect: function(date) {
        console.log('تاریخ تولد انتخاب شده:', date);
    }
});
```

### استفاده در جدول
```javascript
// راه‌اندازی برای تمام فیلدهای تاریخ در جدول
$('table .date-input').persianDatepicker({
    format: 'YYYY/MM/DD',
    autoClose: true
});
```

## استایل‌ها

تقویم از CSS کلاس‌های زیر استفاده می‌کند:

- `.persian-datepicker-container` - کانتینر اصلی
- `.persian-datepicker-header` - هدر تقویم
- `.persian-datepicker-body` - بدنه تقویم
- `.persian-datepicker-footer` - فوتر تقویم
- `.persian-datepicker-day` - روزها
- `.persian-datepicker-day.today` - روز امروز
- `.persian-datepicker-day.selected` - روز انتخاب شده
- `.persian-datepicker-day.disabled` - روز غیرفعال

## سازگاری

- jQuery 1.7+
- WordPress 4.0+
- مرورگرهای مدرن (IE11+)

## فایل‌ها

- `persian-datepicker.js` - فایل JavaScript اصلی
- `persian-datepicker.css` - فایل CSS استایل‌ها
- `README-persian-datepicker.md` - این فایل راهنما

## پشتیبانی

برای گزارش باگ یا درخواست ویژگی جدید، لطفاً با تیم توسعه تماس بگیرید.
