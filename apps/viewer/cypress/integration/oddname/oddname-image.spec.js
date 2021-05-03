import runTest from './oddname.js'

for (const [file, type] of [
	['image1.jpg', 'image/jpeg'],
	['image.gif', 'image/gif'],
	['image.heic', 'image/heic'],
	['image.png', 'image/png'],
	['image-small.png', 'image/png'],
	['image.svg', 'image/svg'],
]) {
	runTest(file, type)
}
