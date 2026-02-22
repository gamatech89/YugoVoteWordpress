/**
 * YugoVote AJAX Login Handler
 * Handles the custom login form submission
 */
(function ($) {
  "use strict";

  $(document).ready(function () {
    console.log("YugoLogin: Script loaded");
    console.log(
      "YugoLogin config:",
      typeof yugoLogin !== "undefined" ? yugoLogin : "not defined"
    );
    initLoginForm();
  });

  function initLoginForm() {
    // Find the login form - check for our custom form first, then fallback
    let $form = $("#ygv-login-form");

    if (!$form.length) {
      // Fallback to any form with password field
      $form = $("form")
        .filter(function () {
          return (
            $(this).find('input[type="password"]').length > 0 &&
            $(this).find(
              'input[name="log"], input[type="text"], input[type="email"]'
            ).length > 0
          );
        })
        .first();
    }

    if (!$form.length) {
      console.log("YugoLogin: No login form found on page");
      return;
    }

    console.log("YugoLogin: Form found", $form.attr("id") || "no id");

    // Find form elements - support both WordPress standard names and generic
    const $usernameField = $form.find('input[name="log"]').length
      ? $form.find('input[name="log"]')
      : $form.find('input[type="text"], input[type="email"]').first();
    const $passwordField = $form.find('input[name="pwd"]').length
      ? $form.find('input[name="pwd"]')
      : $form.find('input[type="password"]').first();
    const $rememberField = $form
      .find('input[name="rememberme"], input[type="checkbox"]')
      .first();
    const $submitBtn = $form
      .find('button[type="submit"], input[type="submit"]')
      .first();

    console.log(
      "YugoLogin: Username field:",
      $usernameField.attr("name") || $usernameField.length
    );
    console.log(
      "YugoLogin: Password field:",
      $passwordField.attr("name") || $passwordField.length
    );
    console.log("YugoLogin: Submit button:", $submitBtn.length);

    // Create/find error message container
    let $errorContainer = $form.find(".yugo-login-error");
    if (!$errorContainer.length) {
      $errorContainer = $(
        '<div class="yugo-login-error" style="display:none; color: #e74c3c; background: #fdf2f2; padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; font-size: 14px;"></div>'
      );
      $form.prepend($errorContainer);
    }

    // Store original button text
    let originalBtnText = "";
    if ($submitBtn.is("button")) {
      originalBtnText = $submitBtn.html();
    } else {
      originalBtnText = $submitBtn.val();
    }

    // Handle form submission
    $form.on("submit", function (e) {
      e.preventDefault();
      e.stopPropagation();

      console.log("YugoLogin: Form submitted");

      const username = $usernameField.val().trim();
      const password = $passwordField.val();
      const remember = $rememberField.is(":checked");

      console.log("YugoLogin: Username:", username);
      console.log(
        "YugoLogin: Password length:",
        password ? password.length : 0
      );

      // Clear previous errors
      $errorContainer.hide().empty();

      // Validate
      if (!username) {
        showError("Molimo unesite korisničko ime ili email.");
        $usernameField.focus();
        return false;
      }

      if (!password) {
        showError("Molimo unesite lozinku.");
        $passwordField.focus();
        return false;
      }

      // Check if yugoLogin is defined
      if (typeof yugoLogin === "undefined") {
        console.error("YugoLogin: yugoLogin config not found!");
        showError("Greška u konfiguraciji. Osvežite stranicu.");
        return false;
      }

      // Get nonce - try hidden field first, then JS config
      let nonce = yugoLogin.nonce;
      const $nonceField = $form.find('input[name="yugo_login_nonce"]');
      if ($nonceField.length) {
        nonce = $nonceField.val();
        console.log("YugoLogin: Using nonce from hidden field");
      }

      // Disable submit button
      $submitBtn.prop("disabled", true);
      if ($submitBtn.is("button")) {
        $submitBtn.html(
          '<span style="display:inline-flex;align-items:center;gap:8px;"><svg width="16" height="16" viewBox="0 0 24 24" style="animation:spin 1s linear infinite"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" fill="none" stroke-dasharray="32" stroke-linecap="round"/></svg> Prijavljivanje...</span>'
        );
      } else {
        $submitBtn.val("Prijavljivanje...");
      }

      // Get redirect URL from URL params or default
      const urlParams = new URLSearchParams(window.location.search);
      const redirectTo = urlParams.get("redirect_to") || yugoLogin.redirect;

      console.log("YugoLogin: Sending AJAX to", yugoLogin.ajaxurl);

      // AJAX request
      $.ajax({
        url: yugoLogin.ajaxurl,
        type: "POST",
        dataType: "json",
        data: {
          action: "yugo_ajax_login",
          nonce: nonce,
          username: username,
          password: password,
          remember: remember ? "true" : "false",
          redirect_to: redirectTo,
        },
        success: function (response) {
          console.log("YugoLogin: Response", response);

          if (response && response.success) {
            // Show success message
            $errorContainer
              .css({ color: "#27ae60", background: "#eafaf1" })
              .text(response.data.message || "Uspešna prijava!")
              .show();

            // Redirect
            setTimeout(function () {
              window.location.href =
                response.data.redirect || yugoLogin.redirect;
            }, 500);
          } else {
            const msg =
              response && response.data && response.data.message
                ? response.data.message
                : "Prijava neuspešna. Proverite podatke.";
            showError(msg);
            resetButton();
          }
        },
        error: function (xhr, status, error) {
          console.error("YugoLogin: AJAX error", status, error);
          console.error("YugoLogin: Response text", xhr.responseText);

          let errorMsg = "Greška pri komunikaciji sa serverom.";

          // Try to parse error response
          try {
            const resp = JSON.parse(xhr.responseText);
            if (resp && resp.data && resp.data.message) {
              errorMsg = resp.data.message;
            }
          } catch (e) {
            // Not JSON, use default error
          }

          showError(errorMsg);
          resetButton();
        },
      });

      return false;
    });

    function showError(message) {
      $errorContainer
        .css({ color: "#e74c3c", background: "#fdf2f2" })
        .text(message)
        .fadeIn();
    }

    function resetButton() {
      $submitBtn.prop("disabled", false);
      if ($submitBtn.is("button")) {
        $submitBtn.html(originalBtnText);
      } else {
        $submitBtn.val(originalBtnText);
      }
    }
  }

  // Add CSS animation for spinner
  if (!document.getElementById("yugo-login-styles")) {
    const style = document.createElement("style");
    style.id = "yugo-login-styles";
    style.textContent =
      "@keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }";
    document.head.appendChild(style);
  }
})(jQuery);
