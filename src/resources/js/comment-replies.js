document.addEventListener('click', function (event) {
    const toggle = event.target.closest('[data-comment-reply-toggle]');
    const cancel = event.target.closest('.add-comment-cancel');

    if (toggle) {
        const targetId = toggle.getAttribute('data-reply-target');
        const slot = document.getElementById(targetId);

        if (!slot) {
            return;
        }

        const willOpen = slot.hidden;

        document.querySelectorAll('.comment-reply-slot').forEach(function (replySlot) {
            replySlot.hidden = true;
        });

        document.querySelectorAll('[data-comment-reply-toggle]').forEach(function (replyToggle) {
            replyToggle.setAttribute('aria-expanded', 'false');
        });

        slot.hidden = !willOpen;
        toggle.setAttribute('aria-expanded', willOpen ? 'true' : 'false');

        if (willOpen) {
            const textarea = slot.querySelector('textarea');

            if (textarea) {
                textarea.focus();
            }
        }
    }

    if (cancel) {
        const slot = cancel.closest('.comment-reply-slot');

        if (!slot) {
            return;
        }

        const toggle = document.querySelector('[data-reply-target="' + slot.id + '"]');
        slot.hidden = true;

        if (toggle) {
            toggle.setAttribute('aria-expanded', 'false');
        }
    }
});
