export function print (data) {
	const newTab = window.open('', t('twofactor_backupcodes', 'Nextcloud backup codes'));
	newTab.document.write('<h1>' + t('twofactor_backupcodes', 'Nextcloud backup codes') + '</h1>');
	newTab.document.write(data);
	newTab.print();
	newTab.close();
}
