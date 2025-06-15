// --- Console Log Helper Functions (Optional but useful for debugging) ---
function logInfo(message, data = null) {
    console.info(
        `[AppMain] ${message}`,
        data !== null ? JSON.parse(JSON.stringify(data)) : ""
    );
}
function logWarn(message, data = null) {
    console.warn(
        `[AppMain] ${message}`,
        data !== null ? JSON.parse(JSON.stringify(data)) : ""
    );
}
function logError(message, data = null) {
    console.error(
        `[AppMain] ${message}`,
        data !== null ? JSON.parse(JSON.stringify(data)) : ""
    );
}

// --- Ensure APP_CONFIG is available ---
if (typeof window.APP_CONFIG === "undefined") {
    logError(
        "CRITICAL: APP_CONFIG is not defined! Please ensure it's set in the main layout <head> script tag."
    );
    // You might want to throw an error or display a message to the user here
} else {
    logInfo("APP_CONFIG successfully loaded:", window.APP_CONFIG);
}

// --- Axios Global Setup ---
if (window.axios) {
    window.axios.defaults.baseURL = window.APP_CONFIG.apiBaseUrl || "/api/v1"; // Set base URL for API calls
    window.axios.defaults.headers.common["X-Requested-With"] = "XMLHttpRequest";
    window.axios.defaults.headers.common["Accept"] = "application/json";

    const initialAccessToken = localStorage.getItem("access_token");
    if (initialAccessToken) {
        logInfo(
            "Axios: Initial access token found. Setting default Authorization header."
        );
        window.axios.defaults.headers.common[
            "Authorization"
        ] = `Bearer ${initialAccessToken}`;
    } else {
        logWarn("Axios: No initial access token found in localStorage.");
    }

    // --- Axios Response Interceptor (401 Handler with Token Refresh) ---
    window.axios.interceptors.response.use(
        (response) => {
            logInfo(
                "Axios Interceptor: Successful response for URL - " +
                    response.config.url
            );
            return response;
        },
        async (error) => {
            const originalRequest = error.config;
            const status = error.response ? error.response.status : null;
            const errorMessage = error.response
                ? error.response.data.message || error.message
                : error.message;

            logError(
                "Axios Interceptor: Error for URL - " +
                    originalRequest.url +
                    ", Status: " +
                    status,
                error.response ? error.response.data : error
            );

            // Handle 401: Unauthorized (e.g., token expired)
            if (
                status === 401 &&
                originalRequest &&
                !originalRequest._retryAttempted &&
                !originalRequest.url.includes("/auth/refresh-token")
            ) {
                originalRequest._retryAttempted = true; // Mark to prevent infinite retry loop
                const refreshToken = localStorage.getItem("refresh_token");

                if (refreshToken) {
                    try {
                        logInfo(
                            "Interceptor(401): Attempting to refresh token..."
                        );
                        const refreshConfig = {
                            headers: { Authorization: "" },
                        }; // Don't send expired access token for refresh
                        const refreshResponse = await axios.post(
                            `${window.APP_CONFIG.apiBaseUrl}/auth/refresh-token`,
                            { refresh_token: refreshToken },
                            refreshConfig
                        );

                        const newAccessToken =
                            refreshResponse.data.data.access_token;
                        localStorage.setItem("access_token", newAccessToken);
                        window.axios.defaults.headers.common[
                            "Authorization"
                        ] = `Bearer ${newAccessToken}`;
                        originalRequest.headers[
                            "Authorization"
                        ] = `Bearer ${newAccessToken}`; // Update header for the original retried request
                        logInfo(
                            "Interceptor(401): Token refreshed successfully. Retrying original request to " +
                                originalRequest.url
                        );
                        return axios(originalRequest); // Retry the original request with the new token
                    } catch (refreshError) {
                        logError(
                            "Interceptor(401): Failed to refresh token.",
                            refreshError.response
                                ? refreshError.response.data
                                : refreshError.message
                        );
                        // If refresh token fails, clear auth data and redirect
                        clearAuthDataAndRedirect(
                            true,
                            "Your session has expired. Please log in again. (Refresh Failed)"
                        );
                        return Promise.reject(refreshError); // Important to reject with the refresh error
                    }
                } else {
                    logError(
                        "Interceptor(401): No refresh token found. Clearing auth data."
                    );
                    clearAuthDataAndRedirect(
                        true,
                        "Authentication required. Please log in. (No Refresh Token)"
                    );
                    return Promise.reject(error); // Reject with the original error
                }
            }
            // For other errors, or if it's a retry / refresh token call itself that failed, or non-401 error
            return Promise.reject(error);
        }
    );
} else {
    logError("Axios library is not loaded! API calls will fail.");
}

// --- Authentication Helper Functions ---
function clearAuthDataAndRedirect(
    showAlert = true,
    alertMessage = "Your session has expired or you have been logged out. Please log in again."
) {
    logWarn("Auth Helper: Clearing authentication data and redirecting...");
    localStorage.removeItem("access_token");
    localStorage.removeItem("refresh_token");
    localStorage.removeItem("user");
    if (window.axios)
        window.axios.defaults.headers.common["Authorization"] = ""; // Clear Axios auth header

    // Update UI elements to reflect logged-out state
    if (typeof updateNavbarUserDetails === "function")
        updateNavbarUserDetails(null);
    if (typeof setupSidebarBasedOnPermissions === "function")
        setupSidebarBasedOnPermissions(null);

    const targetUrl =
        window.APP_CONFIG.baseUrl + window.APP_CONFIG.routes.login;

    if (showAlert && typeof Swal !== "undefined") {
        Swal.fire({
            title: "Session Action Needed",
            text: alertMessage,
            icon: "warning",
            confirmButtonText: "Go to Login",
            allowOutsideClick: false,
            allowEscapeKey: false,
        }).then((result) => {
            // Redirect even if user closes the alert by other means, unless they click confirm (already handled by then)
            if (
                window.location.href.toLowerCase() !== targetUrl.toLowerCase()
            ) {
                window.location.href = targetUrl;
            }
        });
    } else {
        // If Swal is not available or showAlert is false, redirect immediately
        if (window.location.href.toLowerCase() !== targetUrl.toLowerCase()) {
            window.location.href = targetUrl;
        }
    }
}

// --- UI Update Functions ---
function updateNavbarUserDetails(userData = null) {
    const user =
        userData ||
        (localStorage.getItem("user")
            ? JSON.parse(localStorage.getItem("user"))
            : null);
    const defaultAvatar =
        window.APP_CONFIG.defaultAvatar || "assets/images/users/avatar-1.jpg"; // Fallback default
    logInfo("Navbar Update: User data received:", user);

    if (user && user.name) {
        $("#dropdownUserName").text(user.name);
        $("#dropdownUserEmail").text(user.email || "N/A");
        // Check if profile_photo_url is not the default one before using it
        const avatarUrl =
            user.profile_photo_url &&
            user.profile_photo_url !== defaultAvatar &&
            !user.profile_photo_url.endsWith("default-avatar.png")
                ? user.profile_photo_url
                : defaultAvatar;
        $("#navbarUserAvatar")
            .attr("src", avatarUrl)
            .on("error", function () {
                $(this).attr("src", defaultAvatar);
            }); // Fallback if image fails to load
        $("#dropdownUserAvatar")
            .attr("src", avatarUrl)
            .on("error", function () {
                $(this).attr("src", defaultAvatar);
            });
        logInfo(
            "Navbar Update: User details updated for '" +
                user.name +
                "'. Avatar URL: " +
                avatarUrl
        );
    } else {
        $("#dropdownUserName").text("Guest User");
        $("#dropdownUserEmail").text("Not Logged In");
        $("#navbarUserAvatar").attr("src", defaultAvatar);
        $("#dropdownUserAvatar").attr("src", defaultAvatar);
        logInfo("Navbar Update: Displaying guest/logged-out state.");
    }
}

function setupSidebarBasedOnPermissions(user) {
    logInfo("Sidebar Setup: Initializing. User data:", user);
    const $sidebarMenu = $("#iq-sidebar-toggle"); // For POSDash template
    // For Skote template, the main menu UL has id="side-menu"
    // const $sidebarMenu = $('#side-menu'); // <<<< USE THIS FOR SKOTE TEMPLATE

    // --- Step 1: Hide all potentially role-restricted items and titles first ---
    // These classes should be on the <li> elements in sidebar.blade.php
    $sidebarMenu.find("li.menu-item, li.menu-title").each(function () {
        const $item = $(this);
        if (
            !$item.hasClass("always-visible") &&
            !$item.hasClass("unauthenticated-visible")
        ) {
            $item.hide();
        }
    });

    // --- Step 2: Handle unauthenticated or no-role user ---
    if (
        !user ||
        !user.roles ||
        !Array.isArray(user.roles) ||
        user.roles.length === 0
    ) {
        logWarn(
            "Sidebar Setup: No user or no valid roles. Showing only 'always-visible' or 'unauthenticated-visible' items."
        );
        $sidebarMenu.find(".always-visible").hide(); // Hide items like "My Profile" for unauth users
        $sidebarMenu.find(".unauthenticated-visible").show(); // Show "Login" link if it exists
        // Re-initialize MetisMenu if using Skote, for unauthenticated state (few items)
        if ($("#side-menu").length && typeof $.fn.metisMenu === "function") {
            $("#side-menu").metisMenu("dispose").metisMenu();
        }
        return;
    }

    // --- Step 3: User is authenticated, show 'always-visible' items ---
    $sidebarMenu.find(".always-visible").show();
    $sidebarMenu.find(".unauthenticated-visible").hide(); // Hide login link

    const roles = user.roles;
    logInfo("Sidebar Setup: Processing for authenticated user roles:", roles);

    // --- Show items based on roles ---
    if (roles.includes("super-admin")) {
        logInfo("Sidebar: Applying Super Admin menus.");
        $sidebarMenu.find(".menu-super-admin").show(); // Show all LIs with this class
        $sidebarMenu.find(".menu-section-admin").show(); // Show "Administration" title

        // Super admin can see everything, so also show other sections they might interact with
        $sidebarMenu.find(".menu-section-store").show();
        $sidebarMenu.find(".menu-store-admin").show(); // Allow SA to see/use Store Admin menus
        $sidebarMenu.find(".exclusive-for-store-admin").show();
        $sidebarMenu.find(".menu-sales-person").show(); // And Sales Person menus
    } else {
        // Not Super Admin
        if (roles.includes("store-admin")) {
            logInfo("Sidebar: Applying Store Admin menus.");
            if (user.store_id) $(".menu-section-store").show();
            $sidebarMenu.find(".menu-store-admin").show();
            $sidebarMenu.find(".exclusive-for-store-admin").show();
        }
        if (roles.includes("sales-person")) {
            logInfo("Sidebar: Applying Sales Person menus.");
            if (user.store_id) $(".menu-section-store").show();
            $sidebarMenu.find(".menu-sales-person").show();
            if (!roles.includes("store-admin")) {
                // If ONLY sales person
                $sidebarMenu.find(".exclusive-for-store-admin").hide();
            }
        }
    }

    // Update store name in sidebar title if applicable
    if (
        user.store_id &&
        (roles.includes("store-admin") || roles.includes("sales-person")) &&
        !roles.includes("super-admin")
    ) {
        if (user.store && user.store.name) {
            $("#myStoreNameTitleSidebar").text("Store: " + user.store.name);
        } else {
            // Fetch store name if not available in user object from localStorage
            axios
                .get(`${window.APP_CONFIG.apiBaseUrl}/stores/${user.store_id}`)
                .then((response) => {
                    if (response.data.success && response.data.data.name) {
                        $("#myStoreNameTitleSidebar").text(
                            "Store: " + response.data.data.name
                        );
                        // Optionally save updated store info to localStorage user object
                        let localUser = JSON.parse(
                            localStorage.getItem("user")
                        );
                        if (localUser) {
                            localUser.store = response.data.data;
                            localStorage.setItem(
                                "user",
                                JSON.stringify(localUser)
                            );
                        }
                    }
                })
                .catch((err) =>
                    logError(
                        "Sidebar: Could not fetch store name for title.",
                        err
                    )
                );
        }
    } else if (roles.includes("super-admin")) {
        $("#myStoreNameTitleSidebar").text("Store Operations (All)"); // Or based on current filter
    }

    // --- Re-initialize MetisMenu (for Skote template or similar that use it) ---
    // This is CRUCIAL for dynamic sidebar menus to work correctly (collapse/expand).
    const $sideMenuElement = $("#side-menu"); // Skote uses #side-menu for metisMenu
    if ($sideMenuElement.length && typeof $.fn.metisMenu === "function") {
        logInfo("Sidebar JS: Re-initializing MetisMenu for Skote sidebar.");
        $sideMenuElement.metisMenu("dispose"); // Dispose of previous instance
        $sideMenuElement.metisMenu(); // Re-initialize
        // Small delay to ensure DOM is updated before MetisMenu re-initializes expand/collapse state
        setTimeout(function () {
            $sideMenuElement.metisMenu("dispose").metisMenu();
            // Ensure active parent menus are expanded
            $sidebarMenu
                .find("li.mm-active")
                .parents("ul.sub-menu")
                .addClass("mm-show")
                .siblings("a.has-arrow")
                .attr("aria-expanded", true);
        }, 100);
    } else if (
        $("#iq-sidebar-toggle").length &&
        typeof $("#iq-sidebar-toggle").metisMenu === "function"
    ) {
        // Fallback for POSDash
        logInfo(
            "Sidebar JS: Re-initializing MetisMenu for POSDash sidebar (#iq-sidebar-toggle)."
        );
        $("#iq-sidebar-toggle").metisMenu("dispose").metisMenu();
        setTimeout(function () {
            $("#iq-sidebar-toggle").metisMenu("dispose").metisMenu();
            $sidebarMenu
                .find("li.active")
                .parents("ul.iq-submenu.collapse")
                .addClass("show")
                .siblings("a.collapsed")
                .attr("aria-expanded", true)
                .removeClass("collapsed");
        }, 100);
    } else {
        logWarn(
            "Sidebar JS: MetisMenu function not found or target element (#side-menu or #iq-sidebar-toggle) does not exist. Sidebar interactivity might be limited."
        );
    }

    logInfo("Sidebar JS: Setup completed.");
}

// --- Initial Page Load Auth Check (IIFE) ---
(function () {
    logInfo("IIFE Auth Check: Running.");
    const accessToken = localStorage.getItem("access_token");
    const userString = localStorage.getItem("user");
    let user = null;

    if (userString) {
        try {
            user = JSON.parse(userString);
        } catch (e) {
            user = null;
            logError("IIFE: Error parsing user.", e);
        }
    }

    const currentRelativePath = (function () {
        if (!window.APP_CONFIG || !window.APP_CONFIG.baseUrl)
            return window.location.pathname;
        const appBasePath = window.APP_CONFIG.baseUrl.replace(
            window.location.origin,
            ""
        );
        let path = window.location.pathname;
        if (path.startsWith(appBasePath))
            path = path.substring(appBasePath.length);
        if (path.startsWith("/index.php"))
            path = path.substring("/index.php".length);
        return path === "" || path === "/" ? "/" : path.replace(/\/$/, "");
    })();

    const publicAuthPageRelativePaths = [
        window.APP_CONFIG.routes.login,
        window.APP_CONFIG.routes.registerForm,
        // Add other public auth page relative paths if APP_CONFIG.routes has them
    ];

    const isOnPublicAuthPage =
        publicAuthPageRelativePaths.includes(currentRelativePath);
    // Assuming root "/" is a general public page or redirects based on auth
    const isOnGeneralPublicPageOrRoot = currentRelativePath === "/";

    if (accessToken && user) {
        logInfo("IIFE Auth Check: Token and user found.");
        if (
            window.axios &&
            window.axios.defaults.headers.common["Authorization"] !==
                `Bearer ${accessToken}`
        ) {
            window.axios.defaults.headers.common[
                "Authorization"
            ] = `Bearer ${accessToken}`;
        }
        if (isOnPublicAuthPage && !isOnGeneralPublicPageOrRoot) {
            // Avoid redirecting from root if user is logged in
            logInfo(
                "IIFE Auth Check: Logged in, on auth page (not root). Redirecting to dashboard."
            );
            window.location.href =
                window.APP_CONFIG.baseUrl + window.APP_CONFIG.routes.dashboard;
        } else {
            logInfo(
                "IIFE Auth Check: Logged in. Current path is protected, root or non-auth public. No redirect from IIFE."
            );
        }
    } else {
        // No token or no user
        if (!isOnPublicAuthPage && !isOnGeneralPublicPageOrRoot) {
            logWarn(
                "IIFE Auth Check: No token/user on protected page. Path: " +
                    currentRelativePath +
                    ". Redirecting to login via clearAuthData."
            );
            clearAuthDataAndRedirect(false); // Redirect immediately without alert
        } else {
            logInfo(
                "IIFE Auth Check: On public/auth page or root, and no token/user. No redirect needed from IIFE."
            );
        }
    }
})();

// --- DOM Ready ---
$(document).ready(function () {
    logInfo("DOM Ready: Initializing application UI components.");
    const userFromStorage = localStorage.getItem("user")
        ? JSON.parse(localStorage.getItem("user"))
        : null;

    updateNavbarUserDetails(userFromStorage); // Update navbar with user info or guest state
    setupSidebarBasedOnPermissions(userFromStorage); // Setup sidebar menus based on user roles

    // Universal Logout Button Handler
    // Using event delegation for any logout button that might be added dynamically
    // Global Logout Functionality
    $("#logout-button").on("click", function (e) {
        e.preventDefault();

        // Invalidate the cookie by setting its expiration to a past date
        document.cookie =
            "auth_token=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;";

        // You can still call the API to invalidate the token on the server
        axios.post("auth/logout").finally(() => {
            window.location.href = "{{ route('login') }}";
        });
    });

    // General initializations for the Skote template (if not handled by its own app.js)
    // This part depends heavily on how Skote initializes its components.
    // Usually, a template's main app.js or a bundle like backend-bundle.js handles this.
    // If MetisMenu is not auto-initialized for the sidebar by Skote's JS:
    if (
        $("#side-menu").length &&
        typeof $.fn.metisMenu === "function" &&
        !$("#side-menu").hasClass("mm-active")
    ) {
        logInfo(
            "DOM Ready: Initializing MetisMenu for #side-menu as it was not auto-initialized."
        );
        $("#side-menu").metisMenu();
    } else if (
        $("#iq-sidebar-toggle").length &&
        typeof $("#iq-sidebar-toggle").metisMenu === "function" &&
        !$("#iq-sidebar-toggle").hasClass("mm-active")
    ) {
        logInfo(
            "DOM Ready: Initializing MetisMenu for #iq-sidebar-toggle as it was not auto-initialized."
        );
        $("#iq-sidebar-toggle").metisMenu();
    }

    // Activate Bootstrap tooltips (common in many templates)
    if (typeof $('[data-toggle="tooltip"]').tooltip === "function") {
        $('[data-toggle="tooltip"]').tooltip();
    }
    // Activate Bootstrap popovers
    if (typeof $('[data-toggle="popover"]').popover === "function") {
        $('[data-toggle="popover"]').popover();
    }
    // Activate Bootstrap dropdowns (usually handled by data-toggle="dropdown")
    // $('.dropdown-toggle').dropdown(); // This might be needed if they don't work automatically

    logInfo("DOM Ready: Main application UI initializations complete.");
});
