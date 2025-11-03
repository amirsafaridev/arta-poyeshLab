
<!-- Success Modal for Order Submission -->
<div id="orderSuccessModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center hidden">
    <div class="modal-mobile bg-white rounded-2xl p-6 sm:p-8 max-w-md w-full mx-4 shadow-2xl">
        <div class="text-center mb-4 sm:mb-6">
            <div class="w-12 h-12 sm:w-16 sm:h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-3 sm:mb-4">
                <i class="fas fa-check text-green-600 text-xl sm:text-2xl"></i>
            </div>
            <h2 class="text-xl sm:text-2xl font-bold text-gray-800">ثبت سفارش موفق</h2>
            <p class="text-gray-600 mt-2 text-sm sm:text-base" id="orderSuccessMessage">سفارش شما با موفقیت ثبت شد</p>
        </div>
        
        <div class="flex justify-center">
            <button onclick="reloadPage()" class="w-full bg-blue-600 text-white py-3 rounded-lg hover:bg-blue-700 transition duration-200 font-medium">
                <i class="fas fa-redo ml-2"></i>
                بارگذاری مجدد صفحه
            </button>
        </div>
    </div>
</div>

 <!-- Success Modal -->
<div id="successModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center hidden">
        <div class="modal-mobile bg-white rounded-2xl p-6 sm:p-8 max-w-md w-full mx-4 shadow-2xl">
            <div class="text-center mb-4 sm:mb-6">
                <div class="w-12 h-12 sm:w-16 sm:h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-3 sm:mb-4">
                    <i class="fas fa-check text-green-600 text-xl sm:text-2xl"></i>
                </div>
                <h2 class="text-xl sm:text-2xl font-bold text-gray-800">رزرو با موفقیت انجام شد!</h2>
                <p class="text-gray-600 mt-2 text-sm sm:text-base">درخواست شما ثبت شده و به زودی با شما تماس خواهیم گرفت</p>
            </div>
            
            <div class="bg-gray-50 rounded-lg p-4 mb-6">
                <h3 class="font-semibold text-gray-900 mb-3">جزئیات رزرو شما:</h3>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-600">شماره رزرو:</span>
                        <span class="font-medium" id="bookingNumber">RES-1403-001</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">نوع آزمایش:</span>
                        <span class="font-medium">آزمایش #25360</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">تاریخ:</span>
                        <span class="font-medium">۲۵ آذر ۱۴۰۳</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">زمان:</span>
                        <span class="font-medium">۱۰:۰۰ صبح</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">نوع خدمات:</span>
                        <span class="font-medium">نمونه‌گیری در منزل</span>
                    </div>
                </div>
            </div>
            
            <div class="flex space-x-3 space-x-reverse">
                <button onclick="closeSuccessModal()" class="flex-1 bg-blue-600 text-white py-3 rounded-lg hover:bg-blue-700 transition duration-200 font-medium">
                    مشاهده سفارشات
                </button>
                <button onclick="closeSuccessModalToHome()" class="flex-1 bg-gray-600 text-white py-3 rounded-lg hover:bg-gray-700 transition duration-200 font-medium">
                    بازگشت به خانه
                </button>
            </div>
        </div>
    </div>
<!-- Main Dashboard -->
<div id="dashboard" >
        <!-- Header -->
        <?php
        // Get user data
        $current_user = wp_get_current_user();
        $user_first_name = get_user_meta($current_user->ID, 'first_name', true);
        $user_last_name = get_user_meta($current_user->ID, 'last_name', true);
        $user_display_name = $user_first_name && $user_last_name ? $user_first_name . ' ' . $user_last_name : $current_user->display_name;
        
        // Get user role
        $user_roles = $current_user->roles;
        $user_role = !empty($user_roles) ? $user_roles[0] : 'subscriber';
        $role_names = array(
            'administrator' => 'مدیر سیستم',
            'editor' => 'ویرایشگر',
            'author' => 'نویسنده',
            'contributor' => 'مشارکت‌کننده',
            'subscriber' => 'مشترک'
        );
        $user_role_display = isset($role_names[$user_role]) ? $role_names[$user_role] : 'کاربر عادی';
        
        // Get logo and title from settings
        $logo_id = get_option('apl_login_logo');
        $logo_url = $logo_id ? wp_get_attachment_url($logo_id) : '';
        $login_title = get_option('apl_login_title', 'آزمایشگاه پوش');
        
        // Get user mobile number for step 3
        $user_mobile = get_user_meta($current_user->ID, 'apl_mobile_number', true);
        // Get user first name and last name
        $user_first_name = get_user_meta($current_user->ID, 'first_name', true);
        $user_last_name = get_user_meta($current_user->ID, 'last_name', true);
        // Get user national ID
        $user_national_id = get_user_meta($current_user->ID, 'apl_national_id', true);
        // If not in meta, try from user object
        if (empty($user_first_name)) {
            $user_first_name = $current_user->first_name;
        }
        if (empty($user_last_name)) {
            $user_last_name = $current_user->last_name;
        }
        ?>
        
        <script>
            // Pass user data to JavaScript
            window.userMobileNumber = '<?php echo esc_js($user_mobile); ?>';
            window.userFirstName = '<?php echo esc_js($user_first_name); ?>';
            window.userLastName = '<?php echo esc_js($user_last_name); ?>';
            window.userNationalId = '<?php echo esc_js($user_national_id); ?>';
            window.orderSuccessMessage = '<?php echo esc_js(get_option('apl_order_success_message', 'سفارش شما با موفقیت ثبت شد و در انتظار بررسی قرار گرفت. شماره سفارش شما: {order_number}')); ?>';
        </script>
        
        <header class="dashboard-header bg-white shadow-sm border-b border-gray-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center h-16">
                    <div class="flex items-center">
                        <?php if ($logo_url): ?>
                            <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-lg flex items-center justify-center">
                                <img src="<?php echo esc_url($logo_url); ?>" alt="<?php echo esc_attr($login_title); ?>" class="w-full h-full object-contain rounded-lg">
                            </div>
                        <?php else: ?>
                            <div class="w-8 h-8 sm:w-10 sm:h-10 bg-blue-600 rounded-lg flex items-center justify-center">
                                <i class="fas fa-flask text-white text-sm sm:text-lg"></i>
                            </div>
                        <?php endif; ?>
                        <h1 class="mr-2 sm:mr-3 text-lg sm:text-xl font-bold text-gray-900"><?php echo esc_html($login_title); ?></h1>
                    </div>
                    <div class="flex items-center space-x-2 sm:space-x-4 space-x-reverse">
                        
                        <div class="hidden sm:flex items-center space-x-3 space-x-reverse">
                            <?php 
                            $current_profile_picture = get_user_meta($current_user->ID, 'apl_profile_picture', true);
                            if (!empty($current_profile_picture)): 
                            ?>
                                <div class="w-8 h-8 rounded-full overflow-hidden">
                                    <img src="<?php echo esc_url($current_profile_picture); ?>" 
                                         alt="عکس پروفایل" 
                                         class="w-full h-full object-cover">
                                </div>
                            <?php else: ?>
                                <div class="w-8 h-8 bg-gray-100 rounded-full flex items-center justify-center">
                                    <i class="fas fa-user text-gray-400 text-sm"></i>
                                </div>
                            <?php endif; ?>
                            <span class="text-gray-700 font-medium text-sm"><?php echo esc_html($user_display_name); ?></span>
                        </div>
                        <button onclick="logout()" class="hidden sm:block text-gray-400 hover:text-gray-600 p-1 sm:p-2">
                            <i class="fas fa-sign-out-alt text-sm sm:text-base" style="transform: rotate(180deg);"></i>
                        </button>
                        <!-- Mobile Hamburger Menu Button -->
                        <button onclick="toggleMobileMenu()" class="lg:hidden p-2 text-gray-600 hover:text-gray-900">
                            <i class="fas fa-bars text-xl"></i>
                        </button>
                    </div>
                </div>
            </div>
        </header>

        <!-- Mobile Sidebar Overlay -->
        <div id="mobileMenuOverlay" class="fixed inset-0 bg-black bg-opacity-50 z-40 lg:hidden hidden" onclick="closeMobileMenu()"></div>
        
        <!-- Mobile Sidebar -->
        <div id="mobileSidebar" class="fixed top-0 right-0 h-full w-80 bg-white shadow-xl z-50 transform translate-x-full transition-transform duration-300 ease-in-out lg:hidden">
            <div class="flex items-center justify-between p-4 border-b border-gray-200">
                <div class="flex items-center">
                    <?php if ($logo_url): ?>
                        <div class="w-10 h-10 rounded-lg flex items-center justify-center ml-3">
                            <img src="<?php echo esc_url($logo_url); ?>" alt="<?php echo esc_attr($login_title); ?>" class="w-full h-full object-contain rounded-lg">
                        </div>
                    <?php else: ?>
                        <div class="w-10 h-10 bg-blue-600 rounded-lg flex items-center justify-center ml-3">
                            <i class="fas fa-flask text-white text-lg"></i>
                        </div>
                    <?php endif; ?>
                    <h2 class="text-lg font-bold text-gray-900"><?php echo esc_html($login_title); ?></h2>
                </div>
                <button onclick="closeMobileMenu()" class="p-2 text-gray-600 hover:text-gray-900">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            
            <!-- User Profile Section -->
            <!-- <div class="p-4 border-b border-gray-200">
                <div class="flex items-center space-x-3 space-x-reverse">
                    <?php 
                    $current_profile_picture = get_user_meta($current_user->ID, 'apl_profile_picture', true);
                    if (!empty($current_profile_picture)): 
                    ?>
                        <div class="w-12 h-12 rounded-full overflow-hidden">
                            <img src="<?php echo esc_url($current_profile_picture); ?>" 
                                 alt="عکس پروفایل" 
                                 class="w-full h-full object-cover">
                        </div>
                    <?php else: ?>
                        <div class="w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center">
                            <i class="fas fa-user text-gray-400 text-2xl"></i>
                        </div>
                    <?php endif; ?>
                    <div>
                        <p class="font-medium text-gray-900"><?php echo esc_html($user_display_name); ?></p>
                        <p class="text-sm text-gray-600"><?php echo esc_html($user_role_display); ?></p>
                    </div>
                </div>
            </div> -->
            
            <!-- Navigation Menu -->
            <nav class="p-4 space-y-2">
                <button onclick="showSection('overview'); closeMobileMenu();" class="mobile-nav-item w-full flex items-center px-4 py-3 text-right rounded-lg bg-blue-50 text-blue-700 font-medium">
                    <i class="fas fa-home ml-3 text-lg"></i>داشبورد اصلی
                </button>
                <button onclick="showSection('book'); closeMobileMenu();" class="mobile-nav-item w-full flex items-center px-4 py-3 text-right rounded-lg text-gray-700 hover:bg-gray-100">
                    <i class="fas fa-calendar-plus ml-3 text-lg"></i>رزرو آزمایش
                </button>
                <button onclick="showSection('orders'); closeMobileMenu();" class="mobile-nav-item w-full flex items-center px-4 py-3 text-right rounded-lg text-gray-700 hover:bg-gray-100">
                    <i class="fas fa-list-ul ml-3 text-lg"></i>سفارشات من
                </button>
                <button onclick="showSection('results'); closeMobileMenu();" class="mobile-nav-item w-full flex items-center px-4 py-3 text-right rounded-lg text-gray-700 hover:bg-gray-100">
                    <i class="fas fa-file-medical ml-3 text-lg"></i>نتایج آزمایش
                </button>
                <button onclick="showSection('invoices'); closeMobileMenu();" class="mobile-nav-item w-full flex items-center px-4 py-3 text-right rounded-lg text-gray-700 hover:bg-gray-100">
                    <i class="fas fa-receipt ml-3 text-lg"></i>فاکتورها و پرداخت‌ها
                </button>
                <!-- <button onclick="showSection('payment'); closeMobileMenu();" class="mobile-nav-item w-full flex items-center px-4 py-3 text-right rounded-lg text-gray-700 hover:bg-gray-100">
                    <i class="fas fa-credit-card ml-3 text-lg"></i>پرداخت آنلاین
                </button> -->
                <button onclick="showSection('profile'); closeMobileMenu();" class="mobile-nav-item w-full flex items-center px-4 py-3 text-right rounded-lg text-gray-700 hover:bg-gray-100">
                    <i class="fas fa-user-cog ml-3 text-lg"></i>تنظیمات پروفایل
                </button>
            </nav>
            
            <!-- Logout Button -->
            <div class="absolute bottom-4 left-4 right-4">
                <button onclick="logout()" class="w-full flex items-center justify-center px-4 py-3 bg-red-600 text-white rounded-lg hover:bg-red-700 transition duration-200">
                    <i class="fas fa-sign-out-alt ml-2"></i>خروج از حساب
                </button>
            </div>
        </div>

        <div class="dashboard-content max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-8">
            <div class="lg:grid lg:grid-cols-4 lg:gap-8">
                <!-- Desktop Sidebar -->
                <div class="sidebar-tablet hidden lg:block lg:col-span-1">
                    <nav class="space-y-2">
                        <button onclick="showSection('overview')" class="nav-item w-full flex items-center px-4 py-3 text-right rounded-lg bg-blue-50 text-blue-700 font-medium">
                            <i class="fas fa-home ml-3"></i>داشبورد اصلی
                        </button>
                        <button onclick="showSection('book')" class="nav-item w-full flex items-center px-4 py-3 text-right rounded-lg text-gray-700 hover:bg-gray-100">
                            <i class="fas fa-calendar-plus ml-3"></i>رزرو آزمایش
                        </button>
                        <button onclick="showSection('orders')" class="nav-item w-full flex items-center px-4 py-3 text-right rounded-lg text-gray-700 hover:bg-gray-100">
                            <i class="fas fa-list-ul ml-3"></i>سفارشات من
                        </button>
                        <button onclick="showSection('results')" class="nav-item w-full flex items-center px-4 py-3 text-right rounded-lg text-gray-700 hover:bg-gray-100">
                            <i class="fas fa-file-medical ml-3"></i>نتایج آزمایش
                        </button>
                        <button onclick="showSection('invoices')" class="nav-item w-full flex items-center px-4 py-3 text-right rounded-lg text-gray-700 hover:bg-gray-100">
                            <i class="fas fa-receipt ml-3"></i>فاکتورها و پرداخت‌ها
                        </button>
                        <!-- <button onclick="showSection('payment')" class="nav-item w-full flex items-center px-4 py-3 text-right rounded-lg text-gray-700 hover:bg-gray-100">
                            <i class="fas fa-credit-card ml-3"></i>پرداخت آنلاین
                        </button> -->
                        <button onclick="showSection('profile')" class="nav-item w-full flex items-center px-4 py-3 text-right rounded-lg text-gray-700 hover:bg-gray-100">
                            <i class="fas fa-user-cog ml-3"></i>تنظیمات پروفایل
                        </button>
                    </nav>
                </div>

                <!-- Main Content -->
                <div class="lg:col-span-3 mt-4 lg:mt-0">
                    <!-- Dashboard Overview -->
                    <div id="overviewSection" class="section">
                        <div class="mb-8">
                            <h2 class="text-2xl font-bold text-gray-900 mb-2">خوش آمدید <?php echo esc_html($user_display_name); ?>!</h2>
                            <p class="text-gray-600">در اینجا آخرین وضعیت سلامت شما را مشاهده کنید.</p>
                        </div>

                        <!-- Notification Banner for Unseen Test Results -->
                        <div id="testResultsNotification" class="hidden bg-green-50 border border-green-200 rounded-lg p-4 mb-6">
                            <div class="flex items-center">
                                <i class="fas fa-check-circle text-green-600 ml-3"></i>
                                <div class="flex-1">
                                    <p id="testResultsNotificationText" class="text-green-800 font-medium"></p>
                                    <p class="text-green-700 text-sm">برای دانلود گزارش کامل اینجا کلیک کنید.</p>
                                </div>
                                <button onclick="showSection('results')" class="bg-green-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-green-700">
                                    مشاهده نتایج
                                </button>
                            </div>
                        </div>

                        <!-- Quick Actions -->
                        <div class="grid grid-cols-2 md:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 mb-6 sm:mb-8">
                            <button onclick="showSection('book')" class="quick-action-card card-hover bg-white p-4 sm:p-6 rounded-xl shadow-sm border border-gray-200 text-center">
                                <div class="w-10 h-10 sm:w-12 sm:h-12 bg-blue-100 rounded-lg flex items-center justify-center mx-auto mb-3 sm:mb-4">
                                    <i class="fas fa-calendar-plus text-blue-600 text-lg sm:text-xl"></i>
                                </div>
                                <h3 class="font-semibold text-gray-900 mb-1 sm:mb-2 text-sm sm:text-base">رزرو آزمایش</h3>
                                <p class="text-gray-600 text-xs sm:text-sm leading-tight">نوبت آزمایشگاه بعدی خود را رزرو کنید</p>
                            </button>

                            <button onclick="showSection('orders')" class="quick-action-card card-hover bg-white p-4 sm:p-6 rounded-xl shadow-sm border border-gray-200 text-center">
                                <div class="w-10 h-10 sm:w-12 sm:h-12 bg-green-100 rounded-lg flex items-center justify-center mx-auto mb-3 sm:mb-4">
                                    <i class="fas fa-list-ul text-green-600 text-lg sm:text-xl"></i>
                                </div>
                                <h3 class="font-semibold text-gray-900 mb-1 sm:mb-2 text-sm sm:text-base">سفارشات من</h3>
                                <p class="text-gray-600 text-xs sm:text-sm leading-tight">قرارهای ملاقات خود را پیگیری کنید</p>
                            </button>

                            <button onclick="showSection('results')" class="quick-action-card card-hover bg-white p-4 sm:p-6 rounded-xl shadow-sm border border-gray-200 text-center">
                                <div class="w-10 h-10 sm:w-12 sm:h-12 bg-purple-100 rounded-lg flex items-center justify-center mx-auto mb-3 sm:mb-4">
                                    <i class="fas fa-file-medical text-purple-600 text-lg sm:text-xl"></i>
                                </div>
                                <h3 class="font-semibold text-gray-900 mb-1 sm:mb-2 text-sm sm:text-base">نتایج آزمایش</h3>
                                <p class="text-gray-600 text-xs sm:text-sm leading-tight">گزارش‌های خود را دانلود کنید</p>
                            </button>

                            <button onclick="showSection('profile')" class="quick-action-card card-hover bg-white p-4 sm:p-6 rounded-xl shadow-sm border border-gray-200 text-center">
                                <div class="w-10 h-10 sm:w-12 sm:h-12 bg-orange-100 rounded-lg flex items-center justify-center mx-auto mb-3 sm:mb-4">
                                    <i class="fas fa-user-cog text-orange-600 text-lg sm:text-xl"></i>
                                </div>
                                <h3 class="font-semibold text-gray-900 mb-1 sm:mb-2 text-sm sm:text-base">پروفایل</h3>
                                <p class="text-gray-600 text-xs sm:text-sm leading-tight">حساب کاربری خود را مدیریت کنید</p>
                            </button>
                        </div>

                        <!-- Recent Activity -->
                        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">فعالیت‌های اخیر</h3>
                            <div id="recentActivitiesContainer" class="space-y-4">
                                <!-- Loading state -->
                                <div id="recentActivitiesLoading" class="text-center py-8">
                                    <i class="fas fa-spinner fa-spin text-gray-400 text-2xl mb-2"></i>
                                    <p class="text-gray-500 text-sm">در حال بارگذاری...</p>
                                </div>
                                <!-- Empty state -->
                                <div id="recentActivitiesEmpty" class="hidden text-center py-8">
                                    <i class="fas fa-inbox text-gray-300 text-3xl mb-3"></i>
                                    <p class="text-gray-500 text-sm">فعالیت اخیری وجود ندارد</p>
                                </div>
                                <!-- Activities will be rendered here -->
                            </div>
                        </div>
                    </div>

                    <!-- Book a Test Section -->
                    <div id="bookSection" class="section hidden">
                        <div class="mb-8">
                            <h2 class="text-2xl font-bold text-gray-900 mb-2">درخواست خدمات آزمایشگاه</h2>
                            <p class="text-gray-600">خدمات آزمایشگاهی مورد نیاز خود را به صورت مرحله به مرحله ثبت کنید.</p>
                        </div>

                        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 sm:p-6">
                            <form id="serviceRequestForm" class="form-tablet space-y-4 sm:space-y-6">
                                <!-- Step 1: Service Type Selection -->
                                <div class="space-y-6" id="step1">
                                    <!-- Progress Steps -->
                                    <div class="flex justify-between items-center">
                                        <div class="flex items-center">
                                            <div class="w-8 h-8 bg-blue-600 text-white rounded-full flex items-center justify-center">1</div>
                                            <div class="mr-3">
                                                <p class="font-medium text-gray-900">مرحله اول</p>
                                                <p class="text-sm text-gray-600">انتخاب نوع خدمات</p>
                                            </div>
                                        </div>
                                        <div class="hidden sm:flex items-center">
                                            <div class="w-3 h-3 bg-gray-300 rounded-full ml-2"></div>
                                            <div class="w-3 h-3 bg-gray-300 rounded-full ml-2"></div>
                                            <div class="w-3 h-3 bg-gray-300 rounded-full ml-2"></div>
                                            <div class="w-3 h-3 bg-gray-300 rounded-full"></div>
                                        </div>
                                    </div>

                                    <!-- Service Request Type -->
                                <div>
                                        <label class="block text-base font-medium text-gray-900 mb-4">نحوه درخواست خدمات</label>
                                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                            <div class="relative">
                                                <input type="radio" id="uploadPrescription" name="requestType" value="upload" class="peer hidden">
                                                <label for="uploadPrescription" class="block p-4 bg-white border-2 border-gray-200 rounded-xl cursor-pointer hover:border-blue-500 peer-checked:border-blue-600 peer-checked:bg-blue-50">
                                                    <div class="flex flex-col items-center text-center">
                                                        <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center mb-3">
                                                            <i class="fas fa-file-upload text-blue-600 text-xl"></i>
                                                        </div>
                                                        <h3 class="font-medium text-gray-900 mb-1">بارگذاری نسخه</h3>
                                                        <p class="text-sm text-gray-600">آپلود تصویر یا PDF نسخه</p>
                                                    </div>
                                                </label>
                                </div>

                                            <div class="relative">
                                                <input type="radio" id="ePrescription" name="requestType" value="electronic" class="peer hidden">
                                                <label for="ePrescription" class="block p-4 bg-white border-2 border-gray-200 rounded-xl cursor-pointer hover:border-blue-500 peer-checked:border-blue-600 peer-checked:bg-blue-50">
                                                    <div class="flex flex-col items-center text-center">
                                                        <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center mb-3">
                                                            <i class="fas fa-laptop-medical text-green-600 text-xl"></i>
                                    </div>
                                                        <h3 class="font-medium text-gray-900 mb-1">نسخه الکترونیک</h3>
                                                        <p class="text-sm text-gray-600">ثبت کد ملی و نام پزشک</p>
                                                    </div>
                                                </label>
                                </div>

                                            <div class="relative">
                                                <input type="radio" id="testPackages" name="requestType" value="packages" class="peer hidden">
                                                <label for="testPackages" class="block p-4 bg-white border-2 border-gray-200 rounded-xl cursor-pointer hover:border-blue-500 peer-checked:border-blue-600 peer-checked:bg-blue-50">
                                                    <div class="flex flex-col items-center text-center">
                                                        <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center mb-3">
                                                            <i class="fas fa-box-open text-purple-600 text-xl"></i>
                                                        </div>
                                                        <h3 class="font-medium text-gray-900 mb-1">بسته‌های آزمایش</h3>
                                                        <p class="text-sm text-gray-600">انتخاب از لیست بسته‌ها</p>
                                                    </div>
                                                </label>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Service Delivery Method -->
                                <div>
                                        <label class="block text-base font-medium text-gray-900 mb-4">نحوه ارائه خدمات</label>
                                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                            <div class="relative">
                                                <input type="radio" id="homeService" name="deliveryMethod" value="home_sampling" class="peer hidden">
                                                <label for="homeService" class="block p-4 bg-white border-2 border-gray-200 rounded-xl cursor-pointer hover:border-blue-500 peer-checked:border-blue-600 peer-checked:bg-blue-50">
                                                    <div class="flex items-center">
                                                        <div class="w-10 h-10 bg-orange-100 rounded-lg flex items-center justify-center ml-3">
                                                            <i class="fas fa-home text-orange-600"></i>
                                                        </div>
                                        <div>
                                                            <h3 class="font-medium text-gray-900 mb-1">نمونه‌گیری در منزل</h3>
                                                            <p class="text-sm text-gray-600">مراجعه کارشناس به محل شما</p>
                                                        </div>
                                                    </div>
                                                </label>
                                            </div>

                                            <div class="relative">
                                                <input type="radio" id="labService" name="deliveryMethod" value="lab_visit" class="peer hidden">
                                                <label for="labService" class="block p-4 bg-white border-2 border-gray-200 rounded-xl cursor-pointer hover:border-blue-500 peer-checked:border-blue-600 peer-checked:bg-blue-50">
                                                    <div class="flex items-center">
                                                        <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center ml-3">
                                                            <i class="fas fa-hospital-alt text-blue-600"></i>
                                        </div>
                                        <div>
                                                            <h3 class="font-medium text-gray-900 mb-1">مراجعه به آزمایشگاه</h3>
                                                            <p class="text-sm text-gray-600">مراجعه حضوری به شعب</p>
                                                        </div>
                                                    </div>
                                                </label>
                                            </div>

                                            <div class="relative">
                                                <input type="radio" id="sampleDelivery" name="deliveryMethod" value="sample_shipping" class="peer hidden">
                                                <label for="sampleDelivery" class="block p-4 bg-white border-2 border-gray-200 rounded-xl cursor-pointer hover:border-blue-500 peer-checked:border-blue-600 peer-checked:bg-blue-50">
                                                    <div class="flex items-center">
                                                        <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center ml-3">
                                                            <i class="fas fa-truck text-purple-600"></i>
                                        </div>
                                        <div>
                                                            <h3 class="font-medium text-gray-900 mb-1">ارسال نمونه</h3>
                                                            <p class="text-sm text-gray-600">ارسال نمونه به آزمایشگاه</p>
                                                        </div>
                                                    </div>
                                                </label>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Navigation Buttons -->
                                    <div class="flex justify-end pt-6">
                                        <button type="button" onclick="validateStep1()" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition duration-200 font-medium">
                                            مرحله بعد
                                            <i class="fas fa-arrow-left mr-2"></i>
                                        </button>
                                    </div>
                                </div>

                                <!-- Step 2: Dynamic Forms -->
                                <div class="space-y-6 hidden" id="step2">
                                    <!-- Progress Steps -->
                                    <div class="flex justify-between items-center">
                                        <div class="flex items-center">
                                            <div class="w-8 h-8 bg-blue-600 text-white rounded-full flex items-center justify-center">2</div>
                                            <div class="mr-3">
                                                <p class="font-medium text-gray-900">مرحله دوم</p>
                                                <p class="text-sm text-gray-600">اطلاعات درخواست</p>
                                            </div>
                                        </div>
                                        <div class="hidden sm:flex items-center">
                                            <div class="w-3 h-3 bg-blue-600 rounded-full ml-2"></div>
                                            <div class="w-3 h-3 bg-blue-600 rounded-full ml-2"></div>
                                            <div class="w-3 h-3 bg-gray-300 rounded-full ml-2"></div>
                                            <div class="w-3 h-3 bg-gray-300 rounded-full"></div>
                                        </div>
                                    </div>

                                    <!-- File Upload Form -->
                                    <div id="fileUploadForm" class="hidden">
                                        <div class="space-y-4">
                                            <div>
                                                <label class="block text-base font-medium text-gray-900 mb-4">بارگذاری نسخه</label>
                                                <div class="file-upload-mobile border-2 border-dashed border-gray-300 rounded-xl p-6 text-center hover:border-blue-400 transition-colors cursor-pointer">
                                                    <i class="fas fa-cloud-upload-alt text-gray-400 text-3xl mb-4"></i>
                                                    <p class="text-gray-600 mb-2">نسخه خود را اینجا بکشید یا کلیک کنید</p>
                                                    <p class="text-gray-500 text-sm">پشتیبانی از PDF، JPG، PNG (حداکثر ۵ مگابایت هر فایل)</p>
                                                    <input type="file" id="prescriptionFile" class="hidden" accept=".pdf,.jpg,.jpeg,.png" multiple>
                                                    <button type="button" class="mt-4 bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 text-base font-medium">
                                                        انتخاب فایل‌ها
                                                    </button>
                                                </div>
                                            </div>
                                            
                                            <div id="uploadedFiles" class="hidden">
                                                <h4 class="text-sm font-medium text-gray-900 mb-3">فایل‌های انتخاب شده:</h4>
                                                <div class="space-y-3">
                                                    <!-- Uploaded files will be listed here -->
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- E-Prescription Form -->
                                    <div id="ePrescriptionForm" class="hidden">
                                        <div class="space-y-4">
                                            <div>
                                                <label class="block text-base font-medium text-gray-900 mb-4">اطلاعات نسخه الکترونیک</label>
                                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                                    <div>
                                                        <label class="block text-sm font-medium text-gray-700 mb-2">کد ملی بیمار</label>
                                                        <input type="text" id="step2NationalId" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="کد ملی ۱۰ رقمی" maxlength="10" dir="ltr">
                                                    </div>
                                                    <div>
                                                        <label class="block text-sm font-medium text-gray-700 mb-2">نام پزشک</label>
                                                        <input type="text" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="نام پزشک تجویز کننده">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Test Packages Form -->
                                    <div id="testPackagesForm" class="hidden">
                                        <div class="space-y-4">
                                            <div>
                                                <label class="block text-base font-medium text-gray-900 mb-4">بسته‌های آزمایش</label>
                                                <div class="space-y-4">

                                                <?php foreach (apl_get_lab_package_products() as $package) : ?>
                                                    <!-- Package Card -->
                                                    <div id="package-<?= $package->id ?>" data-package-name="<?= $package->title ?>" data-package-price="<?= $package->prices['regular']['raw'] ?>"  data-package-service-delivery="<?= $package->service_delivery['value'] ?>" class="package-card  bg-white border border-gray-200 rounded-xl p-4 cursor-pointer transition-all duration-200 hover:shadow-md" onclick="togglePackage('<?= $package->id ?>')" style="display: none;">
                                                        <div class="flex flex-col sm:flex-row gap-4">
                                                            <div class="w-full sm:w-32 h-32 bg-gradient-to-br from-pink-100 to-purple-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                                                <?php if ($package->thumbnail_url) : ?>
                                                                <img src="<?= $package->thumbnail_url ?>" alt="<?= $package->title ?>" class="w-full h-full object-cover rounded-lg">
                                                                <?php else : ?>
                                                                    <i class="fas fa-female text-pink-600 text-3xl"></i>
                                                                <?php endif; ?>
                                                            </div>
                                                            <div class="flex-1">
                                                                <h3 class="text-lg font-semibold text-gray-900 mb-2"><?= $package->title ?></h3>
                                                                <p class="text-sm text-gray-600 mb-3"><?= $package->short_description ?></p>
                                                                <div class="mb-4">
                                                                    <div class="text-xs text-gray-500 mb-2">
                                                                        <strong>آزمایش‌ها شامل:</strong>
                                                                    </div>
                                                                    <div class="text-xs text-gray-600">
                                                                        <?= implode(' , ', array_column($package->package_items, 'title')) ?>
                                                                    </div>
                                                                </div>
                                                        <div class="flex items-center justify-between">
                                                                    <span class="text-lg font-bold text-blue-600"><?= $package->prices['regular']['formatted'] ?></span>
                                                                    <div class="flex items-center gap-3">
                                                                        <button type="button" class="package-select-btn bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-blue-700 transition duration-200" onclick="event.stopPropagation(); togglePackage('<?= $package->id ?>')">
                                                                            <span class="select-text">انتخاب</span>
                                                                            <span class="deselect-text hidden">لغو انتخاب</span>
                                                            </button>
                                                                        <div class="package-selected hidden">
                                                                            <i class="fas fa-check-circle text-green-600 text-xl"></i>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                <?php endforeach; ?>

                                                </div>
                                            </div>

                                            <!-- Selected Packages -->
                                            <div id="selectedPackages" class="hidden">
                                                <h4 class="text-base font-medium text-gray-900 mb-3">بسته‌های انتخاب شده:</h4>
                                                <div class="space-y-3" id="selectedPackagesList">
                                                    <!-- Selected packages will be listed here -->
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Navigation Buttons -->
                                    <div class="flex justify-between pt-6">
                                        <button type="button" onclick="goToStep(1)" class="bg-gray-600 text-white px-6 py-3 rounded-lg hover:bg-gray-700 transition duration-200 font-medium">
                                            <i class="fas fa-arrow-right ml-2"></i>
                                            مرحله قبل
                                        </button>
                                        <button type="button" onclick="validateStep2()" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition duration-200 font-medium">
                                            مرحله بعد
                                            <i class="fas fa-arrow-left mr-2"></i>
                                        </button>
                                    </div>
                                </div>

                                <!-- Step 3: Patient Information -->
                                <div class="space-y-6 hidden" id="step3">
                                    <!-- Progress Steps -->
                                    <div class="flex justify-between items-center">
                                        <div class="flex items-center">
                                            <div class="w-8 h-8 bg-blue-600 text-white rounded-full flex items-center justify-center">3</div>
                                            <div class="mr-3">
                                                <p class="font-medium text-gray-900">مرحله سوم</p>
                                                <p class="text-sm text-gray-600">اطلاعات بیمار</p>
                                            </div>
                                        </div>
                                        <div class="hidden sm:flex items-center">
                                            <div class="w-3 h-3 bg-blue-600 rounded-full ml-2"></div>
                                            <div class="w-3 h-3 bg-blue-600 rounded-full ml-2"></div>
                                            <div class="w-3 h-3 bg-blue-600 rounded-full ml-2"></div>
                                            <div class="w-3 h-3 bg-gray-300 rounded-full"></div>
                                        </div>
                                    </div>

                                    <!-- Patient Information Form -->
                                    <div class="space-y-6">
                                        <div>
                                            <label class="block text-base font-medium text-gray-900 mb-4">اطلاعات شخصی بیمار</label>
                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                                <div>
                                                    <label class="block text-sm font-medium text-gray-700 mb-2">نام</label>
                                                    <input type="text" id="patientFirstName" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="نام بیمار را وارد کنید">
                                                </div>
                                                <div>
                                                    <label class="block text-sm font-medium text-gray-700 mb-2">نام خانوادگی</label>
                                                    <input type="text" id="patientLastName" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="نام خانوادگی بیمار را وارد کنید">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">کد ملی</label>
                                                    <input type="text" id="patientNationalId" inputmode="numeric" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="کد ملی ۱۰ رقمی" maxlength="10" dir="ltr">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">شماره موبایل</label>
                                                    <input type="tel" id="patientMobile" inputmode="numeric" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="09123456789" dir="ltr">
                                        </div>
                                    </div>
                                </div>

                                        <!-- Service Location (shown only for home sampling) -->
                                        <div id="serviceLocationSection" class="hidden">
                                            <label class="block text-base font-medium text-gray-900 mb-4">آدرس محل ارائه خدمت</label>
                                            
                                            <!-- City Selection -->
                                            <div class="mb-4">
                                                <label class="block text-sm font-medium text-gray-700 mb-2">شهر</label>
                                                <select id="citySelect" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                                    <option value="">شهر خود را انتخاب کنید</option>
                                                    <option value="ardabil">اردبیل</option>
                                                    <option value="namin">نمین</option>
                                                    <option value="astara">آستارا</option>
                                                    <option value="anbaran">عنبران</option>
                                                    <option value="abibiglu">ابی بیگلو</option>
                                                </select>
                                            </div>

                                            <!-- Address Details -->
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-2">آدرس کامل</label>
                                                <textarea id="addressTextarea" rows="3" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="آدرس کامل محل نمونه‌گیری را وارد کنید"></textarea>
                                                <p class="text-sm text-gray-600 mt-2">
                                                    <i class="fas fa-info-circle ml-1"></i>
                                                    لطفاً آدرس دقیق شامل نام خیابان، پلاک و واحد را وارد کنید
                                                </p>
                                            </div>
                                        </div>

                                        <!-- Appointment Schedule (shown for all delivery methods) -->
                                        <div id="labScheduleSection" class="hidden">
                                            <label class="block text-base font-medium text-gray-900 mb-4">زمان مراجعه / نمونه‌گیری</label>
                                            
                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                                <!-- Date Selection -->
                                                <div>
                                                    <label class="block text-sm font-medium text-gray-700 mb-2">تاریخ</label>
                                                    <input type="text" id="labDatePicker" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="تاریخ را انتخاب کنید" readonly>
                                                </div>

                                                <!-- Time Selection -->
                                                <div>
                                                    <label class="block text-sm font-medium text-gray-700 mb-2">ساعت</label>
                                                    <select id="labTimeSelect" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" disabled>
                                                        <option value="">ابتدا تاریخ را انتخاب کنید</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Laboratory Address (shown only for sample delivery) -->
                                        <div id="labAddressSection" class="hidden">
                                            <label class="block text-base font-medium text-gray-900 mb-4">آدرس آزمایشگاه</label>
                                            
                                            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
                                                <div class="flex items-start">
                                                    <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center ml-3 flex-shrink-0">
                                                        <i class="fas fa-info-circle text-blue-600"></i>
                                                    </div>
                                                    <div>
                                                        <h4 class="font-medium text-blue-900 mb-2">لطفاً نمونه خود را به آدرس زیر ارسال کنید:</h4>
                                                        <div class="space-y-2 text-sm text-blue-800">
                                                            <div class="flex items-center">
                                                                <i class="fas fa-map-marker-alt ml-2"></i>
                                                                <span>آدرس: اردبیل، خیابان دانشگاه، پلاک ۱۲۳، آزمایشگاه پوش</span>
                                                            </div>
                                                            <div class="flex items-center">
                                                                <i class="fas fa-mail-bulk ml-2"></i>
                                                                <span>کد پستی: ۵۶۱۳۸-۳۴۱۳۵</span>
                                                            </div>
                                                            <div class="flex items-center">
                                                                <i class="fas fa-phone ml-2"></i>
                                                                <span>تلفن: ۰۴۵-۳۳۵۵۶۶۷۷</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                                                <div class="flex items-start">
                                                    <div class="w-8 h-8 bg-yellow-100 rounded-lg flex items-center justify-center ml-3 flex-shrink-0">
                                                        <i class="fas fa-exclamation-triangle text-yellow-600"></i>
                                                    </div>
                                                    <div>
                                                        <h4 class="font-medium text-yellow-900 mb-2">نکات مهم:</h4>
                                                        <ul class="text-sm text-yellow-800 space-y-1">
                                                            <li>• نمونه را در بسته‌بندی مناسب و محکم ارسال کنید</li>
                                                            <li>• نام و نام خانوادگی بیمار را روی نمونه بنویسید</li>
                                                            <li>• در صورت امکان، نمونه را در ساعات کاری (۸ صبح تا ۶ عصر) ارسال کنید</li>
                                                            <li>• پس از ارسال، شماره پیگیری پست را یادداشت کنید</li>
                                                        </ul>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Navigation Buttons -->
                                        <div class="flex justify-between pt-6">
                                            <button type="button" onclick="goToStep(2)" class="bg-gray-600 text-white px-6 py-3 rounded-lg hover:bg-gray-700 transition duration-200 font-medium">
                                                <i class="fas fa-arrow-right ml-2"></i>
                                                مرحله قبل
                                            </button>
                                            <button type="button" onclick="validateStep3()" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition duration-200 font-medium">
                                                مرحله بعد
                                                <i class="fas fa-arrow-left mr-2"></i>
                                            </button>
                                    </div>
                                    </div>
                                </div>

                                <!-- Step 4: Insurance Coverage -->
                                <div class="space-y-6 hidden" id="step4">
                                    <!-- Progress Steps -->
                                    <div class="flex justify-between items-center">
                                        <div class="flex items-center">
                                            <div class="w-8 h-8 bg-blue-600 text-white rounded-full flex items-center justify-center">4</div>
                                            <div class="mr-3">
                                                <p class="font-medium text-gray-900">مرحله چهارم</p>
                                                <p class="text-sm text-gray-600">پوشش بیمه</p>
                                            </div>
                                        </div>
                                        <div class="hidden sm:flex items-center">
                                            <div class="w-3 h-3 bg-blue-600 rounded-full ml-2"></div>
                                            <div class="w-3 h-3 bg-blue-600 rounded-full ml-2"></div>
                                            <div class="w-3 h-3 bg-blue-600 rounded-full ml-2"></div>
                                            <div class="w-3 h-3 bg-blue-600 rounded-full"></div>
                                        </div>
                                    </div>

                                    <!-- Insurance Information Form -->
                                    <div class="space-y-6">
                                        <!-- Basic Insurance -->
                                    <div>
                                            <label class="block text-base font-medium text-gray-900 mb-4">بیمه پایه</label>
                                        <select class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                                <option value="">بیمه ندارم</option>
                                                <option value="tamin">تأمین اجتماعی</option>
                                                <option value="salamat">سلامت ایران</option>
                                                <option value="mosalah">نیروهای مسلح</option>
                                                <option value="other">سایر</option>
                                        </select>
                                </div>

                                        <!-- Supplementary Insurance -->
                                <div>
                                            <label class="block text-base font-medium text-gray-900 mb-4">بیمه تکمیلی</label>
                                    <select class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                                <option value="">بیمه تکمیلی ندارم</option>
                                                <option value="day">بیمه دی</option>
                                                <option value="alborz">بیمه البرز</option>
                                                <option value="hafez">بیمه حافظ</option>
                                                <option value="hekmat">بیمه حکمت</option>
                                        <option value="dana">بیمه دانا</option>
                                        <option value="asia">بیمه آسیا</option>
                                                <option value="iran">بیمه ایران</option>
                                        <option value="parsian">بیمه پارسیان</option>
                                        <option value="pasargad">بیمه پاسارگاد</option>
                                                <option value="moalem">بیمه معلم</option>
                                        <option value="saman">بیمه سامان</option>
                                                <option value="sina">بیمه سینا</option>
                                        <option value="karafarin">بیمه کارآفرین</option>
                                        <option value="novin">بیمه نوین</option>
                                                <option value="mellat">بیمه ملت</option>
                                    </select>
                                </div>

                                        <!-- Insurance Tracking Code -->
                                <div>
                                            <label class="block text-base font-medium text-gray-900 mb-4">کد رهگیری بیمه</label>
                                            <input type="text" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="کد رهگیری بیمه را وارد کنید" dir="ltr">
                                            <p class="text-sm text-gray-600 mt-2">
                                                <i class="fas fa-info-circle ml-1"></i>
                                                کد رهگیری بیمه را از سامانه بیمه دریافت کنید
                                            </p>
                                        </div>

                                        <!-- Navigation Buttons -->
                                        <div class="flex justify-between pt-6">
                                            <button type="button" onclick="goToStep(3)" class="bg-gray-600 text-white px-6 py-3 rounded-lg hover:bg-gray-700 transition duration-200 font-medium">
                                                <i class="fas fa-arrow-right ml-2"></i>
                                                مرحله قبل
                                            </button>
                                            <button type="button" onclick="goToStep(5)" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition duration-200 font-medium">
                                                مرحله بعد
                                                <i class="fas fa-arrow-left mr-2"></i>
                                        </button>
                                        </div>
                                    </div>
                                    </div>
                                    
                                <!-- Step 5: Payment Information -->
                                <div class="space-y-6 hidden" id="step5">
                                    <!-- Progress Steps -->
                                    <div class="flex justify-between items-center">
                                        <div class="flex items-center">
                                            <div class="w-8 h-8 bg-blue-600 text-white rounded-full flex items-center justify-center">5</div>
                                            <div class="mr-3">
                                                <p class="font-medium text-gray-900">مرحله پنجم</p>
                                                <p class="text-sm text-gray-600">اطلاعات پرداخت</p>
                                        </div>
                                    </div>
                                        <div class="hidden sm:flex items-center">
                                            <div class="w-3 h-3 bg-blue-600 rounded-full ml-2"></div>
                                            <div class="w-3 h-3 bg-blue-600 rounded-full ml-2"></div>
                                            <div class="w-3 h-3 bg-blue-600 rounded-full ml-2"></div>
                                            <div class="w-3 h-3 bg-blue-600 rounded-full"></div>
                                    </div>
                                </div>

                                    <!-- Payment Information Form -->
                                    <div class="space-y-6">
                                        <!-- Order Summary -->
                                        <div id="orderSummarySection">
                                            <label class="block text-base font-medium text-gray-900 mb-4">خلاصه سفارش</label>
                                            <div class="bg-gray-50 rounded-xl p-6">
                                                <div class="space-y-6">
                                                    <!-- Service Type Information -->
                                                    <div>
                                                        <h4 class="text-sm font-semibold text-gray-900 mb-3">نوع درخواست</h4>
                                                        <div class="bg-white rounded-lg p-4">
                                                            <div class="flex items-center justify-between">
                                                                <div>
                                                                    <p class="font-medium text-gray-900" id="summaryRequestType">بارگذاری نسخه</p>
                                                                    <p class="text-sm text-gray-600" id="summaryDeliveryMethod">نمونه‌گیری در منزل</p>
                                                                </div>
                                                                <i class="fas fa-clipboard-list text-blue-600 text-xl"></i>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- Patient Information -->
                                                    <div>
                                                        <h4 class="text-sm font-semibold text-gray-900 mb-3">اطلاعات بیمار</h4>
                                                        <div class="bg-white rounded-lg p-4">
                                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                                                <div>
                                                                    <p class="text-sm text-gray-600">نام و نام خانوادگی</p>
                                                                    <p class="font-medium text-gray-900" id="summaryPatientName">سارا احمدی</p>
                                                                </div>
                                                                <div>
                                                                    <p class="text-sm text-gray-600">کد ملی</p>
                                                                    <p class="font-medium text-gray-900" id="summaryNationalId">۱۲۳۴۵۶۷۸۹۰</p>
                                                                </div>
                                                                <div>
                                                                    <p class="text-sm text-gray-600">شماره موبایل</p>
                                                                    <p class="font-medium text-gray-900" id="summaryMobile">۰۹۱۲۳۴۵۶۷۸۹</p>
                                                                </div>
                                                                <div id="summaryAddressContainer" class="md:col-span-2">
                                                                    <p class="text-sm text-gray-600">آدرس</p>
                                                                    <p class="font-medium text-gray-900" id="summaryAddress">تهران، خیابان ولیعصر، پلاک ۱۲۳</p>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- Appointment Information -->
                                                    <div>
                                                        <h4 class="text-sm font-semibold text-gray-900 mb-3">اطلاعات نوبت</h4>
                                                        <div class="bg-white rounded-lg p-4">
                                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                                                <div>
                                                                    <p class="text-sm text-gray-600">نحوه ارائه خدمات</p>
                                                                    <p class="font-medium text-gray-900" id="summaryDeliveryMethodDetail">نمونه‌گیری در منزل</p>
                                                                </div>
                                                                <div>
                                                                    <p class="text-sm text-gray-600">تاریخ</p>
                                                                    <p class="font-medium text-gray-900" id="summaryAppointmentDate">-</p>
                                                                </div>
                                                                <div>
                                                                    <p class="text-sm text-gray-600">ساعت</p>
                                                                    <p class="font-medium text-gray-900" id="summaryAppointmentTime">-</p>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- Insurance Information -->
                                                    <div>
                                                        <h4 class="text-sm font-semibold text-gray-900 mb-3">اطلاعات بیمه</h4>
                                                        <div class="bg-white rounded-lg p-4">
                                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                                                <div>
                                                                    <p class="text-sm text-gray-600">بیمه پایه</p>
                                                                    <p class="font-medium text-gray-900" id="summaryBasicInsurance">تأمین اجتماعی</p>
                                                                </div>
                                                                <div>
                                                                    <p class="text-sm text-gray-600">بیمه تکمیلی</p>
                                                                    <p class="font-medium text-gray-900" id="summarySupplementaryInsurance">بیمه دی</p>
                                                                </div>
                                                                <div class="md:col-span-2">
                                                                    <p class="text-sm text-gray-600">کد رهگیری</p>
                                                                    <p class="font-medium text-gray-900" id="summaryTrackingCode">TRK-123456789</p>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- Selected Services/Packages -->
                                                    <div id="selectedServicesSummary">
                                                        <h4 class="text-sm font-semibold text-gray-900 mb-3">خدمات انتخاب شده</h4>
                                                        <div class="bg-white rounded-lg p-4">
                                                            <div id="servicesList">
                                                                <!-- Services will be populated dynamically -->
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Discount Code -->
                                        <div>
                                            <label class="block text-base font-medium text-gray-900 mb-4">کد تخفیف</label>
                                            <div class="flex space-x-3 space-x-reverse">
                                                <input type="text" id="step5DiscountCode" class="flex-1 px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="کد تخفیف خود را وارد کنید">
                                                <button type="button" onclick="applyStep5Discount()" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition duration-200 font-medium whitespace-nowrap">
                                                    اعمال کد
                                                </button>
                                            </div>
                                            <input type="hidden" id="appliedDiscountCode" name="applied_discount_code" value="">
                                            <div id="step5DiscountMessage" class="mt-3 text-sm hidden"></div>
                                        </div>

                                        <!-- Terms and Conditions -->
                                        <div>
                                            <label class="flex items-start">
                                                <input type="checkbox" id="acceptTermsStep5" class="mt-1 h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                                <span class="mr-2 text-sm text-gray-700">
                                                    با <a href="#" class="text-blue-600 hover:underline">قوانین و مقررات</a> و 
                                                    <a href="#" class="text-blue-600 hover:underline">سیاست حفظ حریم خصوصی</a> موافقم
                                                </span>
                                            </label>
                                        </div>

                                        <!-- Error/Success Messages -->
                                        <div id="step5MessageContainer" class="hidden">
                                            <div id="step5ErrorMessage" class="bg-red-50 border border-red-200 rounded-lg p-4 hidden">
                                                <div class="flex items-start">
                                                    <div class="flex-shrink-0">
                                                        <i class="fas fa-exclamation-circle text-red-600"></i>
                                                    </div>
                                                    <div class="mr-3">
                                                        <h3 class="text-sm font-medium text-red-800">خطا</h3>
                                                        <p class="mt-1 text-sm text-red-700" id="step5ErrorMessageText"></p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Navigation Buttons -->
                                        <div class="flex justify-between pt-6">
                                            <button type="button" onclick="goToStep(4)" class="bg-gray-600 text-white px-6 py-3 rounded-lg hover:bg-gray-700 transition duration-200 font-medium">
                                                <i class="fas fa-arrow-right ml-2"></i>
                                                مرحله قبل
                                            </button>
                                            <button type="submit" id="finalSubmitBtn" class="bg-green-600 text-white px-8 py-3 rounded-lg hover:bg-green-700 transition duration-200 font-medium">
                                                <i class="fas fa-check ml-2"></i>
                                                ثبت نهایی 
                                            </button>
                                        </div>

                                        <!-- Security Badges -->
                                        <div class="flex items-center justify-center space-x-6 space-x-reverse pt-6">
                                            <div class="flex items-center text-gray-500">
                                                <i class="fas fa-shield-alt ml-2"></i>
                                                <span class="text-sm">پرداخت امن</span>
                                            </div>
                                            <div class="flex items-center text-gray-500">
                                                <i class="fas fa-lock ml-2"></i>
                                                <span class="text-sm">رمزگذاری SSL</span>
                                            </div>
                                            <div class="flex items-center text-gray-500">
                                                <i class="fas fa-certificate ml-2"></i>
                                                <span class="text-sm">تایید شده</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Orders Section -->
                    <div id="ordersSection" class="section hidden">
                        <div class="mb-8">
                            <h2 class="text-2xl font-bold text-gray-900 mb-2">سفارشات و رزروهای من</h2>
                            <p class="text-gray-600">قرارهای آزمایشگاهی خود را پیگیری و مدیریت کنید.</p>
                        </div>

                        <!-- Loading State -->
                        <div id="ordersLoading" class="flex justify-center items-center py-12 hidden">
                            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                            <span class="mr-3 text-gray-600">در حال بارگذاری...</span>
                                </div>
                                
                        <!-- Empty State -->
                        <div id="ordersEmpty" class="hidden text-center py-12">
                            <div class="w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-6">
                                <i class="fas fa-clipboard-list text-gray-400 text-3xl"></i>
                                                </div>
                            <h3 class="text-xl font-semibold text-gray-900 mb-2">هنوز هیچ سفارشی ندارید</h3>
                            <p class="text-gray-600 mb-6">پس از ثبت سفارش، سفارشات شما در اینجا نمایش داده خواهد شد.</p>
                            <button onclick="showSection('book')" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition duration-200 font-medium">
                                <i class="fas fa-plus ml-2"></i>رزرو آزمایش جدید
                                        </button>
                                </div>
                                
                        <!-- Orders Container -->
                        <div id="ordersContainer" class="space-y-4 hidden">
                            <!-- Dynamic order cards will be inserted here via JavaScript -->
                                            </div>
                                        </div>
                                        
                    <!-- Results Section -->
                    <div id="resultsSection" class="section hidden">
                        <div class="mb-8">
                            <h2 class="text-2xl font-bold text-gray-900 mb-2">نتایج آزمایش</h2>
                            <p class="text-gray-600">نتایج آزمایش‌های آزمایشگاهی خود را دانلود و مشاهده کنید.</p>
                        </div>

                        <!-- Loading State -->
                        <div id="testResultsLoading" class="flex justify-center items-center py-12">
                            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                            <span class="mr-3 text-gray-600">در حال بارگذاری...</span>
                        </div>

                        <!-- Empty State -->
                        <div id="testResultsEmpty" class="hidden text-center py-12">
                            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-8">
                                <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                    <i class="fas fa-file-medical-alt text-gray-400 text-2xl"></i>
                                </div>
                                <h3 class="text-lg font-semibold text-gray-900 mb-2">نتیجه آزمایشی یافت نشد</h3>
                                <p class="text-gray-600">هنوز نتیجه آزمایشی برای شما منتشر نشده است.</p>
                            </div>
                        </div>

                        <!-- Test Results Container -->
                        <div id="testResultsContainer" class="hidden space-y-4">
                            <!-- Dynamic test result cards will be inserted here via JavaScript -->
                        </div>
                    </div>

                    <!-- Invoices Section -->
                    <div id="invoicesSection" class="section hidden">
                        <div class="mb-8">
                            <h2 class="text-2xl font-bold text-gray-900 mb-2">فاکتورها و پرداخت‌ها</h2>
                            <p class="text-gray-600">صورتحساب و تاریخچه پرداخت‌های خود را مدیریت کنید.</p>
                        </div>

                        <!-- Loading State -->
                        <div id="invoicesLoading" class="flex justify-center items-center py-12">
                            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                            <span class="mr-3 text-gray-600">در حال بارگذاری...</span>
                            </div>

                        <!-- Empty State -->
                        <div id="invoicesEmpty" class="hidden text-center py-12">
                            <div class="w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-6">
                                <i class="fas fa-receipt text-gray-400 text-3xl"></i>
                                        </div>
                            <h3 class="text-xl font-semibold text-gray-900 mb-2">هنوز هیچ فاکتوری ندارید</h3>
                            <p class="text-gray-600 mb-6">پس از ثبت سفارش، فاکتورهای شما در اینجا نمایش داده خواهد شد.</p>
                            <button onclick="showSection('book')" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition duration-200 font-medium">
                                <i class="fas fa-plus ml-2"></i>رزرو آزمایش جدید
                                        </button>
                            </div>

                        <!-- Orders Container -->
                        <div id="invoicesContainer" class="space-y-4 hidden">
                            <!-- Dynamic invoice cards will be inserted here -->
                        </div>
                    </div>

                    <!-- Payment Section -->
                    <div id="paymentSection" class="section hidden">
                        <div class="mb-8">
                            <h2 class="text-2xl font-bold text-gray-900 mb-2">ثبت درخواست</h2>
                            <p class="text-gray-600">درخواست آزمایش خود را نهایی کنید.</p>
                        </div>

                        <div class="space-y-8">
                            <!-- Payment Form -->
                            <div>
                                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                                    <h3 class="text-lg font-semibold text-gray-900 mb-6">اطلاعات پرداخت</h3>
                                    


                                    <!-- Discount Code Section -->
                                    <div class="mb-6">
                                        <button type="button" onclick="toggleDiscountSection()" class="w-full flex items-center justify-between p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition duration-200">
                                            <div class="flex items-center">
                                                <i class="fas fa-tag text-blue-600 ml-3"></i>
                                                <span class="text-lg font-semibold text-gray-900">کد تخفیف دارید؟</span>
                                            </div>
                                            <i id="discountToggleIcon" class="fas fa-chevron-down text-gray-600 transition-transform duration-200"></i>
                                        </button>
                                        <div id="discountContent" class="hidden mt-4 bg-gray-50 rounded-lg p-6">
                                            <div class="flex space-x-3 space-x-reverse">
                                                <div class="flex-1">
                                                    <input type="text" id="discountCode" placeholder="کد تخفیف خود را وارد کنید" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                                </div>
                                                <button onclick="applyDiscount()" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition duration-200 font-medium whitespace-nowrap">
                                                    اعمال کد
                                                </button>
                                            </div>
                                            <div id="discountMessage" class="mt-3 text-sm hidden"></div>
                                        </div>
                                    </div>

                                    <!-- Booking Details -->
                                    <div class="mb-6">
                                        <h3 class="text-lg font-semibold text-gray-900 mb-4">جزئیات رزرو</h3>
                                        <div class="bg-gray-50 rounded-lg p-6 space-y-4">
                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                                <div class="flex items-center">
                                                    <i class="fas fa-flask text-blue-600 ml-3"></i>
                                                    <div>
                                                        <p class="text-sm text-gray-600">نوع آزمایش</p>
                                                        <p class="font-medium text-gray-900">آزمایش کامل خون (CBC)</p>
                                                    </div>
                                                </div>
                                                <div class="flex items-center">
                                                    <i class="fas fa-calendar text-blue-600 ml-3"></i>
                                                    <div>
                                                        <p class="text-sm text-gray-600">تاریخ و زمان</p>
                                                        <p class="font-medium text-gray-900">۲۵ آذر ۱۴۰۳ - ۱۰:۰۰ صبح</p>
                                                    </div>
                                                </div>
                                                <div class="flex items-center">
                                                    <i class="fas fa-home text-blue-600 ml-3"></i>
                                                    <div>
                                                        <p class="text-sm text-gray-600">نوع خدمات</p>
                                                        <p class="font-medium text-gray-900">نمونه‌گیری در منزل</p>
                                                    </div>
                                                </div>
                                                <div class="flex items-center">
                                                    <i class="fas fa-shield-alt text-blue-600 ml-3"></i>
                                                    <div>
                                                        <p class="text-sm text-gray-600">پوشش بیمه</p>
                                                        <p class="font-medium text-gray-900">بیمه پایه</p>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="border-t border-gray-200 pt-4">
                                                <div class="flex items-center">
                                                    <i class="fas fa-map-marker-alt text-blue-600 ml-3"></i>
                                                    <div>
                                                        <p class="text-sm text-gray-600">آدرس نمونه‌گیری</p>
                                                        <p class="font-medium text-gray-900">تهران، خیابان ولیعصر، پلاک ۱۲۳، واحد ۱۰</p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Order Summary -->
                                    <div class="mb-6">
                                        <h3 class="text-lg font-semibold text-gray-900 mb-4">خلاصه سفارش</h3>
                                        <div class="bg-gray-50 rounded-lg p-6">
                                            <div class="space-y-4">
                                                <div class="flex justify-between items-center py-3 border-b border-gray-200">
                                                    <div>
                                                        <p class="font-medium text-gray-900">آزمایش کامل خون</p>
                                                        <p class="text-sm text-gray-600">نمونه‌گیری در منزل</p>
                                                    </div>
                                                    <span class="font-semibold text-gray-900">۸۵۰,۰۰۰ تومان</span>
                                                </div>
                                                
                                                <div class="flex justify-between items-center py-3 border-b border-gray-200">
                                                    <span class="text-gray-600">هزینه نمونه‌گیری</span>
                                                    <span class="text-gray-900">۵۰,۰۰۰ تومان</span>
                                                </div>
                                                
                                                <div class="flex justify-between items-center py-3 border-b border-gray-200">
                                                    <span class="text-gray-600">تخفیف بیمه</span>
                                                    <span class="text-green-600">-۲۰۰,۰۰۰ تومان</span>
                                                </div>
                                                
                                                <div id="discountRow" class="flex justify-between items-center py-3 border-b border-gray-200 hidden">
                                                    <span class="text-gray-600">تخفیف کد</span>
                                                    <span class="text-green-600" id="discountAmount">-۰ تومان</span>
                                                </div>
                                                
                                                <div class="flex justify-between items-center py-3 text-lg font-bold">
                                                    <span class="text-gray-900">مجموع قابل پرداخت</span>
                                                    <span class="text-blue-600" id="totalAmount">۷۰۰,۰۰۰ تومان</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Wallet Payment Info (only shown when wallet is selected) -->
                                    <div id="walletPaymentForm" class="space-y-4 hidden">
                                        <div class="p-4 bg-green-50 rounded-lg">
                                            <div class="flex items-center justify-between">
                                                <div>
                                                    <p class="font-medium text-green-800">موجودی کیف پول</p>
                                                    <p class="text-2xl font-bold text-green-600">۱,۵۰۰,۰۰۰ تومان</p>
                                                </div>
                                                <i class="fas fa-wallet text-green-600 text-3xl"></i>
                                            </div>
                                        </div>
                                        <div class="p-4 bg-blue-50 rounded-lg">
                                            <p class="text-blue-800">مبلغ ۷۰۰,۰۰۰ تومان از کیف پول شما کسر خواهد شد.</p>
                                        </div>
                                    </div>

                                    <!-- Terms and Conditions -->
                                    <div class="mt-6">
                                        <label class="flex items-start">
                                            <input type="checkbox" id="acceptTerms" class="mt-1 h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                            <span class="mr-2 text-sm text-gray-700">
                                                با <a href="#" class="text-blue-600 hover:underline">قوانین و مقررات</a> و 
                                                <a href="#" class="text-blue-600 hover:underline">سیاست حفظ حریم خصوصی</a> موافقم
                                            </span>
                                        </label>
                                    </div>

                                    <!-- Submit Button -->
                                    <div class="mt-8">
                                        <button onclick="submitRequest()" class="w-full bg-green-600 text-white py-4 px-6 rounded-lg hover:bg-green-700 transition duration-200 font-semibold text-lg">
                                            <i class="fas fa-check ml-2"></i>ثبت درخواست
                                        </button>
                                    </div>

                                    <!-- Security Badges -->
                                    <div class="mt-6 flex items-center justify-center space-x-6 space-x-reverse">
                                        <div class="flex items-center text-gray-500">
                                            <i class="fas fa-shield-alt ml-2"></i>
                                            <span class="text-sm">پرداخت امن</span>
                                        </div>
                                        <div class="flex items-center text-gray-500">
                                            <i class="fas fa-lock ml-2"></i>
                                            <span class="text-sm">رمزگذاری SSL</span>
                                        </div>
                                        <div class="flex items-center text-gray-500">
                                            <i class="fas fa-certificate ml-2"></i>
                                            <span class="text-sm">تایید شده</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Profile Section -->
                    <div id="profileSection" class="section hidden">
                        <div class="mb-8">
                            <h2 class="text-2xl font-bold text-gray-900 mb-2">تنظیمات پروفایل</h2>
                            <p class="text-gray-600">اطلاعات شخصی و تنظیمات حساب کاربری خود را مدیریت کنید.</p>
                        </div>

                        <!-- Success/Error Messages -->
                        <div id="profileMessage" class="hidden mb-6 p-4 rounded-lg"></div>

                        <!-- Personal Information -->
                        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">اطلاعات شخصی</h3>
                            <form id="profileForm" class="space-y-4">
                                <?php
                                // Get current user data
                                $current_user = wp_get_current_user();
                                $user_email = $current_user->user_email;
                                $mobile_number = get_user_meta($current_user->ID, 'apl_mobile_number', true);
                                $national_id = get_user_meta($current_user->ID, 'apl_national_id', true);
                                $address = get_user_meta($current_user->ID, 'apl_address', true);
                                
                                // Check if email and mobile are readonly
                                $email_readonly = !empty($user_email) ? 'readonly' : '';
                                $mobile_readonly = !empty($mobile_number) ? 'readonly' : '';
                                $email_class = !empty($user_email) ? 'bg-gray-100 cursor-not-allowed' : '';
                                $mobile_class = !empty($mobile_number) ? 'bg-gray-100 cursor-not-allowed' : '';
                                ?>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="grid grid-cols-1 md:grid-cols-1 gap-4">
                                        <div class="grid grid-cols-1 md:grid-cols- gap-4">
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-2">نام <span class="text-red-500">*</span></label>
                                                <input type="text" id="profileFirstName" name="first_name" value="<?php echo esc_attr($current_user->first_name); ?>" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-2">نام خانوادگی <span class="text-red-500">*</span></label>
                                                <input type="text" id="profileLastName" name="last_name" value="<?php echo esc_attr($current_user->last_name); ?>" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                            </div>
                                        </div>
                                        <div class="grid grid-cols-1 md:grid-cols- gap-4">
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-2">آدرس ایمیل <?php if (!$email_readonly): ?><span class="text-red-500">*</span><?php endif; ?></label>
                                                <input type="email" id="profileEmail" name="email" value="<?php echo esc_attr($user_email); ?>" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent <?php echo $email_class; ?>" dir="ltr" <?php echo $email_readonly; ?>>
                                                <?php if ($email_readonly): ?>
                                                <p class="text-xs text-gray-500 mt-1">ایمیل قابل تغییر نیست</p>
                                                <?php endif; ?>
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-2">شماره موبایل <?php if (!$mobile_readonly): ?><span class="text-red-500">*</span><?php endif; ?></label>
                                                <input type="tel" id="profileMobile" name="mobile" inputmode="numeric" value="<?php echo esc_attr($mobile_number); ?>" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent <?php echo $mobile_class; ?>" dir="ltr" <?php echo $mobile_readonly; ?>>
                                                <?php if ($mobile_readonly): ?>
                                                <p class="text-xs text-gray-500 mt-1">شماره موبایل قابل تغییر نیست</p>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="grid grid-cols-1 md:grid-cols- gap-4">
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-2">کد ملی <span class="text-red-500">*</span></label>
                                                <input type="text" id="profileNationalId" name="national_id" value="<?php echo esc_attr($national_id); ?>" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" dir="ltr" maxlength="10" pattern="[0-9]{10}" >
                                            </div>
                                            <div></div>
                                        </div>
                                    </div>
                                    <div class="profile-picture text-center">
                                        <label class="block text-sm font-medium text-gray-700 mb-4">عکس پروفایل</label>
                                        
                                        <!-- Current Profile Picture Display / Preview Area -->
                                        <div id="currentProfilePicture" class="mb-4 flex justify-center">
                                            <?php 
                                            $current_profile_picture = get_user_meta($current_user->ID, 'apl_profile_picture', true);
                                            if (!empty($current_profile_picture)): 
                                            ?>
                                                <div class="relative inline-block">
                                                    <img src="<?php echo esc_url($current_profile_picture); ?>" 
                                                         alt="عکس پروفایل" 
                                                         class="w-24 h-24 rounded-full object-cover border-2 border-gray-300">
                                                    <button type="button" 
                                                            id="removeProfilePicture" 
                                                            class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center text-xs hover:bg-red-600 transition duration-200"
                                                            title="حذف عکس">
                                                        ×
                                                    </button>
                                                </div>
                                            <?php else: ?>
                                                <div class="w-24 h-24 rounded-full bg-gray-200 border-2 border-dashed border-gray-300 flex items-center justify-center">
                                                    <i class="fas fa-user text-gray-400 text-2xl"></i>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <!-- File Input -->
                                        <div class="mb-4">
                                            <input type="file" 
                                                   id="profilePictureInput" 
                                                   name="profile_picture" 
                                                   accept="image/jpeg,image/jpg,image/png,image/gif"
                                                   class="hidden">
                                            <button type="button" 
                                                    id="selectProfilePictureBtn" 
                                                    class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600 transition duration-200 text-sm">
                                                <i class="fas fa-camera mr-2"></i>انتخاب عکس
                                            </button>
                                            <p class="text-xs text-gray-500 mt-2">فرمت‌های مجاز: JPG, PNG, GIF - حداکثر 5MB</p>
                                        </div>
                                        
                                        <!-- Action Buttons (Hidden by default) -->
                                        <div id="profilePictureActions" class="hidden mb-4">
                                            <div class="flex justify-center space-x-2 space-x-reverse">
                                                <button type="button" 
                                                        id="confirmUploadBtn" 
                                                        class="bg-green-500 text-white px-4 py-2 rounded-lg hover:bg-green-600 transition duration-200 text-sm">
                                                    <i class="fas fa-check mr-1"></i>تایید
                                                </button>
                                                <button type="button" 
                                                        id="cancelUploadBtn" 
                                                        class="bg-red-500 text-white px-4 py-2 rounded-lg hover:bg-red-600 transition duration-200 text-sm">
                                                    <i class="fas fa-times mr-1"></i>رد
                                                </button>
                                            </div>
                                        </div>
                                        
                                        <!-- Upload Status -->
                                        <div id="uploadStatus" class="hidden mb-4">
                                            <div class="flex items-center justify-center space-x-2 space-x-reverse">
                                                <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-blue-500"></div>
                                                <span class="text-sm text-gray-600">در حال آپلود...</span>
                                            </div>
                                        </div>
                                        
                                        <!-- Error/Success Messages -->
                                        <div id="profilePictureMessage" class="hidden mb-4">
                                            <div id="profilePictureMessageContent" class="text-sm p-3 rounded-lg"></div>
                                        </div>
                                    </div>
                                    </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">آدرس <span class="text-red-500">*</span></label>
                                    <textarea id="profileAddress" name="address" rows="3" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="آدرس کامل خود را وارد کنید"><?php echo esc_textarea($address); ?></textarea>
                                </div>
                                
                                <!-- Hidden nonce field -->
                                <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('apl_profile_nonce'); ?>">
                                <input type="hidden" name="profile_picture_nonce" value="<?php echo wp_create_nonce('apl_profile_picture_nonce'); ?>">
                                
                                <button type="submit" id="profileSubmitBtn" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition duration-200 font-medium">
                                    <span class="btn-text">به‌روزرسانی اطلاعات</span>
                                    <span class="btn-loading hidden">
                                        <i class="fas fa-spinner fa-spin ml-2"></i>در حال به‌روزرسانی...
                                    </span>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>



    
    