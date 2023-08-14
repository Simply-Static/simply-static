'use strict';

let form_redirect_element = document.querySelector("meta[name='ssh-thank-you-path']");

function success(el, redirect_url) {
    el.target.submit.disabled = false;

    if (el.target.querySelector('input[type="submit"]')) {
        el.target.querySelector('input[type="submit"]').blur();
    }

    el.target.reset();

    // Redirect if set
    if (redirect_url.length > 0) {
        window.location.replace(redirect_url);
    }
}

function submitForm(method, url, redirect_url, data, el) {
    var xhr = new XMLHttpRequest();

    xhr.open(method, url);
    xhr.onreadystatechange = function () {
        if (xhr.readyState !== XMLHttpRequest.DONE) return;

        if (xhr.status == 200) {
            if (redirect_url !== false) {
                success(el, redirect_url);
            } else {
                return true;
            }
        }
    };

    xhr.send(data);
}

function modifyFormAttributes(form) {
    form.removeAttribute("action");
    form.removeAttribute("method");
    form.removeAttribute("enctype");
    form.removeAttribute("novalidate");
}

document.addEventListener("DOMContentLoaded", function () {
    const allForms = document.querySelectorAll(
        ".wpcf7 form, .wpcf7-form, .gform_wrapper form, .wpforms-container form, .elementor-form"
    );

    allForms.forEach((form) => {
        modifyFormAttributes(form);

        // Inputs
        const inputs = form.querySelectorAll("input");

        // Add HTML required attribute
        inputs.forEach((input) => {
            if (input.getAttribute("aria-required") === "true") {
                input.required = true;
            }
        });

        form.addEventListener("submit", function (el) {
            el.preventDefault();

            var data = new FormData(form);

            if (null !== form_redirect_element) {
                let redirect_path = form_redirect_element.getAttribute("content");
                let redirect_url = window.location.origin + redirect_path;

                submitForm("POST", 'https://simplycdn.io?mailme=true', redirect_url, data, el);

            } else {
                submitForm("POST", 'https://simplycdn.io?mailme=true', false, data, el);
            }
        });
    });
});