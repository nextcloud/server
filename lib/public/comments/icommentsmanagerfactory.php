<?php

namespace OCP\Comments;

/**
 * Interface ICommentsManagerFactory
 *
 * This class is responsible for instantiating and returning an ICommentsManager
 * instance.
 *
 * @package OCP\Comments
 * @since 9.0.0
 */
interface ICommentsManagerFactory {

	/**
	 * creates and returns an instance of the ICommentsManager
	 *
	 * @return ICommentsManager
	 * @since 9.0.0
	 */
	public function getManager();
}
