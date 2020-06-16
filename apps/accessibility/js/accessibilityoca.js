OCA.Accessibility = OCP.InitialState.loadState('accessibility', 'data')
if (OCA.Accessibility.theme !== false) {
	document.body.classList.add(OCA.Accessibility.theme);
}
