 <!-- Login/Register Screen -->
 <div id="authScreen" class="auth-screen-container min-h-screen bg-gradient-to-br from-blue-50 via-white to-purple-50 flex items-center justify-center p-4">
        <div class="w-full max-w-md">
            <!-- Logo and Header -->
            <div class="text-center mb-6 sm:mb-8">
                <?php
                $logo_id = get_option('apl_login_logo');
                $logo_url = $logo_id ? wp_get_attachment_url($logo_id) : '';
                $login_title = get_option('apl_login_title', 'آزمایشگاه پوش');
                $login_subtitle = get_option('apl_login_subtitle', 'شریک مطمئن شما در تشخیص‌های پزشکی');
                ?>
                
                <?php if ($logo_url): ?>
                    <div class="w-16 h-16 sm:w-20 sm:h-20 mx-auto mb-4 sm:mb-6">
                        <img src="<?php echo esc_url($logo_url); ?>" alt="<?php echo esc_attr($login_title); ?>" class="w-full h-full object-contain rounded-2xl shadow-lg">
                    </div>
                <?php else: ?>
                    <div class="w-16 h-16 sm:w-20 sm:h-20 bg-gradient-to-br from-blue-600 to-purple-600 rounded-2xl flex items-center justify-center mx-auto mb-4 sm:mb-6 shadow-lg">
                        <i class="fas fa-flask text-white text-2xl sm:text-3xl"></i>
                    </div>
                <?php endif; ?>
                
                <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 mb-2"><?php echo esc_html($login_title); ?></h1>
                <p class="text-sm sm:text-base text-gray-600"><?php echo esc_html($login_subtitle); ?></p>
            </div>

            <!-- Auth Tabs -->
            <div class="auth-card bg-white rounded-2xl shadow-xl p-6 sm:p-8">
                <!-- Tab Headers -->
                <div class="flex bg-gray-100 rounded-xl p-1 mb-8">
                    <button id="loginTab" onclick="switchAuthTab('login')" class="flex-1 py-3 px-4 rounded-lg text-sm font-medium transition-all duration-200 bg-white text-blue-600 shadow-sm">
                        ورود
                    </button>
                    <button id="registerTab" onclick="switchAuthTab('register')" class="flex-1 py-3 px-4 rounded-lg text-sm font-medium transition-all duration-200 text-gray-600 hover:text-gray-900">
                        ثبت نام
                    </button>
                </div>

                <!-- Login Form -->
                <div id="loginForm" class="auth-form">
                    <!-- Step 1: Mobile Input -->
                    <div id="loginStep1" class="auth-step">
                        <div class="text-center mb-6">
                            <h2 class="text-xl font-bold text-gray-900 mb-2">ورود به حساب کاربری</h2>
                            <p class="text-gray-600">شماره موبایل خود را وارد کنید</p>
                        </div>
                        
                        <div class="space-y-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">شماره موبایل</label>
                                <div class="relative">
                                    <input type="tel" id="loginMobileInput" inputmode="numeric" class="w-full px-4 py-4 pr-12 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent text-left text-lg" placeholder="09123456789" dir="ltr" maxlength="11">
                                    <i class="fas fa-mobile-alt absolute right-4 top-4 text-gray-400 text-lg"></i>
                                </div>
                            </div>
                            
                            <button onclick="sendLoginOTP()" id="loginSendBtn" class="w-full bg-gradient-to-r from-blue-600 to-purple-600 text-white py-4 rounded-xl hover:from-blue-700 hover:to-purple-700 transition duration-200 font-medium text-lg shadow-lg">
                                <span class="btn-text">ارسال کد تایید</span>
                                <div class="btn-loading hidden">
                                    <i class="fas fa-spinner fa-spin ml-2"></i>
                                    در حال ارسال...
                                </div>
                            </button>
                        </div>
                    </div>

                    <!-- Step 2: OTP Input -->
                    <div id="loginStep2" class="auth-step hidden">
                        <div class="text-center mb-6">
                            <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                <i class="fas fa-sms text-green-600 text-2xl"></i>
                            </div>
                            <h2 class="text-xl font-bold text-gray-900 mb-2">کد تایید را وارد کنید</h2>
                            <p class="text-gray-600">کد ۶ رقمی ارسال شده به</p>
                            <p class="font-medium text-gray-900" id="loginSentToNumber"></p>
                        </div>
                        
                        <div class="space-y-6">
                            <!-- OTP Input Boxes -->
                            <div class="otp-container flex justify-center space-x-3 space-x-reverse" dir="ltr">
                                <input type="text" inputmode="numeric" id="otp-input-0" class="otp-input w-10 h-10 sm:w-12 sm:h-12 text-center text-lg sm:text-xl font-bold border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200" maxlength="1" data-index="0">
                                <input type="text" inputmode="numeric" id="otp-input-1" class="otp-input w-10 h-10 sm:w-12 sm:h-12 text-center text-lg sm:text-xl font-bold border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200" maxlength="1" data-index="1">
                                <input type="text" inputmode="numeric" id="otp-input-2" class="otp-input w-10 h-10 sm:w-12 sm:h-12 text-center text-lg sm:text-xl font-bold border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200" maxlength="1" data-index="2">
                                <input type="text" inputmode="numeric" id="otp-input-3" class="otp-input w-10 h-10 sm:w-12 sm:h-12 text-center text-lg sm:text-xl font-bold border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200" maxlength="1" data-index="3">
                                <input type="text" inputmode="numeric" id="otp-input-4" class="otp-input w-10 h-10 sm:w-12 sm:h-12 text-center text-lg sm:text-xl font-bold border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200" maxlength="1" data-index="4">
                                <input type="text" inputmode="numeric" id="otp-input-5" class="otp-input w-10 h-10 sm:w-12 sm:h-12 text-center text-lg sm:text-xl font-bold border-2 border-gray-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200" maxlength="1" data-index="5">
                            </div>
                            
                            <!-- Resend Timer -->
                            <div class="text-center">
                                <p class="text-gray-600 text-sm mb-2">کد را دریافت نکردید؟</p>
                                <button id="resendBtn" onclick="resendOTP()" class="text-blue-600 hover:text-blue-800 font-medium text-sm disabled:text-gray-400 disabled:cursor-not-allowed" disabled>
                                    <span id="resendText">ارسال مجدد</span>
                                    <span id="resendTimer">(59)</span>
                                </button>
                            </div>
                            
                            <div class="flex space-x-3 space-x-reverse">
                                <button onclick="backToLoginMobile()" class="flex-1 bg-gray-100 text-gray-700 py-3 rounded-xl hover:bg-gray-200 transition duration-200 font-medium">
                                    بازگشت
                                </button>
                                <button onclick="verifyLoginOTP()" id="loginVerifyBtn" class="flex-1 bg-gradient-to-r from-blue-600 to-purple-600 text-white py-3 rounded-xl hover:from-blue-700 hover:to-purple-700 transition duration-200 font-medium shadow-lg">
                                    <span class="btn-text">تایید</span>
                                    <div class="btn-loading hidden">
                                        <i class="fas fa-spinner fa-spin ml-2"></i>
                                        در حال تایید...
                                    </div>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Register Form -->
                <div id="registerForm" class="auth-form hidden">
                    <!-- Step 1: User Info -->
                    <div id="registerStep1" class="auth-step">
                        <div class="text-center mb-6">
                            <h2 class="text-xl font-bold text-gray-900 mb-2">ایجاد حساب کاربری</h2>
                            <p class="text-gray-600">اطلاعات خود را وارد کنید</p>
                        </div>
                        
                        <div class="space-y-4">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">نام</label>
                                    <input type="text" id="registerFirstName" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="نام خود را وارد کنید">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">نام خانوادگی</label>
                                    <input type="text" id="registerLastName" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="نام خانوادگی">
                                </div>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">شماره موبایل</label>
                                <div class="relative">
                                    <input type="tel" id="registerMobileInput" inputmode="numeric" class="w-full px-4 py-3 pr-12 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent text-left" placeholder="09123456789" dir="ltr" maxlength="11">
                                    <i class="fas fa-mobile-alt absolute right-4 top-3 text-gray-400"></i>
                                </div>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">کد ملی</label>
                                <input type="text" id="registerNationalId" inputmode="numeric" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-transparent text-left" placeholder="کد ملی ۱۰ رقمی" dir="ltr" maxlength="10">
                            </div>
                            
                            <div>
                                <label class="flex items-start mt-4">
                                    <input type="checkbox" id="acceptRegisterTerms" class="mt-1 h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                    <span class="mr-2 text-sm text-gray-700">
                                        <?php echo esc_html(get_option('apl_login_terms_text', 'با قوانین و مقررات و سیاست حفظ حریم خصوصی موافقم')); ?>
                                    </span>
                                </label>
                            </div>
                            
                            <button onclick="sendRegisterOTP()" id="registerSendBtn" class="w-full bg-gradient-to-r from-green-600 to-blue-600 text-white py-4 rounded-xl hover:from-green-700 hover:to-blue-700 transition duration-200 font-medium text-lg shadow-lg">
                                <span class="btn-text">ثبت نام و ارسال کد</span>
                                <div class="btn-loading hidden">
                                    <i class="fas fa-spinner fa-spin ml-2"></i>
                                    در حال ثبت نام...
                                </div>
                            </button>
                        </div>
                    </div>

                    <!-- Step 2: OTP Verification -->
                    <div id="registerStep2" class="auth-step hidden">
                        <div class="text-center mb-6">
                            <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                <i class="fas fa-user-check text-green-600 text-2xl"></i>
                            </div>
                            <h2 class="text-xl font-bold text-gray-900 mb-2">تایید شماره موبایل</h2>
                            <p class="text-gray-600">کد ۶ رقمی ارسال شده به</p>
                            <p class="font-medium text-gray-900" id="registerSentToNumber"></p>
                        </div>
                        
                        <div class="space-y-6">
                            <!-- OTP Input Boxes -->
                            <div class="otp-container flex justify-center space-x-3 space-x-reverse" dir="ltr">
                                <input type="text" inputmode="numeric" id="register-otp-input-0" class="register-otp-input w-10 h-10 sm:w-12 sm:h-12 text-center text-lg sm:text-xl font-bold border-2 border-gray-300 rounded-lg focus:border-green-500 focus:ring-2 focus:ring-green-200" maxlength="1" data-index="0">
                                <input type="text" inputmode="numeric" id="register-otp-input-1" class="register-otp-input w-10 h-10 sm:w-12 sm:h-12 text-center text-lg sm:text-xl font-bold border-2 border-gray-300 rounded-lg focus:border-green-500 focus:ring-2 focus:ring-green-200" maxlength="1" data-index="1">
                                <input type="text" inputmode="numeric" id="register-otp-input-2" class="register-otp-input w-10 h-10 sm:w-12 sm:h-12 text-center text-lg sm:text-xl font-bold border-2 border-gray-300 rounded-lg focus:border-green-500 focus:ring-2 focus:ring-green-200" maxlength="1" data-index="2">
                                <input type="text" inputmode="numeric" id="register-otp-input-3" class="register-otp-input w-10 h-10 sm:w-12 sm:h-12 text-center text-lg sm:text-xl font-bold border-2 border-gray-300 rounded-lg focus:border-green-500 focus:ring-2 focus:ring-green-200" maxlength="1" data-index="3">
                                <input type="text" inputmode="numeric" id="register-otp-input-4" class="register-otp-input w-10 h-10 sm:w-12 sm:h-12 text-center text-lg sm:text-xl font-bold border-2 border-gray-300 rounded-lg focus:border-green-500 focus:ring-2 focus:ring-green-200" maxlength="1" data-index="4">
                                <input type="text" inputmode="numeric" id="register-otp-input-5" class="register-otp-input w-10 h-10 sm:w-12 sm:h-12 text-center text-lg sm:text-xl font-bold border-2 border-gray-300 rounded-lg focus:border-green-500 focus:ring-2 focus:ring-green-200" maxlength="1" data-index="5">
                            </div>
                            
                            <!-- Resend Timer -->
                            <div class="text-center">
                                <p class="text-gray-600 text-sm mb-2">کد را دریافت نکردید؟</p>
                                <button id="registerResendBtn" onclick="resendRegisterOTP()" class="text-green-600 hover:text-green-800 font-medium text-sm disabled:text-gray-400 disabled:cursor-not-allowed" disabled>
                                    <span id="registerResendText">ارسال مجدد</span>
                                    <span id="registerResendTimer">(59)</span>
                                </button>
                            </div>
                            
                            <div class="flex space-x-3 space-x-reverse">
                                <button onclick="backToRegisterInfo()" class="flex-1 bg-gray-100 text-gray-700 py-3 rounded-xl hover:bg-gray-200 transition duration-200 font-medium">
                                    بازگشت
                                </button>
                                <button onclick="verifyRegisterOTP()" id="registerVerifyBtn" class="flex-1 bg-gradient-to-r from-green-600 to-blue-600 text-white py-3 rounded-xl hover:from-green-700 hover:to-blue-700 transition duration-200 font-medium shadow-lg">
                                    <span class="btn-text">تایید و ثبت نام</span>
                                    <div class="btn-loading hidden">
                                        <i class="fas fa-spinner fa-spin ml-2"></i>
                                        در حال تایید...
                                    </div>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
