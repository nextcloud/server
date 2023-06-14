import { c } from "tar"

export const getCurrentUser = function() {
	return {
		uid: 'test',
		displayName: 'Test',
		isAdmin: false,
	}
}

export const getRequestToken = function() {
	return 'test-token-1234'
}

export const onRequestTokenUpdate = function() {}
