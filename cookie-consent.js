(function () {
    "use strict";

    const STORAGE_KEY = "knl-cookie-preferences";
    const POLICY_VERSION = "2026-10-06";
    const categories = ["analytics", "advertising", "functional"];
    // Register optional integrations here only; category scripts load after consent.
    const scriptsByCategory = {
        analytics: [],
        advertising: [],
        functional: []
    };
    const banner = document.getElementById("cookie-banner");
    const dialog = document.getElementById("cookie-dialog");
    const bannerStatus = document.getElementById("cookie-status");
    const dialogStatus = document.querySelector("[data-dialog-cookie-status]");
    const preferenceForm = dialog ? dialog.querySelector("form") : null;
    const inputs = {};
    let savedChoice = null;
    let settingsTrigger = null;

    categories.forEach(function (category) {
        inputs[category] = preferenceForm
            ? preferenceForm.querySelector('input[name="' + category + '"]')
            : null;
    });

    function defaultChoice() {
        return {
            analytics: false,
            advertising: false,
            functional: false
        };
    }

    function isValidChoice(value) {
        return value &&
            value.version === POLICY_VERSION &&
            value.choices &&
            categories.every(function (category) {
                return typeof value.choices[category] === "boolean";
            }) &&
            typeof value.timestamp === "string";
    }

    function browserOptOut() {
        return navigator.globalPrivacyControl === true;
    }

    function applyBrowserSignals(choice) {
        const result = Object.assign({}, choice);
        if (browserOptOut()) {
            result.advertising = false;
        }
        return result;
    }

    function showStatus(message) {
        if (bannerStatus) {
            bannerStatus.textContent = message;
        }
        if (dialogStatus) {
            dialogStatus.textContent = message;
        }
    }

    function updateConsentCookie(record) {
        const secure = window.location.protocol === "https:" ? "; Secure" : "";
        const cookieName = encodeURIComponent(STORAGE_KEY);
        document.cookie = cookieName + "=" +
            encodeURIComponent(JSON.stringify(record)) +
            "; Max-Age=15552000; Path=/; SameSite=Lax" + secure;
        return document.cookie.split(";").some(function (cookie) {
            return cookie.trim().indexOf(cookieName + "=") === 0;
        });
    }

    function clearConsentCookie() {
        const secure = window.location.protocol === "https:" ? "; Secure" : "";
        document.cookie = encodeURIComponent(STORAGE_KEY) +
            "=; Max-Age=0; Path=/; SameSite=Lax" + secure;
    }

    function clearFunctionalCookies() {
        ["remember_username", "remember_role"].forEach(function (name) {
            const secure = window.location.protocol === "https:" ? "; Secure" : "";
            document.cookie = name + "=; Max-Age=0; Path=/; SameSite=Lax" + secure;
        });
    }

    function readChoice() {
        try {
            const value = JSON.parse(window.localStorage.getItem(STORAGE_KEY) || "null");
            return isValidChoice(value) ? value : null;
        } catch (error) {
            showStatus("Cookie preferences could not be read from this browser. Optional categories will stay off.");
            return null;
        }
    }

    function updateScriptState(choice) {
        categories.forEach(function (category) {
            const allowed = Boolean(choice && choice[category]) &&
                !(category === "advertising" && browserOptOut());
            const existing = document.querySelectorAll(
                'script[data-optional-category="' + category + '"]'
            );

            if (!allowed) {
                existing.forEach(function (script) {
                    script.remove();
                });
                return;
            }

            scriptsByCategory[category].forEach(function (definition) {
                const alreadyLoaded = Array.from(existing).some(function (script) {
                    return script.src === new URL(definition.src, document.baseURI).href;
                });
                if (alreadyLoaded) {
                    return;
                }

                const script = document.createElement("script");
                script.src = definition.src;
                script.async = true;
                script.dataset.optionalCategory = category;
                if (definition.integrity) {
                    script.integrity = definition.integrity;
                    script.crossOrigin = "anonymous";
                }
                if (definition.referrerPolicy) {
                    script.referrerPolicy = definition.referrerPolicy;
                }
                script.addEventListener("error", function () {
                    showStatus("An optional service could not be loaded. Essential site features remain available.");
                });
                document.head.appendChild(script);
            });
        });
    }

    function saveChoice(choice) {
        const effectiveChoice = applyBrowserSignals(choice);
        const record = {
            choices: effectiveChoice,
            timestamp: new Date().toISOString(),
            version: POLICY_VERSION
        };

        try {
            window.localStorage.setItem(STORAGE_KEY, JSON.stringify(record));
        } catch (error) {
            savedChoice = null;
            updateScriptState(defaultChoice());
            clearConsentCookie();
            clearFunctionalCookies();
            let previousChoiceRemoved = false;
            try {
                window.localStorage.removeItem(STORAGE_KEY);
                previousChoiceRemoved = true;
            } catch (removeError) {
                previousChoiceRemoved = false;
            }
            showStatus(previousChoiceRemoved
                ? "Your choice could not be saved. Optional categories are off; enable browser storage to save preferences."
                : "Your choice could not be saved. Optional categories are off for this page; clear this site’s browser data to remove any earlier saved choice.");
            return false;
        }

        const preferenceCookieSaved = updateConsentCookie(record);
        if (!record.choices.functional) {
            clearFunctionalCookies();
        }
        savedChoice = record;
        updateScriptState(record.choices);
        if (banner) {
            banner.hidden = true;
        }
        showStatus(!preferenceCookieSaved && record.choices.functional
            ? "Preferences are saved in this browser, but browser cookies are blocked, so the optional remembered username and role feature cannot be used."
            : browserOptOut() && choice.advertising
            ? "Preferences saved. Advertising and marketing remains off because this browser signals an opt-out."
            : "Cookie preferences saved.");
        return true;
    }

    function openSettings(event) {
        if (!dialog || typeof dialog.showModal !== "function") {
            if (banner) {
                banner.hidden = false;
            }
            showStatus("Cookie preferences are unavailable in this browser. Optional categories remain off.");
            return;
        }

        settingsTrigger = event && event.currentTarget ? event.currentTarget : document.activeElement;
        const current = savedChoice ? savedChoice.choices : defaultChoice();
        categories.forEach(function (category) {
            if (inputs[category]) {
                inputs[category].checked = Boolean(current[category]) &&
                    !(category === "advertising" && browserOptOut());
            }
        });
        showStatus("");
        dialog.showModal();
    }

    function closeSettings() {
        if (dialog && dialog.open) {
            dialog.close();
        }
    }

    function restoreSettingsFocus(trigger) {
        settingsTrigger = null;
        if (trigger && typeof trigger.focus === "function") {
            window.setTimeout(function () {
                trigger.focus();
            }, 0);
        }
    }

    document.querySelectorAll("[data-open-cookie-settings]").forEach(function (button) {
        button.addEventListener("click", openSettings);
    });

    document.querySelectorAll("[data-consent-accept]").forEach(function (button) {
        button.addEventListener("click", function () {
            const choice = {
                analytics: true,
                advertising: true,
                functional: true
            };
            if (saveChoice(choice)) {
                closeSettings();
            }
        });
    });

    document.querySelectorAll("[data-consent-reject]").forEach(function (button) {
        button.addEventListener("click", function () {
            if (saveChoice(defaultChoice())) {
                closeSettings();
            }
        });
    });

    const saveButton = document.querySelector("[data-consent-save]");
    if (saveButton) {
        saveButton.addEventListener("click", function () {
            const choice = {};
            categories.forEach(function (category) {
                choice[category] = Boolean(inputs[category] && inputs[category].checked);
            });
            if (saveChoice(choice)) {
                closeSettings();
            }
        });
    }

    if (dialog) {
        dialog.addEventListener("cancel", function (event) {
            event.preventDefault();
            const trigger = settingsTrigger;
            dialog.close();
            restoreSettingsFocus(trigger);
        });
        dialog.addEventListener("click", function (event) {
            if (event.target === dialog) {
                dialog.close();
            }
        });
        dialog.addEventListener("close", function () {
            const trigger = settingsTrigger;
            restoreSettingsFocus(trigger);
        });
    }

    document.addEventListener("keydown", function (event) {
        if (event.key === "Escape" && dialog && dialog.open) {
            event.preventDefault();
            const trigger = settingsTrigger;
            closeSettings();
            restoreSettingsFocus(trigger);
        }
    });

    savedChoice = readChoice();
    const signalAdjusted = savedChoice
        ? applyBrowserSignals(savedChoice.choices)
        : defaultChoice();
    updateScriptState(signalAdjusted);

    if (savedChoice) {
        updateConsentCookie({
            choices: signalAdjusted,
            timestamp: savedChoice.timestamp,
            version: POLICY_VERSION
        });
    } else {
        clearConsentCookie();
        clearFunctionalCookies();
    }

    if (savedChoice && browserOptOut() &&
        savedChoice.choices.advertising !== signalAdjusted.advertising) {
        saveChoice(signalAdjusted);
    }

    if (!savedChoice && banner) {
        banner.hidden = false;
    }

    window.SiteConsent = {
        hasConsent: function (category) {
            return categories.indexOf(category) !== -1 &&
                Boolean(savedChoice && savedChoice.choices[category]) &&
                !(category === "advertising" && browserOptOut());
        },
        policyVersion: POLICY_VERSION,
        openSettings: function () {
            openSettings();
        }
    };
}());
