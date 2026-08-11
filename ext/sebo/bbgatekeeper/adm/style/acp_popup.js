document.addEventListener('DOMContentLoaded', function () {
	function setupModal(triggerId, modalId, confirmId, cancelId, onConfirm)
	{
		var triggerBtn = document.getElementById(triggerId);
		var modal = document.getElementById(modalId);
		var confirmBtn = document.getElementById(confirmId);
		var cancelBtn = document.getElementById(cancelId);

		if (!triggerBtn || !modal || !confirmBtn || !cancelBtn)
		{
			return;
		}

		function openModal()
		{
			modal.style.display = 'flex';
		}

		function closeModal()
		{
			modal.style.display = 'none';
		}

		triggerBtn.addEventListener('click', function (e) {
			e.preventDefault();
			openModal();
		});

		cancelBtn.addEventListener('click', closeModal);

		modal.addEventListener('click', function (e) {
			if (e.target === modal)
			{
				closeModal();
			}
		});

		confirmBtn.addEventListener('click', function () {
			closeModal();
			onConfirm(triggerBtn);
		});
	}

	// Save settings modal
	setupModal(
		'bbgatekeeper-save-btn',
		'bbgatekeeper-save-modal',
		'bbgatekeeper-save-modal-confirm',
		'bbgatekeeper-save-modal-cancel',
		function (saveBtn) {
			if (saveBtn.form.requestSubmit)
			{
				// Includes name="submit_save" in the POST, same as a real click
				saveBtn.form.requestSubmit(saveBtn);
			}
			else
			{
				// Fallback for very old browsers: manually add a hidden field
				// with the same name/value as the button
				var hidden = document.createElement('input');
				hidden.type = 'hidden';
				hidden.name = saveBtn.name;
				hidden.value = saveBtn.value;
				saveBtn.form.appendChild(hidden);
				saveBtn.form.submit();
			}
		}
	);

	// Download package modal — the callback now correctly receives the
	// triggering button instead of relying on an out-of-scope variable
	setupModal(
		'bbgatekeeper-download-btn',
		'bbgatekeeper-download-modal',
		'bbgatekeeper-download-modal-confirm',
		'bbgatekeeper-download-modal-cancel',
		function (downloadBtn) {
			var downloadUrl = downloadBtn.getAttribute('data-url');
			if (downloadUrl)
			{
				window.location.href = downloadUrl;
			}
		}
	);
});