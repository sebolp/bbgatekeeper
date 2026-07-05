document.addEventListener('DOMContentLoaded', function () {
    var saveBtn = document.getElementById('bbgatekeeper-save-btn');
    var modal = document.getElementById('bbgatekeeper-save-modal');
    var confirmBtn = document.getElementById('bbgatekeeper-modal-confirm');
    var cancelBtn = document.getElementById('bbgatekeeper-modal-cancel');

    if (!saveBtn || !modal) {
        return;
    }

    function openModal() {
        modal.style.display = 'flex';
    }

    function closeModal() {
        modal.style.display = 'none';
    }

    saveBtn.addEventListener('click', function (e) {
        e.preventDefault();
        openModal();
    });

    cancelBtn.addEventListener('click', closeModal);

    modal.addEventListener('click', function (e) {
        if (e.target === modal) {
            closeModal();
        }
    });

    confirmBtn.addEventListener('click', function () {
        closeModal();

        if (saveBtn.form.requestSubmit) {
            // Includes name="submit_save" nel POST, come un click reale
            saveBtn.form.requestSubmit(saveBtn);
        } else {
            // Fallback per browser molto vecchi: aggiunge manualmente
            // un campo hidden con lo stesso nome/valore del bottone
            var hidden = document.createElement('input');
            hidden.type = 'hidden';
            hidden.name = saveBtn.name;   // "submit_save"
            hidden.value = saveBtn.value;
            saveBtn.form.appendChild(hidden);
            saveBtn.form.submit();
        }
    });
});