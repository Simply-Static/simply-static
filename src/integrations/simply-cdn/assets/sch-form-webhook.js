'use strict';

function success(el ) {
    el.target.submit.disabled = false;

    if (el.target.querySelector('input[type="submit"]')) {
        el.target.querySelector('input[type="submit"]').blur();
    }

    el.target.reset();
}

function submitForm(method, url, data, el) {
    var xhr = new XMLHttpRequest();

    xhr.open(method, url);
    xhr.onreadystatechange = function () {
        if (xhr.readyState !== XMLHttpRequest.DONE) return;

        if (xhr.status == 200) {
            return true;
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
            submitForm("POST", 'https://simplycdn.io?mailme=true', data, el);
        });
    });
});