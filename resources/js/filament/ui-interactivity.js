/**
 * Prevent stale Filament modal overlays from blocking header controls.
 * Runs only after SPA navigation completes — never during Livewire commits
 * (that was breaking project save redirects for PM/PD).
 */

const ENABLED = document.documentElement.dataset.consistentButtons !== 'false';

function closeAllFilamentModals() {
    document.querySelectorAll('[data-fi-modal-id]').forEach((modal) => {
        const id = modal.getAttribute('data-fi-modal-id') ?? modal.id;

        if (! id || modal.classList.contains('fi-modal-open')) {
            return;
        }

        window.dispatchEvent(
            new CustomEvent('close-modal-quietly', {
                detail: { id },
            }),
        );
    });
}

function removeOrphanedFloatingLayers() {
    document.querySelectorAll('[data-tippy-root]').forEach((element) => {
        if (! element.closest('.fi-modal-open')) {
            element.remove();
        }
    });
}

function unlockDocumentScroll() {
    document.documentElement.style.removeProperty('overflow');
    document.body.style.removeProperty('overflow');
    document.documentElement.classList.remove('overflow-hidden');
    document.body.classList.remove('overflow-hidden');
}

export function restoreHeaderInteractivity() {
    if (! ENABLED) {
        return;
    }

    closeAllFilamentModals();
    removeOrphanedFloatingLayers();
    unlockDocumentScroll();
}

function scheduleRestore() {
    window.setTimeout(restoreHeaderInteractivity, 150);
}

function registerLivewireHooks() {
    if (! window.Livewire) {
        return;
    }

    window.Livewire.on('restore-header-interactivity', scheduleRestore);
}

document.addEventListener('livewire:init', registerLivewireHooks);
document.addEventListener('livewire:navigated', scheduleRestore);

window.restoreHeaderInteractivity = restoreHeaderInteractivity;
