import runTest from './oddname.js'

for (const [file, type] of [
	['audio.mp3', 'audio/mpeg'],
	['audio.ogg', 'audio/mpeg'],
	['audio.ogg', 'audio/ogg'],
]) {
	runTest(file, type)
}
