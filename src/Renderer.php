<?php

namespace Drupal\site_audit;

use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Class Renderer.
 */
abstract class Renderer {

  use StringTranslationTrait;

  /**
   * The Report to be rendered.
   *
   * @var \Drupal\site_audit\Report.
   */
  var $report;

  /**
   * The logger we are using for output
   */
  var $logger;

  /**
   * Any options that have been passed in
   */
  var $options;

  /**
   * Output interface
   */
  var $output;

  public function __construct($report, $logger, $options, $output) {
    $this->report = $report;
    $this->logger = $logger;
    $this->options = $options;
    $this->output = $output;
  }

  abstract public function render($detail = FALSE);
}
