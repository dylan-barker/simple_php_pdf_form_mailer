/* ---------------------------------------------------------------------------
   Form behaviour: client-side validation and AJAX submission.

   The server is always the source of truth. This script only mirrors its rules
   so people get feedback without a round trip.
   --------------------------------------------------------------------------- */

(function () {
    'use strict';

    var form = document.getElementById('details-form');
    if (!form) {
        return;
    }

    var csrfInput = form.querySelector('input[name="csrf_token"]');
    var statusEl = document.getElementById('form-status');
    var successPanel = document.getElementById('success-panel');
    var successHeading = document.getElementById('success-heading');
    var successMessage = document.getElementById('success-message');
    var submitButton = document.getElementById('submit-button');
    var resetButton = document.getElementById('reset-button');
    var dobInput = document.getElementById('date_of_birth');

    var NAME_PATTERN = /^[\p{L}\p{M}][\p{L}\p{M}' -]*$/u;
    var MIN_DATE = '1900-01-01';

    var todayIso = (function () {
        var now = new Date();
        var month = String(now.getMonth() + 1).padStart(2, '0');
        var day = String(now.getDate()).padStart(2, '0');
        return now.getFullYear() + '-' + month + '-' + day;
    })();

    if (dobInput) {
        dobInput.max = todayIso;
    }

    function fieldWrapper(name) {
        return form.querySelector('[data-field="' + name + '"]');
    }

    function errorElement(name) {
        return form.querySelector('[data-error-for="' + name + '"]');
    }

    function clearError(name) {
        var wrapper = fieldWrapper(name);
        var error = errorElement(name);
        var input = form.elements[name];

        if (wrapper) {
            wrapper.classList.remove('has-error');
        }
        if (error) {
            error.textContent = '';
        }
        if (input && input.setAttribute) {
            input.removeAttribute('aria-invalid');
        }
    }

    function showError(name, message) {
        var wrapper = fieldWrapper(name);
        var error = errorElement(name);
        var input = form.elements[name];

        if (wrapper) {
            wrapper.classList.add('has-error');
        }
        if (error) {
            error.textContent = message;
        }
        if (input && input.setAttribute) {
            input.setAttribute('aria-invalid', 'true');
        }
    }

    function validateField(name, rawValue) {
        var value = typeof rawValue === 'string' ? rawValue : '';

        if (name === 'first_name' || name === 'surname') {
            var label = name === 'first_name' ? 'first name' : 'surname';
            var cleaned = value.trim().replace(/\s+/g, ' ');

            if (cleaned === '') {
                return 'Please enter your ' + label + '.';
            }
            if (cleaned.length > 60) {
                return 'Please keep your ' + label + ' under 60 characters.';
            }
            if (!NAME_PATTERN.test(cleaned)) {
                return 'Please use letters, spaces, hyphens or apostrophes only.';
            }
            return '';
        }

        if (name === 'date_of_birth') {
            if (value === '') {
                return 'Please enter your date of birth.';
            }
            if (!/^\d{4}-\d{2}-\d{2}$/.test(value)) {
                return 'Please use the date picker to choose your date of birth.';
            }
            if (value < MIN_DATE) {
                return 'Please enter a date on or after 1 January 1900.';
            }
            if (value > todayIso) {
                return 'Date of birth cannot be in the future.';
            }
            return '';
        }

        return '';
    }

    function validateForm() {
        var names = ['first_name', 'surname', 'date_of_birth'];
        var firstInvalid = null;

        names.forEach(function (name) {
            var input = form.elements[name];
            var message = validateField(name, input ? input.value : '');

            if (message !== '') {
                showError(name, message);
                if (firstInvalid === null) {
                    firstInvalid = input;
                }
            } else {
                clearError(name);
            }
        });

        return firstInvalid;
    }

    function setLoading(isLoading) {
        if (!submitButton) {
            return;
        }

        submitButton.disabled = isLoading;
        submitButton.classList.toggle('is-loading', isLoading);

        var label = submitButton.querySelector('.btn-label');
        if (label) {
            label.textContent = isLoading ? 'Sending\u2026' : 'Send details';
        }
    }

    function setStatus(message, isError) {
        if (!statusEl) {
            return;
        }

        statusEl.textContent = message;
        statusEl.classList.remove('status-error', 'status-success');
        if (message !== '') {
            statusEl.classList.add(isError ? 'status-error' : 'status-success');
        }
    }

    function showFieldErrors(errors) {
        Object.keys(errors).forEach(function (name) {
            showError(name, String(errors[name]));
        });

        var firstField = Object.keys(errors)[0];
        var input = firstField ? form.elements[firstField] : null;
        if (input && typeof input.focus === 'function') {
            input.focus();
        }
    }

    function showSuccess(message, token) {
        setStatus('', false);
        form.hidden = true;

        if (successMessage) {
            successMessage.textContent = message;
        }
        if (successPanel) {
            successPanel.hidden = false;
        }
        if (successHeading) {
            successHeading.focus();
        }

        if (typeof token === 'string' && token !== '' && csrfInput) {
            csrfInput.value = token;
            var meta = document.querySelector('meta[name="csrf-token"]');
            if (meta) {
                meta.setAttribute('content', token);
            }
        }
    }

    function resetForm() {
        form.reset();
        ['first_name', 'surname', 'date_of_birth'].forEach(clearError);
        setStatus('', false);

        if (successPanel) {
            successPanel.hidden = true;
        }
        form.hidden = false;

        var first = form.elements.first_name;
        if (first && typeof first.focus === 'function') {
            first.focus();
        }
    }

    form.addEventListener('submit', function (event) {
        event.preventDefault();

        setStatus('', false);

        var firstInvalid = validateForm();
        if (firstInvalid) {
            if (typeof firstInvalid.focus === 'function') {
                firstInvalid.focus();
            }
            return;
        }

        setLoading(true);

        var request;
        try {
            request = fetch(form.action, {
                method: 'POST',
                body: new FormData(form),
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                credentials: 'same-origin'
            });
        } catch (error) {
            setLoading(false);
            setStatus('We could not reach the server. Please try again.', true);
            return;
        }

        request
            .then(function (response) {
                return response.json().catch(function () {
                    return null;
                }).then(function (payload) {
                    return { response: response, payload: payload };
                });
            })
            .then(function (result) {
                var response = result.response;
                var payload = result.payload;

                setLoading(false);

                if (response.ok && payload && payload.ok) {
                    showSuccess(
                        payload.message || 'Thank you. Your details have been sent.',
                        payload.token
                    );
                    return;
                }

                if (payload && payload.errors) {
                    showFieldErrors(payload.errors);
                    setStatus(payload.message || 'Please check the highlighted fields.', true);
                    return;
                }

                if (response.status === 419) {
                    setStatus(
                        'Your session has expired. Please reload the page and try again.',
                        true
                    );
                    return;
                }

                setStatus(
                    (payload && payload.message) ||
                        'Sorry, something went wrong. Please try again in a few minutes.',
                    true
                );
            })
            .catch(function () {
                setLoading(false);
                setStatus('We could not reach the server. Please try again.', true);
            });
    });

    ['first_name', 'surname', 'date_of_birth'].forEach(function (name) {
        var input = form.elements[name];
        if (!input) {
            return;
        }

        var handler = function () {
            if (input.getAttribute('aria-invalid') === 'true') {
                var message = validateField(name, input.value);
                if (message === '') {
                    clearError(name);
                }
            }
        };

        input.addEventListener('input', handler);
        input.addEventListener('change', handler);
    });

    if (resetButton) {
        resetButton.addEventListener('click', resetForm);
    }
})();
