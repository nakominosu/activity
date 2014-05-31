<?php

/**
 * ownCloud - Activity App
 *
 * @author Joas Schilling
 * @copyright 2014 Joas Schilling nickvergessen@owncloud.com
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Activity;

class DataHelper
{
	/**
	 * Prepares the parameters before we use them in the subject or message
	 * @param \OC_L10N $l Language object, if you want to use a different language (f.e. to send an email)
	 * @param string $app
	 * @param string $text
	 * @param array $params
	 * @param mixed $filePosition Position of a file in $params
	 * @param bool $stripPath Shall we remove the path from the filename
	 * @param bool $highlightParams
	 * @return array
	 */
	public static function prepareFilesParams(\OC_L10N $l, $app, $text, $params, $filePosition = false, $stripPath = false, $highlightParams = false) {
		if ($app === 'files' && $text) {
			$preparedParams = array();
			foreach ($params as $i => $param) {
				if (is_array($param)) {
					$parameterList = array();
					foreach ($param as $parameter) {
						$parameterList[] = self::prepareParam($parameter, $filePosition === $i, $stripPath, $highlightParams);
					}
					$preparedParams[] = self::joinParameterList($l, $parameterList, $highlightParams);
				} else {
					$preparedParams[] = self::prepareParam($param, $filePosition === $i, $stripPath, $highlightParams);
				}
			}
			return $preparedParams;
		}
		return $params;
	}

	/**
	 * Prepares the parameter for usage
	 *
	 * Removes the path from filenames and adds highlights
	 *
	 * @param string $param
	 * @param bool $isFilePosition Are we on the filename position?
	 * @param bool $stripPath Shall we remove the path from the filename
	 * @param bool $highlightParams
	 * @return string
	 */
	protected static function prepareParam($param, $isFilePosition, $stripPath, $highlightParams) {
		if ($isFilePosition && strpos($param, '/') === 0) {
			// Remove the path from the file string
			$param = substr($param, 1);
		}

		$newParam = $param;
		if ($stripPath === true && $isFilePosition && strrpos($param, '/') !== false) {
			// Remove the path from the file string
			$newParam = substr($param, strrpos($param, '/') + 1);
		}

		if ($highlightParams) {
			return '<strong class="tooltip" title="' . \OC_Util::sanitizeHTML($param) . '">' . \OC_Util::sanitizeHTML($newParam) . '</strong>';
		} else {
			return $newParam;
		}
	}

	/**
	 * Returns a list of grouped parameters
	 *
	 * 2 parameters are joined by "and":
	 * => A and B
	 * Up to 5 parameters are joined by "," and "and":
	 * => A, B, C, D and E
	 * More than 5 parameters are joined by "," and trimmed:
	 * => A, B, C and #n more
	 *
	 * @param \OC_L10N $l
	 * @param array $parameterList
	 * @param bool $highlightParams
	 * @return string
	 */
	protected static function joinParameterList(\OC_L10N $l, $parameterList, $highlightParams) {
		if (empty($parameterList)) {
			return '';
		}

		$count = sizeof($parameterList);
		$lastItem = array_pop($parameterList);

		if ($count == 1)
		{
			return $lastItem;
		}
		else if ($count == 2)
		{
			$firstItem = array_pop($parameterList);
			return $l->t('%s and %s', array($firstItem, $lastItem));
		}
		else if ($count <= 5)
		{
			$list = implode($l->t(', '), $parameterList);
			return $l->t('%s and %s', array($list, $lastItem));
		}

		$firstParams = array_slice($parameterList, 0, 3);
		$list = implode($l->t(', '), $firstParams);
		if ($highlightParams) {
			return $l->n('%s and <strong>%n</strong> more', '%s and <strong>%n</strong> more', $count - 3, array($list));
		}
		return $l->n('%s and %n more', '%s and %n more', $count - 3, array($list));
	}

	/**
	 * @brief Translate an event string with the translations from the app where it was send from
	 * @param string $app The app where this event comes from
	 * @param string $text The text including placeholders
	 * @param array $params The parameter for the placeholder
	 * @param bool $stripPath Shall we strip the path from file names?
	 * @param bool $highlightParams Shall we highlight the parameters in the string?
	 *             They will be highlighted with `<strong>`, all data will be passed through
	 *             \OC_Util::sanitizeHTML() before, so no XSS is possible.
	 * @param \OC_L10N $l Language object, if you want to use a different language (f.e. to send an email)
	 * @return string translated
	 */
	public static function translation($app, $text, $params, $stripPath = false, $highlightParams = false, \OC_L10N $l = null) {
		if (!$text) {
			return '';
		}
		if ($l === null) {
			$l = \OCP\Util::getL10N('activity');
		}

		if ($app === 'files') {
			$params = self::prepareFilesParams($l, $app, $text, $params, 0, $stripPath, $highlightParams);
			if ($text === 'created_self') {
				return $l->t('You created %1$s', $params);
			}
			else if ($text === 'created_by') {
				return $l->t('%2$s created %1$s', $params);
			}
			else if ($text === 'changed_self') {
				return $l->t('You changed %1$s', $params);
			}
			else if ($text === 'changed_by') {
				return $l->t('%2$s changed %1$s', $params);
			}
			else if ($text === 'deleted_self') {
				return $l->t('You deleted %1$s', $params);
			}
			else if ($text === 'deleted_by') {
				return $l->t('%2$s deleted %1$s', $params);
			}
			else if ($text === 'shared_user_self') {
				return $l->t('You shared %1$s with %2$s', $params);
			}
			else if ($text === 'shared_group_self') {
				return $l->t('You shared %1$s with group %2$s', $params);
			}
			else if ($text === 'shared_with_by') {
				return $l->t('%2$s shared %1$s with you', $params);
			}
			else if ($text === 'shared_link_self') {
				return $l->t('You shared %1$s', $params);
			}

			return $l->t($text, $params);
		} else {
			$l = \OCP\Util::getL10N($app);
			return $l->t($text, $params);
		}
	}

	/**
	 * Process the rows from the database and also groups them if requested
	 *
	 * @param array $activities
	 * @param bool $allowGrouping
	 * @return array
	 */
	public static function prepareActivities($activities, $allowGrouping = true) {
		$helper = new \OCA\Activity\GroupHelper($allowGrouping);

		foreach ($activities as $row) {
			$helper->addActivity($row);
		}

		return $helper->getActivities();
	}
}
