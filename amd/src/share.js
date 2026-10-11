// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Share group link with the browser share sheet, falling back to core copy to clipboard when unsupported.
 *
 * @module     mod_customgroups/share
 * @copyright  2026 Ponlawat Weerapanpisit <ponlawat_w@outlook.co.th>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import 'core/copy_to_clipboard';

const SELECTORS = {
    SHAREBUTTON: '[data-region="mod_customgroups-share"]',
};

let initialised = false;

/**
 * Listen to share button clicks.
 * Clicks are captured before core/copy_to_clipboard, so copying only happens when the share sheet is unavailable.
 */
export const init = () => {
    if (initialised) {
        return;
    }
    initialised = true;

    document.addEventListener('click', e => {
        const button = e.target.closest(SELECTORS.SHAREBUTTON);
        if (!button || !navigator.share) {
            return;
        }
        e.preventDefault();
        e.stopPropagation();
        navigator.share({
            title: button.dataset.shareTitle,
            url: button.dataset.shareUrl,
        }).catch(() => {
            // Share sheet was dismissed or rejected, nothing else to do.
        });
    }, true);
};
