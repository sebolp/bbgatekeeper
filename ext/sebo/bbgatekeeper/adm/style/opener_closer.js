document.addEventListener('click', function (e) {
	var toggle = e.target.closest('.log-group-expand-toggle');
	if (!toggle)
	{
		return;
	}
	e.preventDefault();

	var wrap = toggle.closest('.log-group').querySelector('.log-group-table-wrap');
	var expanding = !wrap.classList.contains('expanded');
	wrap.classList.toggle('expanded', expanding);

	var icon = expanding ? toggle.dataset.collapseIcon : toggle.dataset.expandIcon;
	var label = expanding ? toggle.dataset.collapseLabel : toggle.dataset.expandLabel;

	toggle.innerHTML = '<i class="fa ' + icon + '" aria-hidden="true"></i> ' + label;
});
