jQuery(function($) {
    const phoneFields = [
        { id: "#billing_phone", name: "billing_phone" },
        { id: "#billing_phone_popup", name: "billing_phone_popup" },
        { id: "#login_your_whatsapp", name: "login_your_whatsapp" },
        { id: "#register_your_whatsapp", name: "register_your_whatsapp" },
        { id: "#field_billing_phone", name: "item_meta[13]" } // New field added
    ];

    let utilsScriptLoaded = false;

    function initializeIntlTelInput(phoneField, allowlist, defaultCountry, localizedCountries) {
        if (!allowlist.length) {
            return;
        }

        if (phoneField.data('iti-initialized')) {
            return;
        }

        function geoIpLookup(callback) {
            $.getJSON("https://ipapi.co/jsonp/?callback=?", function(resp) {
                const countryCode = (resp && resp.country) ? resp.country.toLowerCase() : defaultCountry;
                callback(countryCode);
            }).fail(function() {
                callback(defaultCountry);
            });
        }

        const iti = window.intlTelInput(phoneField[0], {
            initialCountry: "auto",
            geoIpLookup: function(success, failure) {
                geoIpLookup(function(countryCode) {
                    if (allowlist.includes(countryCode)) {
                        success(countryCode);
                    } else {
                        success(defaultCountry);
                    }
                });
            },
            onlyCountries: allowlist,
            utilsScript: woocommerceITISettings.utilsScriptUrl,
            separateDialCode: true,
            nationalMode: false,
            formatOnDisplay: true
        });

        iti.promise.then(function() {
            const currentNumber = phoneField.val();
            if (currentNumber && currentNumber.length > 0) {
                iti.setNumber(currentNumber);
                phoneField.trigger('blur.intlTelInput');

                const countryData = iti.getSelectedCountryData();
                if (countryData.iso2) {
                    iti.setCountry(countryData.iso2);
                }
            }
        }).catch(function(error) {
            console.error('Error initializing IntlTelInput:', error);
        });

        phoneField.data('iti-instance', iti);
        phoneField.data('iti-initialized', true);

        function addCountryCodeIfNeeded(phoneField, iti) {
            const countryData = iti.getSelectedCountryData();
            const countryCode = countryData && countryData.dialCode ? countryData.dialCode : '';
            let currentNumber = phoneField.val();

            // Add country code only if it's not already present and not starting with 0
            if (currentNumber && !currentNumber.startsWith(`+${countryCode}`) && !currentNumber.startsWith(countryCode) && !currentNumber.startsWith('0')) {
                currentNumber = `${countryCode}${currentNumber}`;
                phoneField.val(currentNumber);
            }
        }

        function formatNumber(phoneField, iti) {
            const countryData = iti.getSelectedCountryData();
            const countryCode = countryData && countryData.dialCode ? countryData.dialCode : '';
            let currentNumber = phoneField.val();

            // Remove any leading zeros, or the current country code, but keep the "+"
            currentNumber = currentNumber.replace(new RegExp(`^(\\+?${countryCode}|0+|00+)`), '');
            currentNumber = currentNumber.replace(/\s+/g, '');
            currentNumber = currentNumber.replace(/-/g, '');

            // Re-add the country code only if it's not already present
            if (countryCode && !currentNumber.startsWith(countryCode)) {
                currentNumber = `${countryCode}${currentNumber}`;
            }

            phoneField.val(currentNumber);
        }

        // Remove debounce to make changes faster
        phoneField.off('blur.intlTelInput');
        phoneField.off('input.intlTelInput');
        phoneField.off('countrychange.intlTelInput');

        // Add country code immediately when user starts typing if not already present
        phoneField.on('input.intlTelInput', function() {
            addCountryCodeIfNeeded(phoneField, iti);
            formatNumber(phoneField, iti);
        });

        // Final formatting when user clicks outside the phone field
        phoneField.on('blur.intlTelInput', function() {
            formatNumber(phoneField, iti);
        });

        phoneField.on('countrychange.intlTelInput', function() {
            phoneField.val(''); // Clear field when the country changes
        });

        if (phoneField.val()) {
            iti.setNumber(phoneField.val());
        }

        if (woocommerceITISettings.isArabic) {
            setTimeout(() => {
                $('.iti__country-list .iti__country').each(function() {
                    const countryCode = $(this).attr('data-country-code');
                    const arabicName = localizedCountries[countryCode];
                    if (arabicName) {
                        $(this).find('.iti__country-name').text(arabicName);
                    }
                });
            }, 500);
        }

        const placeholder = phoneField.attr('placeholder');
        if (placeholder) {
            phoneField.attr('placeholder', placeholder.replace('+', ''));
        }
    }

    function initializePhoneFields() {
        const allowlist = woocommerceITISettings.allowlist ? woocommerceITISettings.allowlist.split(',') : [];
        const defaultCountry = woocommerceITISettings.default_country || 'us';
        const localizedCountries = woocommerceITISettings.countryNames.reduce((obj, country) => {
            obj[country.iso2] = country.name;
            return obj;
        }, {});

        phoneFields.forEach(fieldConfig => {
            const phoneField = $(fieldConfig.id);
            if (phoneField.length) {
                initializeIntlTelInput(phoneField, allowlist, defaultCountry, localizedCountries);
            }
        });
    }

    function loadIntlTelInputUtils(callback) {
        if (!utilsScriptLoaded) {
            $.getScript(woocommerceITISettings.utilsScriptUrl, function() {
                utilsScriptLoaded = true;
                callback();
            }).fail(function(jqxhr, settings, exception) {
                console.error('Failed to load intlTelInputUtils:', exception);
            });
        } else {
            callback();
        }
    }

    $(document).ready(function() {
        loadIntlTelInputUtils(initializePhoneFields);
    });

    $(document.body).on('updated_checkout', function() {
        loadIntlTelInputUtils(initializePhoneFields);
    });

    $(document).on('click', 'button[type="submit"]', function() {
        setTimeout(() => {
            loadIntlTelInputUtils(initializePhoneFields);
        }, 100);
    });

    const observer = new MutationObserver((mutations) => {
        mutations.forEach((mutation) => {
            if (mutation.addedNodes.length || mutation.removedNodes.length) {
                loadIntlTelInputUtils(initializePhoneFields);
            }
        });
    });

    observer.observe(document.body, {
        childList: true,
        subtree: true,
    });

    $('#change-country').text(awpTranslations.changeCountry);
    $('#country-dialog').attr('title', awpTranslations.selectDefaultCountry);
});
