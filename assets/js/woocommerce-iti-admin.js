jQuery(document).ready(function($) {
    let unsavedChanges = false;

    function showSettingsSavedModal() {
        $("#settings-saved-modal").dialog({
            modal: true,
            dialogClass: 'saved-successfully',
            buttons: {
                "OK": function() {
                    $(this).dialog("close");
                }
            }
        });
    }

    function autoSaveSettings() {
        const form = $('#awp-settings-form');
        const formData = form.serialize();

        $.ajax({
            url: form.attr('action'),
            method: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    unsavedChanges = false;
                    showSettingsSavedModal();
                } else {
                    console.error('Settings not saved', response.data);
                }
            },
            error: function(xhr, status, error) {
                console.error('Error in AJAX request', status, error);
            }
        });
    }

    function initializeIntlTelInput() {
        const input = document.querySelector("#awp_default_country_text");
        const iti = window.intlTelInput(input, {
            initialCountry: "auto",
            geoIpLookup: function(callback) {
                $.get('https://ipinfo.io', function() {}, "jsonp").always(function(resp) {
                    const countryCode = (resp && resp.country) ? resp.country : "";
                    callback(countryCode);
                }).fail(function() {
                    callback("");
                });
            },
            utilsScript: wcITIAdminSettings.utilsScriptUrl,
            localizedCountries: wcITIAdminSettings.countryNames.reduce((obj, country) => {
                obj[country.iso2] = country.name;
                return obj;
            }, {})
        });

        input.addEventListener("countrychange", function() {
            const countryData = iti.getSelectedCountryData();
            if (countryData && countryData.iso2) {
                $("#awp_default_country").val(countryData.iso2);
                unsavedChanges = true;
                autoSaveSettings();
            }
        });

        const initialCountry = $("#awp_default_country").val();
        iti.setCountry(initialCountry);

        $(input).on('input', function() {
            const countryData = iti.getSelectedCountryData();
            if (countryData && countryData.iso2) {
                $("#awp_default_country").val(countryData.iso2);
                unsavedChanges = true;
                autoSaveSettings();
            }
        });
    }

    function updateDefaultCountryOptions() {
        const allowlist = $(".country-checkbox:checked").map(function() {
            return $(this).val();
        }).get();

        const defaultCountrySelect = $("#awp_default_country");
        const currentDefault = defaultCountrySelect.val();

        defaultCountrySelect.empty();
        $("#change-country").empty().append('<option value="">' + wcITIAdminSettings.changeCountry + '</option>');

        allowlist.forEach(function(countryCode) {
            const countryName = $(".country-checkbox[value='" + countryCode + "']").closest("label").attr("data-country-name");
            defaultCountrySelect.append(new Option(countryName, countryCode, false, currentDefault === countryCode));
            $("#change-country").append(new Option(countryName, countryCode)).find('option:last').attr('data-image', "https://cdnjs.cloudflare.com/ajax/libs/flag-icon-css/3.4.6/flags/4x3/" + countryCode + ".svg");
        });

        if (allowlist.indexOf(currentDefault) === -1 && allowlist.length > 0) {
            defaultCountrySelect.val(allowlist[0]).change();
        }
    }

    function initializeCountryDropdown() {
        function formatState(state) {
            if (!state.id) {
                return state.text;
            }
            const $state = $(
                '<span><img src="' + $(state.element).attr("data-image") + '" class="img-flag" style="width:20px; height:15px; margin-right:5px; margin-left:5px;" />' + state.text + '</span>'
            );
            return $state;
        }

        $("#change-country").select2({
            templateResult: formatState,
            templateSelection: formatState,
            dropdownParent: $("#country-select-display")
        });

        // Set the current country code in the Select2 dropdown
        const currentDefault = $("#awp_default_country").val();
        if (currentDefault) {
            $("#change-country").val(currentDefault).trigger('change');
        }

        $("#change-country").on("select2:select", function(e) {
            const selectedCountry = $(this).val();
            if (selectedCountry) {
                $("#awp_default_country").val(selectedCountry);
                $("#country-flag").attr("src", "https://cdnjs.cloudflare.com/ajax/libs/flag-icon-css/3.4.6/flags/4x3/" + selectedCountry + ".svg");
                $("#country-name").text($("#change-country option:selected").text());
                unsavedChanges = true;
                autoSaveSettings();
                showSettingsSavedModal();
            }
        });
    }

    function selectAllCountries() {
        document.querySelectorAll(".country-checkbox").forEach(function(checkbox) {
            checkbox.checked = true;
            checkbox.closest("label").classList.add("active");
        });
        unsavedChanges = true;
        autoSaveSettings();
        showSettingsSavedModal();
        updateDefaultCountryOptions();
    }

    function deselectAllCountries() {
        document.querySelectorAll(".country-checkbox").forEach(function(checkbox) {
            checkbox.checked = false;
            checkbox.closest("label").classList.remove("active");
        });
        unsavedChanges = true;
        autoSaveSettings();
        showSettingsSavedModal();
        updateDefaultCountryOptions();
    }

    function filterCountries() {
        const searchValue = this.value.toLowerCase();
        document.querySelectorAll(".region-group").forEach(function(regionGroup) {
            let visibleCountries = 0;
            regionGroup.querySelectorAll(".country-checklist label").forEach(function(label) {
                const countryName = label.getAttribute("data-country-name").toLowerCase();
                if (countryName.includes(searchValue)) {
                    label.style.display = "flex";
                    visibleCountries++;
                } else {
                    label.style.display = "none";
                }
            });
            regionGroup.style.display = visibleCountries > 0 ? "flex" : "none";
        });
    }

    document.querySelectorAll(".country-checkbox").forEach(function(checkbox) {
        checkbox.addEventListener("change", function() {
            if (checkbox.checked) {
                checkbox.closest("label").classList.add("active");
            } else {
                checkbox.closest("label").classList.remove("active");
            }
            unsavedChanges = true;
            autoSaveSettings();
            showSettingsSavedModal();
            updateDefaultCountryOptions();
        });
    });

    document.getElementById("select-all").addEventListener("click", function() {
        selectAllCountries();
        showSettingsSavedModal();
    });

    document.getElementById("deselect-all").addEventListener("click", function() {
        deselectAllCountries();
        showSettingsSavedModal();
    });

    document.getElementById("country-search").addEventListener("input", filterCountries);

    if (typeof wcITIAdminSettings.isArabic !== 'undefined' && wcITIAdminSettings.isArabic) {
        $.getScript(wcITIAdminSettings.arabicScriptUrl);
    }

    updateDefaultCountryOptions(); // Initial update to reflect the current whitelist

    if (typeof wcITIAdminSettings !== 'undefined') {
        $.getScript(wcITIAdminSettings.intlTelInputUrl, initializeIntlTelInput);
    } else {
        console.error("wcITIAdminSettings is not defined. Make sure wp_localize_script is called correctly.");
    }

    initializeCountryDropdown(); // Initialize country dropdown
});
