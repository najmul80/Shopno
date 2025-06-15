// public/js/auth-redirect.js
(function () {
    if (typeof window.APP_CONFIG === 'undefined') {
        console.error("auth-redirect.js: APP_CONFIG is not defined!");
        return;
    }
    console.log("AuthRedirect: Script Running for path:", window.location.pathname);

    const accessToken = localStorage.getItem('access_token');
    const userString = localStorage.getItem('user'); // Check if user data also exists

    if (accessToken && userString) { // Only redirect if both token and user data are present
        const currentPath = (function(){ // Helper to get relative path
            const appBasePath = window.APP_CONFIG.baseUrl.replace(window.location.origin, "");
            let path = window.location.pathname;
            if (path.startsWith(appBasePath)) path = path.substring(appBasePath.length);
            if (path.startsWith("/index.php")) path = path.substring("/index.php".length);
            return path === "" || path === "/" ? "/" : path.replace(/\/$/, "");
        })();

        const authPageRelativePaths = [
            window.APP_CONFIG.routes.login,
            window.APP_CONFIG.routes.registerForm,
            // Do not redirect from password reset flow pages if user is logged in
            // but wants to reset password (though typically they'd logout first).
            window.APP_CONFIG.routes.forgotPassword,
            window.APP_CONFIG.routes.verifyOtp,
            window.APP_CONFIG.routes.resetPasswordForm,
        ];

        if (authPageRelativePaths.includes(currentPath)) {
            console.log('AuthRedirect: User already logged in and on an auth page. Redirecting to dashboard from:', currentPath);
            window.location.href = window.APP_CONFIG.baseUrl + window.APP_CONFIG.routes.dashboard;
        }
    } else {
        console.log('AuthRedirect: No access token/user or not on a page that needs redirect for logged in state.');
    }
})();