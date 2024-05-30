export interface IGroup {
	id: string
	name: string

	/**
	 * Overall user count
	 */
	usercount: number

	/**
	 * Number of disabled users
	 */
	disabled: number
}
