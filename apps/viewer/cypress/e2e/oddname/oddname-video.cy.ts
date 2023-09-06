import runTest from '../mixins/oddname'

for (const [file, type] of [
	['video1.mp4', 'video/mp4'],
	['video.mkv', 'video/mkv'],
	['video.ogv', 'video/ogv'],
	['video.webm', 'video/webm'],
]) {
	runTest(file, type)
}
