<?php
/**
 * @file
 * Contains Drupal\site_audit\Plugin\SiteAuditCheck\ContentVocabularies
 */

namespace Drupal\site_audit\Plugin\SiteAuditCheck;

use Drupal\site_audit\Plugin\SiteAuditCheckBase;
use Drupal\site_audit\Renderer\Html;

/**
 * Provides the ContentVocabularies Check.
 *
 * @SiteAuditCheck(
 *  id = "content_vocabularies",
 *  name = @Translation("Taxonomy vocabularies"),
 *  description = @Translation("Available vocabularies and term counts"),
 *  report = "content",
 *  weight = 6,
 * )
 */
class ContentVocabularies extends SiteAuditCheckBase {

  /**
   * {@inheritdoc}.
   */
  public function getResultFail() {}

  /**
   * {@inheritdoc}.
   */
  public function getResultInfo() {
    if (!isset($this->registry->vocabulary_counts)) {
      return $this->t('The taxonomy module is not enabled.');
    }
    if (empty($this->registry->vocabulary_counts)) {
      if (drush_get_option('detail')) {
        return $this->t('No vocabularies exist.');
      }
      return '';
    }
    $ret_val = '';
    //if (drush_get_option('html') == TRUE) {
    if (TRUE) {
      $ret_val .= '<table class="table table-condensed">';
      $ret_val .= '<thead><tr><th>' . $this->t('Vocabulary') . '</th><th>' . $this->t('Terms') . '</th></tr></thead>';
      foreach ($this->registry->vocabulary_counts as $vocabulary => $count) {
        $ret_val .= "<tr><td>$vocabulary</td><td>$count</td></tr>";
      }
      $ret_val .= '</table>';
    }
    else {
      $ret_val  = $this->t('Vocabulary: Count') . PHP_EOL;
      if (!drush_get_option('json')) {
        $ret_val .= str_repeat(' ', 4);
      }
      $ret_val .= '-------------------';
      foreach ($this->registry->vocabulary_counts as $vocabulary => $count) {
        $ret_val .= PHP_EOL;
        if (!drush_get_option('json')) {
          $ret_val .= str_repeat(' ', 4);
        }
        $ret_val .= $vocabulary . ': ' . $count;
      }
    }
    return $ret_val;
  }

  /**
   * {@inheritdoc}.
   */
  public function getResultPass() {}

  /**
   * {@inheritdoc}.
   */
  public function getResultWarn() {
    return $this->getResultInfo();
  }

  /**
   * {@inheritdoc}.
   */
  public function getAction() {}

  /**
   * {@inheritdoc}.
   */
  public function calculateScore() {
    if (!\Drupal::moduleHandler()->moduleExists('taxonomy')) {
      return SiteAuditCheckBase::AUDIT_CHECK_SCORE_INFO;
    }

    if (!isset($this->registry->vocabulary_unused)) {
      $this->registry->vocabulary_unused = [];

      $vocabularies = \Drupal::entityManager()->getBundleInfo("taxonomy_term");

      $query = db_select('taxonomy_term_field_data');
      $query->addExpression('COUNT(tid)', 'count');
      $query->addField('taxonomy_term_field_data', 'vid');
      $query->orderBy('count', 'DESC');
      $query->groupBy('vid');
      $result = $query->execute();

      $this->registry->vocabulary_counts = $this->registry->vocabulary_unused = array();

      while ($row = $result->fetchAssoc()) {
        $label = $vocabularies[$row['vid']]['label'];
        $this->registry->vocabulary_counts[$label] = $row['count'];
      }

      // Check for unused vocabularies.
      foreach ($vocabularies as $vocabulary) {
        if (array_search($vocabulary['label'], array_keys($this->registry->vocabulary_counts)) === FALSE) {
          $this->registry->vocabulary_unused[] = $vocabulary['label'];
          $this->registry->vocabulary_counts[$vocabulary['label']] = 0;
        }
      }
      // No need to check for unused vocabularies if there aren't any.
      if (empty($this->registry->vocabulary_counts)) {
        $this->abort = TRUE;
      }
    }

    return SiteAuditCheckBase::AUDIT_CHECK_SCORE_INFO;
  }
}