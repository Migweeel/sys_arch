const hamburger = document.getElementById("hamburger");
const mobileMenu = document.getElementById("mobile-menu");

if (hamburger && mobileMenu) {
    function closeMobileMenu() {
        mobileMenu.classList.remove("show-mobile");
        hamburger.setAttribute("aria-expanded", "false");
        hamburger.setAttribute("aria-label", "Open navigation menu");
    }

    hamburger.addEventListener("click", function () {
        const isOpen = mobileMenu.classList.toggle("show-mobile");
        hamburger.setAttribute("aria-expanded", String(isOpen));
        hamburger.setAttribute("aria-label", isOpen ? "Close navigation menu" : "Open navigation menu");
    });

    document.addEventListener("keydown", function (event) {
        if (event.key === "Escape" && mobileMenu.classList.contains("show-mobile")) {
            closeMobileMenu();
            hamburger.focus();
        }
    });
}

function initCustomSelects() {
    document.querySelectorAll('select[data-custom-select]').forEach((select) => {
        const wrapper = select.closest('.select-wrap');
        if (!wrapper || wrapper.querySelector('.custom-select-button')) {
            return;
        }

        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'custom-select-button';
        button.setAttribute('aria-haspopup', 'listbox');
        button.setAttribute('aria-expanded', 'false');

        const menu = document.createElement('div');
        menu.className = 'custom-select-menu';
        menu.setAttribute('role', 'listbox');

        const updateButtonText = () => {
            const selected = select.options[select.selectedIndex];
            button.innerHTML = '<span>' + (selected ? selected.textContent : '') + '</span>';
            button.setAttribute('aria-label', selected ? selected.textContent : 'Select option');
            Array.from(menu.children).forEach((item) => {
                const isSelected = item.dataset.value === select.value;
                item.classList.toggle('selected', isSelected);
                item.setAttribute('aria-selected', String(isSelected));
            });
        };

        Array.from(select.options).forEach((option) => {
            const item = document.createElement('button');
            item.type = 'button';
            item.className = 'custom-select-option';
            item.dataset.value = option.value;
            item.textContent = option.textContent;
            item.setAttribute('role', 'option');
            item.addEventListener('click', () => {
                select.value = option.value;
                updateButtonText();
                menu.classList.remove('open');
                button.setAttribute('aria-expanded', 'false');
                select.dispatchEvent(new Event('change', { bubbles: true }));
            });
            menu.appendChild(item);
        });

        button.addEventListener('click', (event) => {
            event.stopPropagation();
            const isOpen = menu.classList.toggle('open');
            button.setAttribute('aria-expanded', String(isOpen));
        });

        document.addEventListener('click', (event) => {
            if (!wrapper.contains(event.target)) {
                menu.classList.remove('open');
                button.setAttribute('aria-expanded', 'false');
            }
        });

        wrapper.appendChild(button);
        wrapper.appendChild(menu);
        updateButtonText();
    });
}

function initPrivacyConsent() {
    const consent = document.getElementById('privacy_consent');
    const proceedButton = document.querySelector('.register-btn');

    if (!consent || !proceedButton) {
        return;
    }

    const updateProceedButton = () => {
        proceedButton.disabled = !consent.checked;
    };

    consent.addEventListener('change', updateProceedButton);
    updateProceedButton();
}

document.addEventListener('DOMContentLoaded', () => {
    initCustomSelects();
    initPrivacyConsent();
});