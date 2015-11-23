<?php

namespace OCP\Comments;

/**
 * Interface IComment
 *
 * This class represents a comment and offers methods for modification.
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
