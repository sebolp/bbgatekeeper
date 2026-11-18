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

	// Deploy settings modal
	setupModal(
		'bbgatekeeper-deploy-btn',
		'bbgatekeeper-deploy-modal',
		'bbgatekeeper-deploy-modal-confirm',
		'bbgatekeeper-deploy-modal-cancel',
		function (deployBtn) {
			if (deployBtn.form.requestSubmit)
			{
				// Includes name="submit_deploy" in the POST, same as a real click
				deployBtn.form.requestSubmit(deployBtn);
			}
			else
			{
				// Fallback for very old browsers: manually add a hidden field
				// with the same name/value as the button
				var hidden = document.createElement('input');
				hidden.type = 'hidden';
				hidden.name = deployBtn.name;
				hidden.value = deployBtn.value;
				deployBtn.form.appendChild(hidden);
				deployBtn.form.submit();
			}
		}
	);

	// Reset settings modal
	setupModal(
		'bbgatekeeper-reset-btn',
		'bbgatekeeper-reset-modal',
		'bbgatekeeper-reset-modal-confirm',
		'bbgatekeeper-reset-modal-cancel',
		function (resetBtn) {
			if (resetBtn.form.requestSubmit)
			{
				// Includes name="submit_reset" in the POST, same as a real click
				resetBtn.form.requestSubmit(resetBtn);
			}
			else
			{
				// Fallback for very old browsers: manually add a hidden field
				// with the same name/value as the button
				var hidden = document.createElement('input');
				hidden.type = 'hidden';
				hidden.name = resetBtn.name;
				hidden.value = resetBtn.value;
				resetBtn.form.appendChild(hidden);
				resetBtn.form.submit();
			}
		}
	);

	// Automatic mode consent modal: the confirm button stays disabled until
	// the acknowledgement checkbox is ticked. This is only a convenience:
	// the server verifies the checkbox again and records the consent.
	var ackBox = document.getElementById('bbgatekeeper-ini-auto-ack');
	var ackConfirm = document.getElementById('bbgatekeeper-ini-auto-modal-confirm');
	if (ackBox && ackConfirm)
	{
		ackBox.addEventListener('change', function () {
			ackConfirm.disabled = !ackBox.checked;
		});
	}

	setupModal(
		'bbgatekeeper-ini-auto-btn',
		'bbgatekeeper-ini-auto-modal',
		'bbgatekeeper-ini-auto-modal-confirm',
		'bbgatekeeper-ini-auto-modal-cancel',
		function (enableBtn) {
			if (enableBtn.form.requestSubmit)
			{
				// Includes name="submit_ini_auto_enable" in the POST, same as a real click
				enableBtn.form.requestSubmit(enableBtn);
			}
			else
			{
				var hidden = document.createElement('input');
				hidden.type = 'hidden';
				hidden.name = enableBtn.name;
				hidden.value = enableBtn.value;
				enableBtn.form.appendChild(hidden);
				enableBtn.form.submit();
			}
		}
	);

	// Copy the auto_prepend_file line to the clipboard
	var copyBtn = document.getElementById('bbgatekeeper-ini-copy');
	var lineInput = document.getElementById('bbgatekeeper-ini-line');
	if (copyBtn && lineInput)
	{
		var originalLabel = copyBtn.value;

		function showCopied()
		{
			copyBtn.value = copyBtn.getAttribute('data-copied') || originalLabel;
			setTimeout(function () {
				copyBtn.value = originalLabel;
			}, 2000);
		}

		function legacyCopy()
		{
			lineInput.focus();
			lineInput.select();
			try
			{
				if (document.execCommand('copy'))
				{
					showCopied();
				}
			}
			catch (e)
			{
				// Nothing else to do: the text is selected, Ctrl+C still works
			}
		}

		copyBtn.addEventListener('click', function () {
			if (navigator.clipboard && navigator.clipboard.writeText)
			{
				navigator.clipboard.writeText(lineInput.value).then(showCopied, legacyCopy);
			}
			else
			{
				legacyCopy();
			}
		});
	}

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