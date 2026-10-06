// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Client-side behaviour for the auth_moowoodle setup wizard.
 *
 * @module     auth_moowoodle/setup_wizard
 * @copyright  2026 DualCube Team(https://dualcube.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

let copyButtonsRegistered = false;

/**
 * Register the click handler for all copy-to-clipboard buttons on the page. Safe to
 * load on every step: it's a single delegated click listener, and does nothing unless
 * a ".auth-moowoodle-copy" button is actually present on the page. Safe to call more
 * than once; only the first call attaches the listener.
 *
 * A trigger button needs class "auth-moowoodle-copy", a "data-copy-target" attribute
 * naming the id of the element to copy from, and a "data-copied-label" attribute with
 * the translated "Copied" text to show for 2 seconds after a successful copy.
 */
export const initCopyButtons = () => {
    if (copyButtonsRegistered) {
        return;
    }
    copyButtonsRegistered = true;

    document.addEventListener('click', (event) => {
        const button = event.target.closest('.auth-moowoodle-copy');
        if (!button) {
            return;
        }

        const target = document.getElementById(button.getAttribute('data-copy-target'));
        if (!target) {
            return;
        }

        const text = 'value' in target ? target.value : target.textContent;

        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text);
        } else {
            target.select();
            document.execCommand('copy');
        }

        if (!button.dataset.originalLabel) {
            button.dataset.originalLabel = button.textContent;
        }

        clearTimeout(button.moowoodleCopyTimeout);
        button.textContent = button.dataset.copiedLabel;
        button.moowoodleCopyTimeout = setTimeout(() => {
            button.textContent = button.dataset.originalLabel;
        }, 2000);
    });
};

/**
 * Refreshes the Web Service step's Token list when the service or user dropdown
 * changes, via a small JSON fetch, instead of reloading the page. No page navigation
 * means no "leave this page?" prompt from Moodle's unsaved-changes warning.
 *
 * The token <select> must carry a "data-placeholder" attribute with the translated
 * placeholder text, since its options are fully replaced on every refresh.
 *
 * @param {string} ajaxurl URL of wizard_ajax.php.
 */
export const initWebserviceStep = (ajaxurl) => {
    const serviceSelect = document.getElementById('auth_moowoodle_serviceid');
    const userSelect = document.getElementById('id_userid');
    const tokenSelect = document.getElementById('auth_moowoodle_token');
    const button = document.getElementById('id_updateservice');

    if (!serviceSelect || !tokenSelect) {
        return;
    }

    const tokenPlaceholder = tokenSelect.dataset.placeholder || '';

    const refreshTokens = () => {
        if (serviceSelect.value === '') {
            return;
        }

        const params = new URLSearchParams({
            sesskey: M.cfg.sesskey,
            serviceid: serviceSelect.value,
            userid: userSelect ? userSelect.value : 0,
        });

        fetch(ajaxurl + '?' + params.toString(), {credentials: 'same-origin'})
            .then((response) => response.json())
            .then((data) => {
                tokenSelect.innerHTML = '';

                const placeholder = document.createElement('option');
                placeholder.value = '';
                placeholder.textContent = tokenPlaceholder;
                tokenSelect.appendChild(placeholder);

                Object.keys(data.tokens).forEach((token) => {
                    const option = document.createElement('option');
                    option.value = token;
                    option.textContent = data.tokens[token];
                    option.selected = (token === data.selectedtoken);
                    tokenSelect.appendChild(option);
                });

                if (button) {
                    button.value = data.buttonlabel;
                }

                return;
            })
            .catch(() => {
                // Leave the current token list as-is on a network error.
            });
    };

    serviceSelect.addEventListener('change', refreshTokens);
    if (userSelect) {
        userSelect.addEventListener('change', refreshTokens);
    }
};
