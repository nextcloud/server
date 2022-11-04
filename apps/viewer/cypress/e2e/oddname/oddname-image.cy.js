import runTest from '../mixins/oddname.js'

for (const [file, type] of [
	['image1.jpg', 'image/jpeg'],
	['image.gif', 'image/gif'],
	['image.png', 'image/png'],
	['image-small.png', 'image/png'],
	['image.svg', 'image/svg'],
]) {
	runTest(file, type)
}
