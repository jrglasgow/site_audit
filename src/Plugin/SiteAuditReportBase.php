<?php

namespace Drupal\site_audit\Plugin;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Base class for Site Audit Report plugins.
 */
abstract class SiteAuditReportBase extends PluginBase implements SiteAuditReportInterface {

  use StringTranslationTrait;

  /**
   * Individual check objects.
   *
   * @var array
   */
  protected $checks;

  /**
   * Percentage pass.
   *
   * @var int
   */
  protected $percent;

  /**
   * Maximum score.
   *
   * @var int
   */
  protected $scoreMax = 0;

  /**
   * Total score.
   *
   * @var int
   */
  protected $scoreTotal = 0;

  /**
   * Flag to indicate whether any of the checks are a complete FAIL.
   *
   * @var boolean
   */
  protected $hasFail = FALSE;

  /**
   * Container that's passed between each Check.
   *
   * @var array
   */
  protected $registry;

  /**
   * Get the complete name of the report.
   *
   * @return string
   *   The report name.
   */
  protected function getReportName() {
    return $this->getPluginDefinition()['name'];
  }

  /**
   * get the label(name) for the report
   */
  public function getLabel() {
    return $this->getPluginDefinition()['name'];
  }

   /**
   * get the description for the report
   */
  public function getDescription() {
    return $this->getPluginDefinition()['description'];
  }

  /**
   * get the percentage score for the report
   */
  public function getPercent() {
    return $this->percent ?: SiteAuditCheckBase::AUDIT_CHECK_SCORE_INFO;
  }

  /**
   * Constructor.
   *
   * @param array $registry
   *   Aggregates data from each individual check.
   * @param bool $opt_out
   *   If set, will not perform checks.
   */
  public function __construct($configuration, $plugin_id, $plugin_definition) {
    $checkManager = \Drupal::service('plugin.manager.site_audit_check');
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->registry = new \stdClass();

    $percent_override = NULL;

    $checks_to_skip = array();

    $checks_to_perform = $this->getChecksList();

    foreach ($checks_to_perform as $key => $check_name) {
      if (in_array($key, $checks_to_skip)) {
        unset($checks_to_perform[$key]);
      }
    }

    if (empty($checks_to_perform)) {
      $this_def = $this->getPluginDefinition();
      //throw new \RuntimeException(t('No checks are available for report \'@id\'!', array('@id' => $this_def['id'])));
    }

    $config = \Drupal::config('site_audit');
    foreach ($checks_to_perform as $check_id) {
      $opt_out = $config->get('opt_out.' . $this->getPluginId() . $check_id) != NULL;
      $check = $checkManager->createInstance($check_id, array('registry' => $this->registry, 'opt_out' => $opt_out, 'options' => $this->configuration));

      // Calculate score.
      if ($check->getScore() != SiteAuditCheckBase::AUDIT_CHECK_SCORE_INFO) {
        // Mark if there's a major failure.
        if ($check->getScore() == SiteAuditCheckBase::AUDIT_CHECK_SCORE_FAIL) {
          $this->hasFail = TRUE;
        }
        // Total.
        $this->scoreTotal += $check->getScore();
        // Maximum.
        $this->scoreMax += SiteAuditCheckBase::AUDIT_CHECK_SCORE_PASS;
      }
      // Allow Report percentage to be overridden.
      if ($check->getPercentOverride()) {
        $percent_override = $check->getPercentOverride();
      }
      // Store all checks.
      $this->checks[$check_id] = $check;
      // Abort the loop if the check says to bail.
      if ($check->shouldAbort()) {
        break;
      }
    }
    if ($percent_override) {
      $this->percent = $percent_override;
    }
    else {
      if ($this->scoreMax != 0) {
        $this->percent = round(($this->scoreTotal / $this->scoreMax) * 100);
      }
    }
  }

  /**
   * returns a list of checks for this report
   */
  public function getChecksList() {
    $this_def = $this->getPluginDefinition();
    $checkManager = \Drupal::service('plugin.manager.site_audit_check');
    static $checkDefinitions = NULL;
    if (empty($checkDefinitions)) {
      $checkDefinitions = $checkManager->getDefinitions();
    }

    $checksInReport = [];
    foreach ($checkDefinitions AS $checkDefinition) {
      if ($checkDefinition['report'] == $this_def['id']) {
        // this check belongs to this report
        $checksInReport[$checkDefinition['id']] = $checkDefinition;
      }
    }
    uasort($checksInReport, array($this, 'weightKeySort'));

    $checks = [];
    foreach ($checksInReport AS $check) {
      $checks[] = $check['id'];
    }
    return $checks;
  }

  /**
   * sort by weight
   */
  function weightKeySort($a, $b) {
   if ($a['weight'] > $b['weight']) {
     return 1;
   }
   else if ($a['weight'] < $b['weight']) {
     return -1;
   }
   // they have the same weight, sort by id
   return strcmp($a['id'], $b['id']);
  }

  /**
   * returns an array of check objects
   */
  public function getCheckObjects() {
    return $this->checks;
  }
}
