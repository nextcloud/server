import runTest from '../mixins/oddname'

for (const [file, type] of [
	['audio.mp3', 'audio/mpeg'],
	['audio.ogg', 'audio/ogg'],
]) {
	runTest(file, type)
}
