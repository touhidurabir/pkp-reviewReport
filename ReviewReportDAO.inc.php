<?php

/**
 * @file plugins/reports/reviewReport/ReviewReportDAO.inc.php
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2003-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ReviewReportDAO
 * @ingroup plugins_reports_review
 * @see ReviewReportPlugin
 *
 * @brief Review report DAO
 */

import('lib.pkp.classes.submission.SubmissionComment');
import('lib.pkp.classes.db.DBRowIterator');

class ReviewReportDAO extends DAO {
	/**
	 * Get the review report data.
	 * @param $contextId int Context ID
	 * @return array
	 */
	function getReviewReport($contextId) {
		$locale = AppLocale::getLocale();

		import('lib.pkp.classes.db.DBRowIterator');
		$commentsReturner = new DBRowIterator($this->retrieve(
			'SELECT	submission_id,
				comments,
				author_id
			FROM	submission_comments
			WHERE	comment_type = ?',
			array(
				COMMENT_TYPE_PEER_REVIEW
			)
		));

		$userDao = DAORegistry::getDAO('UserDAO');
		$params = array_merge(
			array(
				$locale, // Submission title
				'title',
				'title',
			),
			$userDao->getFetchParameters(),
			array((int) $contextId)
		);
		$reviewsReturner = new DBRowIterator($this->retrieve(
			'SELECT	r.stage_id AS stage_id,
				r.round AS round,
				COALESCE(asl.setting_value, aspl.setting_value) AS submission,
				a.submission_id AS submission_id,
				u.user_id AS reviewer_id,
				u.username AS reviewer,
				' . $userDao->getFetchColumns() .',
				r.date_assigned AS dateAssigned,
				r.date_notified AS dateNotified,
				r.date_confirmed AS dateConfirmed,
				r.date_completed AS dateCompleted,
				r.date_reminded AS dateReminded,
				(r.declined=1) AS declined,
				r.recommendation AS recommendation
			FROM	review_assignments r
				LEFT JOIN submissions a ON r.submission_id = a.submission_id
				LEFT JOIN submission_settings asl ON (a.submission_id = asl.submission_id AND asl.locale = ? AND asl.setting_name = ?)
				LEFT JOIN submission_settings aspl ON (a.submission_id = aspl.submission_id AND aspl.locale = a.locale AND aspl.setting_name = ?)
				LEFT JOIN users u ON (u.user_id = r.reviewer_id)
				' . $userDao->getFetchJoins() .'
			WHERE	 a.context_id = ?
			ORDER BY submission',
			$params
		));

		return array($commentsReturner, $reviewsReturner);
	}
}

