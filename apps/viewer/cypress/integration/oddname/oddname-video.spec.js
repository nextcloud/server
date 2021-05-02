import runTest from './oddname.js'

for (const [file, type] of [
	['image.webp', 'image/webp'],
	['video1.mp4', 'video/mp4'],
	['video.mkv', 'video/mkv'],
	['video.ogv', 'video/ogv'],
	['video.webm', 'video/webm'],
]) {
	runTest(file, type)
}
