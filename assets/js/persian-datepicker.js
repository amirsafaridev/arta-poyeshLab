/**
 * Persian Date Picker - Simple and Reusable
 * A lightweight Persian calendar picker without external dependencies
 */

(function($) {
    'use strict';

    // Persian month names
    const PERSIAN_MONTHS = [
        'فروردین', 'اردیبهشت', 'خرداد', 'تیر',
        'مرداد', 'شهریور', 'مهر', 'آبان',
        'آذر', 'دی', 'بهمن', 'اسفند'
    ];

    // Persian day names
    const PERSIAN_DAYS = [
        'شنبه', 'یکشنبه', 'دوشنبه', 'سه‌شنبه',
        'چهارشنبه', 'پنج‌شنبه', 'جمعه'
    ];

    // Persian day names short
    const PERSIAN_DAYS_SHORT = ['ش', 'ی', 'د', 'س', 'چ', 'پ', 'ج'];

    // Default options
    const DEFAULTS = {
        format: 'YYYY/MM/DD',
        placeholder: 'تاریخ را انتخاب کنید',
        autoClose: true,
        showToday: true,
        showClear: true,
        minDate: null,
        maxDate: null,
        onSelect: null,
        onShow: null,
        onHide: null
    };

    // Persian Date Picker Class
    class PersianDatePicker {
        constructor(element, options) {
            this.element = $(element);
            this.options = $.extend({}, DEFAULTS, options);
            this.isOpen = false;
            this.selectedDate = null;
            this.currentYear = 1400;
            this.currentMonth = 1;
            this.currentDay = 1;
            
            this.init();
        }

        init() {
            this.setupInput();
            this.bindEvents();
        }

        setupInput() {
            this.element.addClass('persian-datepicker-input');
            this.element.attr('readonly', true);
            this.element.attr('placeholder', this.options.placeholder);
        }

        bindEvents() {
            const self = this;
            
            this.element.on('click', function(e) {
                e.preventDefault();
                if (!self.isOpen) {
                    self.show();
                }
            });

            this.element.on('focus', function(e) {
                e.preventDefault();
                if (!self.isOpen) {
                    self.show();
                }
            });

            // Close on outside click
            $(document).on('click.persianDatepicker', function(e) {
                if (!$(e.target).closest('.persian-datepicker-container').length && 
                    !$(e.target).closest('.persian-datepicker-input').length) {
                    self.hide();
                }
            });

            // Close on escape key
            $(document).on('keydown.persianDatepicker', function(e) {
                if (e.keyCode === 27 && self.isOpen) {
                    self.hide();
                }
            });
        }

        show() {
            if (this.isOpen) return;

            this.createCalendar();
            this.positionCalendar();
            this.isOpen = true;

            if (this.options.onShow) {
                this.options.onShow.call(this);
            }
        }

        hide() {
            if (!this.isOpen) return;

            this.calendar.remove();
            this.isOpen = false;

            if (this.options.onHide) {
                this.options.onHide.call(this);
            }
        }

        createCalendar() {
            const self = this;
            
            // Create calendar container
            this.calendar = $('<div class="persian-datepicker-container"></div>');
            
            // Get current Persian date
            const now = this.getCurrentPersianDate();
            this.currentYear = now.year;
            this.currentMonth = now.month;
            this.currentDay = now.day;

            // Create calendar HTML
            const calendarHTML = `
                <div class="persian-datepicker-header">
                    <button type="button" class="persian-datepicker-prev">&lt;</button>
                    <div class="persian-datepicker-title">
                        <span class="persian-datepicker-year">${this.currentYear}</span>
                        <span class="persian-datepicker-month">${PERSIAN_MONTHS[this.currentMonth - 1]}</span>
                    </div>
                    <button type="button" class="persian-datepicker-next">&gt;</button>
                </div>
                <div class="persian-datepicker-body">
                    <div class="persian-datepicker-weekdays">
                        ${PERSIAN_DAYS_SHORT.map(day => `<div class="persian-datepicker-weekday">${day}</div>`).join('')}
                    </div>
                    <div class="persian-datepicker-days"></div>
                </div>
                <div class="persian-datepicker-footer">
                    ${this.options.showToday ? '<button type="button" class="persian-datepicker-today">امروز</button>' : ''}
                    ${this.options.showClear ? '<button type="button" class="persian-datepicker-clear">پاک کردن</button>' : ''}
                </div>
            `;

            this.calendar.html(calendarHTML);
            $('body').append(this.calendar);

            // Bind events
            this.calendar.find('.persian-datepicker-prev').on('click', function() {
                self.previousMonth();
            });

            this.calendar.find('.persian-datepicker-next').on('click', function() {
                self.nextMonth();
            });

            this.calendar.find('.persian-datepicker-today').on('click', function() {
                self.selectToday();
            });

            this.calendar.find('.persian-datepicker-clear').on('click', function() {
                self.clear();
            });

            this.renderDays();
        }

        renderDays() {
            const daysContainer = this.calendar.find('.persian-datepicker-days');
            daysContainer.empty();

            // Get first day of month and number of days
            const firstDay = this.getFirstDayOfMonth(this.currentYear, this.currentMonth);
            const daysInMonth = this.getDaysInMonth(this.currentYear, this.currentMonth);

            // Add empty cells for days before first day of month
            for (let i = 0; i < firstDay; i++) {
                daysContainer.append('<div class="persian-datepicker-day empty"></div>');
            }

            // Add days of month
            for (let day = 1; day <= daysInMonth; day++) {
                const dayElement = $(`<div class="persian-datepicker-day" data-day="${day}">${day}</div>`);
                
                // Check if this day is selected
                if (this.selectedDate && 
                    this.selectedDate.year === this.currentYear &&
                    this.selectedDate.month === this.currentMonth &&
                    this.selectedDate.day === day) {
                    dayElement.addClass('selected');
                }

                // Check if this day is today
                const today = this.getCurrentPersianDate();
                if (today.year === this.currentYear &&
                    today.month === this.currentMonth &&
                    today.day === day) {
                    dayElement.addClass('today');
                }

                // Check if this day is disabled
                if (this.isDayDisabled(this.currentYear, this.currentMonth, day)) {
                    dayElement.addClass('disabled');
                } else {
                    dayElement.on('click', () => {
                        this.selectDate(this.currentYear, this.currentMonth, day);
                    });
                }

                daysContainer.append(dayElement);
            }
        }

        selectDate(year, month, day) {
            this.selectedDate = { year, month, day };
            
            // Format date
            const formattedDate = this.formatDate(year, month, day);
            this.element.val(formattedDate);

            // Close calendar if autoClose is enabled
            if (this.options.autoClose) {
                this.hide();
            }

            // Trigger onSelect callback
            if (this.options.onSelect) {
                this.options.onSelect.call(this, formattedDate, { year, month, day });
            }

            // Re-render to show selected state
            this.renderDays();
        }

        selectToday() {
            const today = this.getCurrentPersianDate();
            this.currentYear = today.year;
            this.currentMonth = today.month;
            this.currentDay = today.day;
            this.selectDate(today.year, today.month, today.day);
        }

        clear() {
            this.selectedDate = null;
            this.element.val('');
            this.hide();
        }

        previousMonth() {
            this.currentMonth--;
            if (this.currentMonth < 1) {
                this.currentMonth = 12;
                this.currentYear--;
            }
            this.updateHeader();
            this.renderDays();
        }

        nextMonth() {
            this.currentMonth++;
            if (this.currentMonth > 12) {
                this.currentMonth = 1;
                this.currentYear++;
            }
            this.updateHeader();
            this.renderDays();
        }

        updateHeader() {
            const yearElement = this.calendar.find('.persian-datepicker-year');
            const monthElement = this.calendar.find('.persian-datepicker-month');
            
            if (yearElement.length) {
                yearElement.text(this.currentYear);
            }
            if (monthElement.length) {
                monthElement.text(PERSIAN_MONTHS[this.currentMonth - 1]);
            }
        }

        positionCalendar() {
            const inputOffset = this.element.offset();
            const inputHeight = this.element.outerHeight();
            
            this.calendar.css({
                position: 'absolute',
                top: inputOffset.top + inputHeight + 5,
                left: inputOffset.left,
                zIndex: 9999
            });
        }

        formatDate(year, month, day) {
            const yearStr = year.toString();
            const monthStr = month.toString().padStart(2, '0');
            const dayStr = day.toString().padStart(2, '0');
            return `${yearStr}/${monthStr}/${dayStr}`;
        }

        isDayDisabled(year, month, day) {
            // Check min date
            if (this.options.minDate) {
                const minDate = this.parseDate(this.options.minDate);
                if (minDate && this.compareDates(year, month, day, minDate.year, minDate.month, minDate.day) < 0) {
                    return true;
                }
            }

            // Check max date
            if (this.options.maxDate) {
                const maxDate = this.parseDate(this.options.maxDate);
                if (maxDate && this.compareDates(year, month, day, maxDate.year, maxDate.month, maxDate.day) > 0) {
                    return true;
                }
            }

            return false;
        }

        parseDate(dateStr) {
            if (!dateStr) return null;
            const parts = dateStr.split('/');
            if (parts.length !== 3) return null;
            return {
                year: parseInt(parts[0]),
                month: parseInt(parts[1]),
                day: parseInt(parts[2])
            };
        }

        compareDates(year1, month1, day1, year2, month2, day2) {
            if (year1 !== year2) return year1 - year2;
            if (month1 !== month2) return month1 - month2;
            return day1 - day2;
        }

        getCurrentPersianDate() {
            const now = new Date();
            
            // Use proper Persian calendar conversion
            const persianDate = this.gregorianToPersian(now.getFullYear(), now.getMonth() + 1, now.getDate());
            
            return persianDate;
        }

        gregorianToPersian(gy, gm, gd) {
            // Accurate Persian calendar conversion algorithm
            const g_days_in_month = [31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
            
            // Check if it's a leap year
            const isLeapYear = gy % 4 === 0 && (gy % 100 !== 0 || gy % 400 === 0);
            
            if (isLeapYear) {
                g_days_in_month[1] = 29;
            }
            
            // Calculate days since March 21 (Persian New Year)
            let days = 0;
            for (let i = 0; i < gm - 1; i++) {
                days += g_days_in_month[i];
            }
            days += gd;
            
            // Adjust for leap year
            if (isLeapYear) {
                if (gm > 2) days++;
            }
            
            // Convert to Persian
            let jy = gy - 621;
            if (days <= 79) {
                jy--;
                days += 286;
            } else {
                days -= 79;
            }
            
            // Calculate Persian month and day
            let jm, jd;
            if (days <= 186) {
                jm = Math.ceil(days / 31);
                jd = days - (jm - 1) * 31;
            } else {
                days -= 186;
                jm = 7 + Math.ceil(days / 30);
                jd = days - (jm - 7) * 30;
            }
            
            // Fix negative day
            if (jd <= 0) {
                jm--;
                if (jm < 1) {
                    jm = 12;
                    jy--;
                }
                if (jm <= 6) {
                    jd += 31;
                } else if (jm <= 11) {
                    jd += 30;
                } else {
                    jd += this.isLeapYear(jy) ? 30 : 29;
                }
            }
            
            return { year: jy, month: jm, day: jd };
        }

        getFirstDayOfMonth(year, month) {
            // Convert Persian date to Gregorian to get proper day of week
            const gregorian = this.persianToGregorian(year, month, 1);
            const date = new Date(gregorian.year, gregorian.month - 1, gregorian.day);
            return (date.getDay() + 1) % 7; // Convert to Persian week start (Saturday = 0)
        }

        persianToGregorian(jy, jm, jd) {
            // More accurate Persian to Gregorian conversion
            const j_days_in_month = [31, 31, 31, 31, 31, 31, 30, 30, 30, 30, 30, 29];
            const g_days_in_month = [31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
            
            let gy = jy + 621;
            let gm, gd;
            
            // Calculate days since Persian New Year
            let days = 0;
            for (let i = 0; i < jm - 1; i++) {
                days += j_days_in_month[i];
            }
            days += jd;
            
            // Convert to Gregorian
            if (days <= 79) {
                gy--;
                days += 286;
            } else {
                days -= 79;
            }
            
            // Calculate Gregorian month and day
            if (days <= 186) {
                gm = Math.ceil(days / 31);
                gd = days - (gm - 1) * 31;
            } else {
                days -= 186;
                gm = 7 + Math.ceil(days / 30);
                gd = days - (gm - 7) * 30;
            }
            
            // Adjust for leap year
            if (gy % 4 === 0 && (gy % 100 !== 0 || gy % 400 === 0)) {
                if (gm > 2) gd++;
            }
            
            return { year: gy, month: gm, day: gd };
        }

        getDaysInMonth(year, month) {
            // Persian calendar months have different number of days
            if (month <= 6) return 31;  // Farvardin to Shahrivar
            if (month <= 11) return 30; // Mehr to Bahman
            // Esfand (month 12) - check for leap year
            return this.isLeapYear(year) ? 30 : 29;
        }

        isLeapYear(year) {
            // Proper Persian leap year calculation
            const a = year - 474;
            const b = a % 128;
            const c = b % 33;
            return c === 1 || c === 5 || c === 9 || c === 13 || c === 17 || c === 22 || c === 26 || c === 30;
        }
    }

    // jQuery plugin
    $.fn.persianDatepicker = function(options) {
        return this.each(function() {
            if (!$(this).data('persianDatepicker')) {
                $(this).data('persianDatepicker', new PersianDatePicker(this, options));
            }
        });
    };

    // Static methods
    $.persianDatepicker = {
        version: '1.0.0',
        defaults: DEFAULTS
    };

})(jQuery);
